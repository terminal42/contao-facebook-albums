<?php

/**
 * facebook-albums extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2015, terminal42
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    commercial
 */

/**
 * Back end modules
 */
$GLOBALS['BE_MOD']['system']['facebook_albums'] = [
    'tables' => ['tl_facebook_album_account'],
];

/**
 * Content elements
 */
$GLOBALS['TL_CTE']['media']['facebook_album'] = 'Terminal42\FacebookAlbumsBundle\AlbumContentElement';

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_facebook_album_account'] = 'Terminal42\FacebookAlbumsBundle\AccountModel';

/**
 * Cron jobs
 */
$GLOBALS['TL_CRON']['hourly'][] = ['Terminal42\FacebookAlbumsBundle\Automator', 'updateAlbums'];
