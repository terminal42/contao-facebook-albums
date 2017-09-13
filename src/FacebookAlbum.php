<?php

/**
 * facebook-albums extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2008-2015, terminal42
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    commercial
 */

namespace Terminal42\FacebookAlbumsExtension;

use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Facebook\GraphNodes\GraphAlbum;

/**
 * Class FacebookAlbum
 *
 * Provide methods to handle Facebook albums.
 */
class FacebookAlbum
{

    /**
     * Account model
     * @var AccountModel
     */
    protected $accountModel;

    /**
     * Album ID
     * @var string
     */
    protected $albumId;

    /**
     * Facebook instance
     * @var Facebook
     */
    protected $facebook;

    /**
     * Meta file name
     * @var string
     */
    protected $metaFileName = 'facebook_meta';

    /**
     * Initialize the object
     *
     * @param AccountModel $accountModel
     */
    public function __construct(AccountModel $accountModel)
    {
        $this->accountModel = $accountModel;
    }

    /**
     * Get the account model
     *
     * @return AccountModel
     */
    public function getAccountModel()
    {
        return $this->accountModel;
    }

    /**
     * Set the account model
     *
     * @param AccountModel $accountModel
     */
    public function setAccountModel(AccountModel $accountModel)
    {
        $this->accountModel = $accountModel;
    }

    /**
     * Get album ID
     *
     * @return string
     */
    public function getAlbumId()
    {
        return $this->albumId;
    }

    /**
     * Set album ID
     *
     * @param string $albumId
     */
    public function setAlbumId($albumId)
    {
        $this->albumId = $albumId;
    }

    /**
     * Connect to the Facebook
     *
     * @return bool
     */
    public function connect()
    {
        try {
            $this->facebook = new Facebook(
                [
                    'app_id'                => $this->accountModel->appId,
                    'app_secret'            => $this->accountModel->appSecret,
                    'default_graph_version' => 'v2.10'
                ]
            );
        } catch (FacebookSDKException $e) {
            \System::log('Facebook SDK exception: ' . $e->getMessage(), __METHOD__, TL_ERROR);

            $this->facebook = null;
        }

        return $this->facebook !== null;
    }

