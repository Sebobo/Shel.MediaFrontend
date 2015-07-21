<?php
namespace Shel\MediaFrontend\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Shel.MediaFrontend"     *
 *                                                                        */

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\SystemLoggerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Flow\Resource\ResourceRepository;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Model\Audio;
use TYPO3\Media\Domain\Model\Document;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\Video;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\Domain\Repository\AssetRepository;

/**
 * A service for importing files as assets for the media browser
 *
 * @Flow\Scope("singleton")
 */
class ImportAssetService {

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
	 * @var Connection
	 */
	protected $dbalConnection;

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
	protected $importedAssetCollection = NULL;

	/**
	 * @throws \TYPO3\Flow\Persistence\Exception\IllegalObjectTypeException
	 * @return AssetCollection
	 */
	public function getImportedAssetCollection() {
		$collectionName = 'Imported';
		if ($this->importedAssetCollection == NULL) {
			$this->importedAssetCollection = $this->assetCollectionRepository->findByTitle($collectionName)->getFirst();
			if ($this->importedAssetCollection == NULL) {
				$this->systemLogger->log(sprintf('AssetCollection %s for import missing, creating...', $collectionName));
				$this->importedAssetCollection = new AssetCollection($collectionName);
				$this->assetCollectionRepository->add($this->importedAssetCollection);
			}
		}
		return $this->importedAssetCollection;
	}

	/**
	 * @param \SplFileInfo $file
	 * @throws \TYPO3\Flow\Resource\Exception
	 * @internal param \SplFileInfo $path
	 * @return bool|Asset
	 */
	public function importAsset(\SplFileInfo $file) {
		$filePath = $file->getRealPath();
		$sha1 = sha1_file($filePath);

		/** @var Resource $existingFile */
		$existingFile = $this->resourceRepository->findByFilename($file->getFilename())->getFirst();
		if ($existingFile !== NULL && $existingFile->getSha1() == $sha1) {
			$this->systemLogger->log(sprintf('Asset %s already exists, import cancelled', $filePath));
			return FALSE;
		}

		$resource = $this->resourceManager->importResource($filePath);
		if ($resource === NULL) {
			$this->systemLogger->log(sprintf('Failed to import asset %s', $filePath));
			return FALSE;
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
	 */
	public function importAssetFolder($path, $simulate = FALSE, \Closure $callback = NULL) {
		$directory = new \RecursiveDirectoryIterator($path, \FilesystemIterator::FOLLOW_SYMLINKS);
		$filter = new \RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
			/** @var \SplFileInfo $current */
			// Skip hidden files and directories.
			if ($current->getFilename()[0] === '.') {
				return FALSE;
			}
			return TRUE;
		});

		$iterator = new \RecursiveIteratorIterator($filter);
		/** @var \SplFileInfo $file */
		foreach ($iterator as $file) {
			$result = FALSE;
			$asset = NULL;

			if ($file->isDir()) {
				continue;
			}

			if (!$simulate) {
				$asset = $this->importAsset($file);
				if ($asset !== FALSE && $asset !== NULL) {
					$result = TRUE;
				}
			} else {
				$result = TRUE;
			}

			if (is_object($callback) && $callback instanceof \Closure) {
				$callback($result, $file, $asset, $simulate);
			}
		}
	}

	/**
	 * @param $simulate
	 * @param \Closure|NULL $callback
	 */
	public function removeImportedAssets($simulate, \Closure $callback = NULL) {
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
