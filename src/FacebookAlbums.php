<?php

/**
 * facebook-albums extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2015, terminal42
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    commercial
 */

namespace Terminal42\FacebookAlbumsExtension;

use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;
use Facebook\FacebookSession;
use Facebook\GraphAlbum;

/**
 * Class FacebookAlbums
 *
 * Provide methods to handle Facebook albums.
 */
class FacebookAlbums
{

    /**
     * Facebook sessions
     * @var array
     */
    protected static $facebookSessions = [];

    /**
     * Initialize the Facebook session
     *
     * @param AccountModel $accountModel
     *
     * @return FacebookSession|null
     */
    public static function initFacebookSession(AccountModel $accountModel)
    {
        $appId     = $accountModel->appId;
        $appSecret = $accountModel->appSecret;

        if (!isset(static::$facebookSessions[$appId])) {
            FacebookSession::setDefaultApplication($appId, $appSecret);
            static::$facebookSessions[$appId] = FacebookSession::newAppSession();

            try {
                static::$facebookSessions[$appId]->validate();
            } catch (FacebookRequestException $e) {
                \System::log('Facebook request exception: ' . $e->getMessage(), __METHOD__, TL_ERROR);
                static::$facebookSessions[$appId] = null;
            } catch (\Exception $e) {
                \System::log('Facebook session exception: ' . $e->getMessage(), __METHOD__, TL_ERROR);
                static::$facebookSessions[$appId] = null;
            }
        }

        return static::$facebookSessions[$appId];
    }

    /**
     * Return true if the album is outdated and should be fetched again
     *
     * @param AccountModel $accountModel
     * @param int          $albumId
     * @param int          $tstamp
     *
     * @return bool
     */
    public static function isAlbumOutdated(AccountModel $accountModel, $albumId, $tstamp)
    {
        $album = static::getAlbum($accountModel, $albumId);

        if ($album === null) {
            return false;
        }

        return $album->getUpdatedTime()->getTimestamp() > $tstamp;
    }

    /**
     * Fetch the album images
     *
     * @param AccountModel $accountModel
     * @param int          $albumId
     */
    public static function fetchAlbumImages(AccountModel $accountModel, $albumId)
    {
        $album = static::getAlbum($accountModel, $albumId);

        if ($album === null) {
            return;
        }

        $folder = $accountModel->getFolder();

        if ($folder === null) {
            return;
        }

        // @todo - fetch images
    }

    /**
     * @param AccountModel $accountModel
     * @param string       $albumId
     *
     * @return GraphAlbum|null
     */
    public static function getAlbum(AccountModel $accountModel, $albumId)
    {
        $session = static::initFacebookSession($accountModel);

        if ($session === null) {
            return null;
        }

        $me = (new FacebookRequest(
            $session, 'GET', sprintf('/%s', $albumId)
        ))->execute()->getGraphObject(GraphAlbum::className());
    }

    /**
     * Get the albums
     *
     * @param AccountModel $accountModel
     *
     * @return array
     */
    public static function getAlbums(AccountModel $accountModel)
    {
        $session = static::initFacebookSession($accountModel);

        if ($session === null) {
            return null;
        }

        $graphObject = (new FacebookRequest(
            $session, 'GET', sprintf('/%s/albums', $accountModel->userId)
        ))->execute()->getGraphObject();

        dump($graphObject);
        exit;
    }
}
