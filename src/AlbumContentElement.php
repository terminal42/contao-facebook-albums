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
class AlbumContentElement extends \ContentGallery
{

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

        // Update the album if it is outdated
        if (FacebookAlbums::isAlbumOutdated($accountModel, $this->facebook_album, $this->facebook_album_tstamp)) {
            FacebookAlbums::fetchAlbumImages($accountModel, $this->facebook_album);
        }

        // @todo
        // $this->multiSRC = '';

        return parent::generate();
    }
}
