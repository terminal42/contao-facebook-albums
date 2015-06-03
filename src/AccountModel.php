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
 * Class AccountModel
 *
 * Reads and writes Facebook album accounts.
 */
class AccountModel extends \Model
{

    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_facebook_album_account';

    /**
     * Get the folder
     *
     * @return \FilesModel|null
     */
    public function getFolder()
    {
        return \FilesModel::findByPk($this->folder);
    }
}
