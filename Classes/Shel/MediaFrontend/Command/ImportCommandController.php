<?php
namespace Shel\MediaFrontend\Command;

/*                                                                        *
 * This script belongs to the Flow package "Shel.Importer".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Utility\MediaTypes;
use TYPO3\Media\Domain\Model\Image;

/**
 * @Flow\Scope("singleton")
 */
class ImportCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @Flow\Inject
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 */
	protected $entityManager;

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $dbalConnection;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Media\Domain\Repository\AssetRepository
	 */
	protected $assetRepository;

	/**
	 * Import resources to asset management
	 *
	 * This command imports files from the file system and imports them as assets for Neos.
	 * The type of the imported asset is determined by the file extension provided by the
	 * Resource object.
	 *
	 * @param boolean $simulate If set, this command will only tell what it would do instead of doing it right away
	 * @return void
	 */
	public function importResourcesCommand($simulate = FALSE) {
		$this->initializeConnection();

		// TODO: collect files

//		if ($resourceInfos === array()) {
//			$this->outputLine('Found no resources which need to be imported.');
//			$this->quit();
//		}
//
//		foreach ($resourceInfos as $resourceInfo) {
//			$mediaType = $resourceInfo['mediatype'];
//
//			if (substr($mediaType, 0, 6) === 'image/') {
//				$resource = $this->persistenceManager->getObjectByIdentifier($resourceInfo['persistence_object_identifier'], 'TYPO3\Flow\Resource\Resource');
//				if ($resource === NULL) {
//					$this->outputLine('Warning: Resource for file "%s" seems to be corrupt. No resource object with identifier %s could be retrieved from the Persistence Manager.', array($resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
//					continue;
//				}
//				if (!$resource->getStream()) {
//					$this->outputLine('Warning: Resource for file "%s" seems to be corrupt. The actual data of resource %s could not be found in the resource storage.', array($resourceInfo['filename'], $resourceInfo['persistence_object_identifier']));
//					continue;
//				}
//				$image = new Image($resource);
//				if ($simulate) {
//					$this->outputLine('Simulate: Adding new image "%s" (%sx%s px)', array($image->getResource()->getFilename(), $image->getWidth(), $image->getHeight()));
//				} else {
//					$this->assetRepository->add($image);
//					$this->outputLine('Adding new image "%s" (%sx%s px)', array($image->getResource()->getFilename(), $image->getWidth(), $image->getHeight()));
//				}
//			}
//		}
	}

	/**
	 * Initializes the DBAL connection which is currently bound to the Doctrine Entity Manager
	 *
	 * @return void
	 */
	protected function initializeConnection() {
		if (!$this->entityManager instanceof \Doctrine\ORM\EntityManager) {
			$this->outputLine('This command only supports database connections provided by the Doctrine ORM Entity Manager.
				However, the current entity manager is an instance of %s.', array(get_class($this->entityManager)));
			$this->quit(1);
		}

		$this->dbalConnection = $this->entityManager->getConnection();
	}

}