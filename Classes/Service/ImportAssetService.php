<?php
declare(strict_types=1);

namespace Shel\MediaFrontend\Service;

/*                                                                        *
 * This script belongs to the Neos package "Shel.MediaFrontend".          *
 *                                                                        *
 * @author Sebastian Helzle <sebastian@helzle.it>                         *
 *                                                                        */

use Doctrine\Common\Persistence\ObjectManager;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Media\Exception\AssetServiceException;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\SystemLoggerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\ResourceManagement\ResourceRepository;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Audio;
use Neos\Media\Domain\Model\Document;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\Video;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * A service for importing files as assets for the media browser
 *
 * @Flow\Scope("singleton")
 */
class ImportAssetService
{

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var SystemLoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var ResourceRepository
     */
    protected $resourceRepository;

    /**
     * @var AssetCollection
     */
    protected $importedAssetCollection = null;

    /**
     * @throws IllegalObjectTypeException
     * @return AssetCollection
     */
    public function getImportedAssetCollection(): AssetCollection
    {
        $collectionName = 'Imported';
        if ($this->importedAssetCollection == null) {
            $this->importedAssetCollection = $this->assetCollectionRepository->findOneByTitle($collectionName);
            if ($this->importedAssetCollection == null) {
                $this->systemLogger->log(sprintf('AssetCollection %s for import missing, creating...',
                  $collectionName));
                $this->importedAssetCollection = new AssetCollection($collectionName);
                $this->assetCollectionRepository->add($this->importedAssetCollection);
            }
        }
        return $this->importedAssetCollection;
    }

    /**
     * @param \SplFileInfo $file
     * @return bool|Asset
     * @throws IllegalObjectTypeException
     */
    public function importAsset(\SplFileInfo $file)
    {
        $filePath = $file->getRealPath();
        $sha1 = sha1_file($filePath);

        /** @var Resource $existingFile */
        $existingFile = $this->resourceRepository->findOneByFilename($file->getFilename());
        if ($existingFile !== null && $existingFile->getSha1() == $sha1) {
            $this->systemLogger->log(sprintf('Asset %s already exists, import cancelled', $filePath));
            return false;
        }

        try {
            $resource = $this->resourceManager->importResource($filePath);
            if ($resource === null) {
                $this->systemLogger->log(sprintf('Failed to import asset %s', $filePath));
                return false;
            }
        } catch (\Exception $e) {
            $this->systemLogger->log(sprintf('Failed to import asset %s with message %s', $filePath, $e->getMessage()));
            return false;
        }

        $mediaType = $resource->getMediaType();
        $mediaMainType = substr($mediaType, 0, 6);

        if ($mediaMainType === 'image/') {
            $asset = new Image($resource);
            $type = 'image';
        } else if ($mediaMainType === 'video/') {
            $asset = new Video($resource);
            $type = 'video';
        } else if ($mediaMainType === 'audio/') {
            $asset = new Audio($resource);
            $type = 'audio';
        } else {
            $asset = new Document($resource);
            $type = 'document';
        }

        $this->assetRepository->add($asset);
        $this->getImportedAssetCollection()->addAsset($asset);
        $this->systemLogger->log(sprintf('Imported asset %s as %s', $filePath, $type));

        return $asset;
    }

    /**
     * @param $path
     * @param bool $simulate
     * @param \Closure $callback
     * @throws IllegalObjectTypeException
     */
    public function importAssetFolder(string $path, $simulate = false, \Closure $callback = null)
    {
        $directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS);
        $filter = new \RecursiveCallbackFilterIterator($directory, function ($current) {
            /** @var \SplFileInfo $current */
            // Skip hidden files and directories.
            if ($current->getFilename()[0] === '.') {
                return false;
            }
            return true;
        });

        $iterator = new \RecursiveIteratorIterator($filter);
        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            $result = false;
            $asset = null;

            if ($file->isDir()) {
                continue;
            }

            if (!$simulate) {
                $asset = $this->importAsset($file);
                if ($asset !== false && $asset !== null) {
                    $result = true;
                }
            } else {
                $result = true;
            }

            if (is_object($callback) && $callback instanceof \Closure) {
                $callback($result, $file, $asset, $simulate);
            }
        }
    }

    /**
     * @param $simulate
     * @param \Closure $callback
     * @throws IllegalObjectTypeException
     * @throws AssetServiceException
     */
    public function removeImportedAssets(bool $simulate, \Closure $callback = null): void
    {
        $assetCollection = $this->getImportedAssetCollection();
        $assets = $assetCollection->getAssets();
        $numberOfAssets = count($assets);
        if (!$simulate) {
            foreach ($assets as $asset) {
                $this->assetRepository->remove($asset);
            }
            $this->assetCollectionRepository->remove($assetCollection);
        }

        if (is_object($callback) && $callback instanceof \Closure) {
            $callback($numberOfAssets, $simulate);
        }
    }

}
