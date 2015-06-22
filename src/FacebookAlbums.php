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
use Facebook\GraphObject;

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
     *
     * @return bool
     */
    public static function isAlbumOutdated(AccountModel $accountModel, $albumId)
    {
        $album = static::getAlbum($accountModel, $albumId);

        if ($album === null) {
            return false;
        }

        $metaFile = static::getMetaFile($accountModel, $albumId);

        // There is no meta file yet
        if ($metaFile === null) {
            return true;
        }

        $data = json_decode($metaFile->getContent(), true);

        if ($data === null) {
            return true;
        }

        return $album->getUpdatedTime()->getTimestamp() > $data['date_updated'];
    }

    /**
     * Return true if the album is new and must be fetched
     *
     * @param AccountModel $accountModel
     * @param int          $albumId
     *
     * @return bool
     */
    public static function isAlbumNew(AccountModel $accountModel, $albumId)
    {
        return static::getMetaFile($accountModel, $albumId) === null;
    }

    /**
     * Get the folder
     *
     * @param AccountModel $accountModel
     * @param int          $albumId
     *
     * @return \Folder|null
     */
    public static function getFolder(AccountModel $accountModel, $albumId = null)
    {
        $folderModel = $accountModel->getFolder();

        if ($folderModel === null) {
            return null;
        }

        $path = $folderModel->path;

        // Add the album ID
        if ($albumId) {
            $path .= '/' . $albumId;
        }

        return new \Folder($path);
    }

    /**
     * Get the meta file
     *
     * @param AccountModel $accountModel
     * @param int          $albumId
     *
     * @return \File|null
     *
     * @throws \Exception
     */
    public static function getMetaFile(AccountModel $accountModel, $albumId)
    {
        $folder = static::getFolder($accountModel, $albumId);

        if ($folder === null) {
            return null;
        }

        $file = new \File($folder->path . '/facebook_meta.json');

        // Synchronize the file with database
        if ($file->getModel() === null) {
            \Dbafs::addResource($file->path);
        }

        return $file;
    }

    /**
     * Update the meta file
     *
     * @param string $folder
     * @param array  $photos
     */
    protected static function updateMetaFile(AccountModel $accountModel, $albumId, array $photos)
    {
        $file = static::getMetaFile($accountModel, $albumId);

        if ($file === null) {
            return;
        }

        $album = static::getAlbum($accountModel, $albumId);

        if ($album === null) {
            return;
        }

        $data = [
            'id'           => $album->getId(),
            'date_created' => $album->getCreatedTime()->getTimestamp(),
            'date_updated' => $album->getUpdatedTime()->getTimestamp(),
            'files'        => [],
        ];

        /** @var GraphObject $album */
        foreach ($photos as $photo) {
            $dateCreated = new \DateTime($photo->getProperty('created_time'));
            $dateUpdated = new \DateTime($photo->getProperty('updated_time'));

            $data['files'][] = [
                'id'           => $photo->getProperty('id'),
                'name'         => basename($photo->getProperty('source')),
                'date_created' => $dateCreated->getTimestamp(),
                'date_updated' => $dateUpdated->getTimestamp(),
            ];
        }

        $file->truncate();
        $file->write(json_encode($data, JSON_PRETTY_PRINT));
        $file->close();
    }

    /**
     * Fetch the album images and save them into the folder
     *
     * @param AccountModel $accountModel
     * @param int          $albumId
     */
    public static function fetchAlbumImages(AccountModel $accountModel, $albumId)
    {
        $folder = static::getFolder($accountModel, $albumId);

        if ($folder === null) {
            return;
        }

        $photos = static::getAlbumPhotos($accountModel, $albumId);

        if ($photos === null) {
            return;
        }

        /** @var GraphObject $album */
        foreach ($photos as $photo) {
            $request = new \Request();
            $request->send($photo->getProperty('source'));

            if ($request->hasError()) {
                continue;
            }

            $file = new \File($folder->path . '/' . basename($photo->getProperty('source')));
            $file->write($request->response);
            $file->close();
        }

        static::updateMetaFile($accountModel, $albumId, $photos);
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
            return [];
        }

        $graphObject = (new FacebookRequest(
            $session, 'GET', sprintf('/%s/albums', $accountModel->pageId)
        ))->execute()->getGraphObject();

        $albums = $graphObject->getPropertyAsArray('data');

        if (!is_array($albums) || empty($albums)) {
            return [];
        }

        $return = [];

        /** @var GraphObject $album */
        foreach ($albums as $album) {
            $return[] = $album->cast(GraphAlbum::className());
        }

        return $return;
    }

    /**
     * Get the album
     *
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

        return (new FacebookRequest(
            $session, 'GET', sprintf('/%s', $albumId)
        ))->execute()->getGraphObject(GraphAlbum::className());
    }

    /**
     * Get the photos of particular album
     *
     * @param AccountModel $accountModel
     * @param string       $albumId
     *
     * @return array
     */
    public static function getAlbumPhotos(AccountModel $accountModel, $albumId)
    {
        $session = static::initFacebookSession($accountModel);

        if ($session === null) {
            return [];
        }

        $graphObject = (new FacebookRequest(
            $session, 'GET', sprintf('/%s/photos', $albumId)
        ))->execute()->getGraphObject();

        $photos = $graphObject->getPropertyAsArray('data');

        if (!is_array($photos) || empty($photos)) {
            return [];
        }

        return $photos;
    }
}