    /**
     * Return true if the album is outdated and should be fetched again
     *
     * @return bool
     */
    public function isOutdated()
    {
        $album = $this->getAlbum();

        if ($album === null) {
            return false;
        }

        $metaFile = $this->getMetaFile();

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
     * @return bool
     */
    public function isNew()
    {
        $folder = $this->getAlbumFolder();

        if ($folder === null) {
            return false;
        }

        return !is_file(TL_ROOT . '/' . $folder->path . '/' . $this->metaFileName . '.json');
    }

    /**
     * Get the root folder
     *
     * @return \Folder|null
     */
    public function getRootFolder()
    {
        $folderModel = $this->accountModel->getFolder();

        if ($folderModel === null) {
            return null;
        }

        return new \Folder($folderModel->path);
    }

    /**
     * Get the album folder
     *
     * @return \Folder|null
     */
    public function getAlbumFolder()
    {
        $rootFolder = $this->getRootFolder();

        if ($rootFolder === null) {
            return null;
        }

        return new \Folder($rootFolder->path . '/' . $this->albumId);
    }

    /**
     * Get the meta file
     *
     * @return \File|null
     *
     * @throws \Exception
     */
    public function getMetaFile()
    {
        $folder = $this->getAlbumFolder();

        if ($folder === null) {
            return null;
        }

        $file = new \File($folder->path . '/' . $this->metaFileName . '.json');

        // Synchronize the file with database
        if ($file->getModel() === null) {
            \Dbafs::addResource($file->path);
        }

        return $file;
    }

    /**
     * Update the meta file
     *
     * @param array $photos
     */
    protected function updateMetaFile(array $photos)
    {
        $file = $this->getMetaFile();

        if ($file === null) {
            return;
        }

        $album = $this->getAlbum();

        if ($album === null) {
            return;
        }

        $dateCreated = $album->getCreatedTime();
        $dateUpdated = $album->getUpdatedTime();

        $data = [
            'id'           => $album->getId(),
            'link'         => $album->getLink(),
            'count'        => $album->getCount(),
            'date_created' => $dateCreated ? $dateCreated->getTimestamp() : 0,
            'date_updated' => $dateUpdated ? $dateUpdated->getTimestamp() : 0,
            'files'        => [],
        ];

        foreach ($photos as $photo) {
            $dateCreated = new \DateTime($photo['created_time']);
            $dateUpdated = new \DateTime($photo['updated_time']);
            $source      = parse_url($photo['source']);

            $data['files'][] = [
                'id'           => $photo['id'],
                'name'         => basename($source['path']),
                'date_created' => $dateCreated ? $dateCreated->getTimestamp() : 0,
                'date_updated' => $dateUpdated ? $dateUpdated->getTimestamp() : 0,
            ];
        }

        $file->truncate();
        $file->write(json_encode($data, JSON_PRETTY_PRINT));
        $file->close();
    }

    /**
     * Fetch the album images and save them into the folder
     */
    public function fetchImages()
    {
        $folder = $this->getAlbumFolder();

        if ($folder === null) {
            return;
        }

        $photos = $this->getPhotos();

        if (empty($photos)) {
            return;
        }

        foreach ($photos as $photo) {
            $request = new \Request();
            $request->send($photo['source']);

            if ($request->hasError()) {
                continue;
            }

            $source = parse_url($photo['source']);

            $file = new \File($folder->path . '/' . basename($source['path']));
            $file->write($request->response);
            $file->close();
        }

        $this->updateMetaFile($photos);
    }

    /**
     * Get the albums
     *
     * @return array
     */
    public function getAlbums()
    {
        $response = $this->request(sprintf('/%s/albums', $this->accountModel->pageId));

        if ($response === null) {
            return [];
        }

        $albums = [];

        foreach ($response->getGraphEdge() as $album) {
            $albums[] = $album->uncastItems();
        }

        return $albums;
    }

    /**
     * Get the album
     *
     * @return GraphAlbum|null
     */
    public function getAlbum()
    {
        $response = $this->request(sprintf('/%s?fields=count,created_time,link,updated_time', $this->albumId));

        if ($response === null) {
            return null;
        }

        return $response->getGraphAlbum();
    }

    /**
     * Get the photos of particular album
     *
     * @return array
     */
    public function getPhotos()
    {
        $response = $this->request(sprintf('/%s/photos?fields=id,source,created_time,updated_time', $this->albumId));

        if ($response === null) {
            return [];
        }

        $photos = [];

        foreach ($response->getGraphEdge() as $photo) {
            $photos[] = $photo->uncastItems();
        }

        return $photos;
    }

    /**
     * Perform a Facebook request
     *
     * @param string $action
     *
     * @return \Facebook\FacebookResponse|null
     */
    protected function request($action)
    {
        if ($this->facebook === null) {
            return null;
        }

        try {
            $response = $this->facebook->get($action, $this->facebook->getApp()->getAccessToken());
        } catch (FacebookResponseException $e) {
            \System::log('Facebook response exception: ' . $e->getMessage(), __METHOD__, TL_ERROR);

            return null;
        } catch (FacebookSDKException $e) {
            \System::log('Facebook SDK exception: ' . $e->getMessage(), __METHOD__, TL_ERROR);

            return null;
        }

        return $response;
    }

    /**
     * Get the file models
     *
     * @return \Model\Collection|null
     */
    public function getFileModels()
    {
        $albumFolder = $this->getAlbumFolder();

        if ($albumFolder === null) {
            return null;
        }

        return \FilesModel::findByPid($albumFolder->getModel()->uuid);
    }

    /**
     * Get the images
     *
     * @param string $sorting
     * @param string $metaLanguage
     * @param bool   $metaIgnore
     * @param string $metaFallbackLanguage
     *
     * @return array
     */
    public function getImages($sorting, $metaLanguage, $metaIgnore = false, $metaFallbackLanguage = null)
    {
        $files = $this->getFileModels();

        if ($files === null) {
            return [];
        }

        $images = [];

        while ($files->next()) {
            // Skip subfolders
            if ($files->type == 'folder') {
                continue;
            }

            $file = new \File($files->path, true);

            if (!$file->isImage) {
                continue;
            }

            $meta = \Frontend::getMetaData($files->meta, $metaLanguage);

            if (empty($meta)) {
                if ($metaIgnore) {
                    continue;
                } elseif ($metaFallbackLanguage !== null) {
                    $meta = \Frontend::getMetaData($files->meta, $metaFallbackLanguage);
                }
            }

            // Use the file name as title if none is given
            if ($meta['title'] == '') {
                $meta['title'] = specialchars($file->basename);
            }

            // Add the image
            $images[$files->path] = [
                'id'        => $files->id,
                'uuid'      => $files->uuid,
                'name'      => $file->basename,
                'singleSRC' => $files->path,
                'alt'       => $meta['title'],
                'imageUrl'  => $meta['link'],
                'caption'   => $meta['caption']
            ];
        }

        return $this->sortImages($images, $this->getMetaData(), $sorting);
    }

    /**
     * Get the meta data
     *
     * @return array
     */
    public function getMetaData()
    {
        $file = $this->getMetaFile();

        if ($file === null) {
            return [];
        }

        $data = json_decode($file->getContent(), true);

        if ($data === null) {
            return [];
        }

        return $data;
    }

    /**
     * Sort the images
     *
     * @param array  $images
     * @param array  $metaData
     * @param string $sorting
     *
     * @return array
     */
    protected function sortImages(array $images, array $metaData, $sorting)
    {
        switch ($sorting) {
            default:
            case 'facebook':
                $order = [];

                // Order the images
                foreach ($metaData['files'] as $meta) {
                    foreach ($images as $path => $image) {
                        if ($meta['name'] == $image['name']) {
                            $order[] = $image;
                            unset($images[$path]);
                        }
                    }
                }

                // Append the rest of images
                if (!empty($images)) {
                    $order = array_merge($order, $images);
                }

                // Revert the variable
                $images = $order;
                break;

            case 'date_asc':
            case 'date_desc':
                $orderHelper = [];

                // Prepare the order array
                foreach ($metaData['files'] as $meta) {
                    $orderHelper[$meta['name']] = $meta['date_updated'];
                }

                asort($orderHelper, SORT_NUMERIC);

                // Sort descending
                if (($sorting == 'date_desc')) {
                    $orderHelper = array_reverse($orderHelper, true);
                }

                $order = [];

                // Order images
                foreach (array_keys($orderHelper) as $orderImage) {
                    foreach ($images as $path => $image) {
                        if ($orderImage == $image['name']) {
                            $order[] = $image;
                            unset($images[$path]);
                        }
                    }
                }

                // Append the rest of images
                if (!empty($images)) {
                    $order = array_merge($order, $images);
                }

                // Revert the variable
                $images = $order;
                break;

            case 'random':
                shuffle($images);
                break;
        }

        return $images;
    }
}
