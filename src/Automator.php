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
 * Class Automator
 *
 * Provide methods to handle automatic tasks.
 */
class Automator
{

    /**
     * Update the Facebook albums
     */
    public function updateAlbums()
    {
        $contentModels = \ContentModel::findBy('type', 'facebook_album');

        if ($contentModels === null) {
            return;
        }

        $count = 0;

        /** @var \ContentModel $contentModel */
        foreach ($contentModels as $contentModel) {
            $accountModel = AccountModel::findByPk($contentModel->accountModel);

            if ($accountModel === null) {
                continue;
            }

            $facebookAlbum = new FacebookAlbum($accountModel);
            $facebookAlbum->setAlbumId($contentModel->facebook_album);

            if ($facebookAlbum->isOutdated()) {
                $facebookAlbum->fetchImages();
                $count++;
            }
        }

        if ($count > 0) {
            \System::log(sprintf('Facebook albums: %s have been updated', $count), __METHOD__, TL_CRON);
        }
    }
}
