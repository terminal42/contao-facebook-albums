<?php

/**
 * facebook-albums extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2015, terminal42
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    commercial
 */

namespace Terminal42\FacebookAlbumsExtension;

use Facebook\GraphAlbum;

/**
 * Class ContentDca
 *
 * Provide miscellaneous methods that are used by data container array.
 */
class ContentDca
{

    /**
     * Get the albums
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getAlbums(\DataContainer $dc = null)
    {
        if (!$dc->id) {
            return [];
        }

        $elementModel = \ContentModel::findByPk($dc->id);

        if ($elementModel === null) {
            return [];
        }

        $accountModel = AccountModel::findByPk($elementModel->facebook_album_account);

        if ($accountModel === null) {
            return [];
        }

        $return = [];
        $albums = FacebookAlbums::getAlbums($accountModel);

        /** @var GraphAlbum $album */
        foreach ($albums as $album) {
            $return[$album->getId()] = $album->getName();
        }

        return $return;
    }
}
