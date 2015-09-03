<?php

/**
 * facebook-albums extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2015, terminal42
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    commercial
 */

namespace Terminal42\FacebookAlbumsExtension;

/**
 * Class AlbumContentElement
 *
 * Content element "facebook album".
 */
class AlbumContentElement extends \ContentElement
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_gallery';

    /**
     * Facebook album
     * @var FacebookAlbum
     */
    protected $facebookAlbum;

    /**
     * Return if there are no files
     *
     * @return string
     */
    public function generate()
    {
        $accountModel = AccountModel::findByPk($this->facebook_album_account);

        if ($accountModel === null) {
            return '';
        }

        $this->facebookAlbum = new FacebookAlbum($accountModel);
        $this->facebookAlbum->setAlbumId($this->facebook_album);

        if ($this->facebookAlbum->getAlbumFolder() === null) {
            return '';
        }

        // Could not connect to the Facebook
        if (!$this->facebookAlbum->connect()) {
            return '';
        }

        // Create the album if it is new
        if ($this->facebookAlbum->isNew()) {
            $this->facebookAlbum->fetchImages();
        }

        return parent::generate();
    }

    /**
     * Generate the content element
     */
    protected function compile()
    {
        $images = $this->facebookAlbum->getImages(
            $this->facebook_album_order,
            $GLOBALS['objPage']->language,
            $this->metaIgnore,
            $GLOBALS['objPage']->rootFallbackLanguage
        );

        if (empty($images)) {
            return;
        }

        $images = array_values($images);

        // Limit the total number of items
        if ($this->numberOfItems > 0) {
            $images = array_slice($images, 0, $this->numberOfItems);
        }

        $offset = 0;
        $total  = count($images);
        $limit  = $total;

        // Pagination
        if ($this->perPage > 0) {
            // Get the current page
            $id   = 'page_g' . $this->id;
            $page = (\Input::get($id) !== null) ? \Input::get($id) : 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                /** @var \PageError404 $objHandler */
                $handler = new $GLOBALS['TL_PTY']['error_404']();
                $handler->generate($GLOBALS['objPage']->id);
            }

            // Set limit and offset
            $offset = ($page - 1) * $this->perPage;
            $limit  = min($this->perPage + $offset, $total);

            $pagination                 = new \Pagination(
                $total,
                $this->perPage,
                \Config::get('maxPaginationLinks'),
                $id
            );
            $this->Template->pagination = $pagination->generate("\n  ");
        }

        $this->Template->images = $this->generatePartial($images, $offset, $limit);
    }

    /**
     * Generate the partial template
     *
     * @param array $images
     * @param int   $offset
     * @param int   $limit
     *
     * @return string
     */
    protected function generatePartial(array $images, $offset, $limit)
    {
        $rowcount   = 0;
        $colwidth   = floor(100 / $this->perRow);
        $maxWidth   = (TL_MODE == 'BE') ? floor((640 / $this->perRow)) : floor(
            (\Config::get('maxImageWidth') / $this->perRow)
        );
        $lightboxId = 'lightbox[lb' . $this->id . ']';
        $body       = array();

        // Rows
        for ($i = $offset; $i < $limit; $i = ($i + $this->perRow)) {
            $class_tr = '';

            if ($rowcount == 0) {
                $class_tr .= ' row_first';
            }

            if (($i + $this->perRow) >= $limit) {
                $class_tr .= ' row_last';
            }

            $class_eo = (($rowcount % 2) == 0) ? ' even' : ' odd';

            // Columns
            for ($j = 0; $j < $this->perRow; $j++) {
                $class_td = '';

                if ($j == 0) {
                    $class_td .= ' col_first';
                }

                if ($j == ($this->perRow - 1)) {
                    $class_td .= ' col_last';
                }

                $cell = new \stdClass();
                $key  = 'row_' . $rowcount . $class_tr . $class_eo;

                // Empty cell
                if (!is_array($images[($i + $j)]) || ($j + $i) >= $limit) {
                    $cell->colWidth = $colwidth . '%';
                    $cell->class    = 'col_' . $j . $class_td;
                } else {
                    // Add size and margin
                    $images[($i + $j)]['size']        = $this->size;
                    $images[($i + $j)]['imagemargin'] = $this->imagemargin;
                    $images[($i + $j)]['fullsize']    = $this->fullsize;

                    $this->addImageToTemplate($cell, $images[($i + $j)], $maxWidth, $lightboxId);

                    // Add column width and class
                    $cell->colWidth = $colwidth . '%';
                    $cell->class    = 'col_' . $j . $class_td;
                }

                $body[$key][$j] = $cell;
            }

            ++$rowcount;
        }

        $templateName = 'gallery_default';

        // Use a custom template
        if (TL_MODE == 'FE' && $this->galleryTpl != '') {
            $templateName = $this->galleryTpl;
        }

        /** @var \FrontendTemplate|object $objTemplate */
        $template = new \FrontendTemplate($templateName);
        $template->setData($this->arrData);

        $template->body     = $body;
        $template->headline = $this->headline; // see #1603

        return $template->parse();
    }
}
