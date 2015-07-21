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

use Shel\MediaFrontend\Service\ImportAssetService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Media\Domain\Model\Asset;

/**
 * @Flow\Scope("singleton")
 */
class ImportCommandController extends CommandController {

	/**
	 * @Flow\Inject
	 * @var ImportAssetService
	 */
	protected $importAssetService;

	/**
	 * Import resources to asset management
	 *
	 * This command imports files from the file system and imports them as assets for Neos.
	 * The type of the imported asset is determined by the file extension provided by the
	 * Resource object.
	 *
	 * @param string $path The folder which the files should be read from
	 * @param boolean $simulate If set, this command will only tell what it would do instead of doing it right away
	 */
	public function assetsCommand($path, $simulate = FALSE) {
		$path = realpath($path);
		$this->outputFormatted("Importing assets from %s", array($path));

		$this->importAssetService->importAssetFolder($path, $simulate, function ($status, $file, $asset, $simulate) {
			/** @var Asset $asset */
			/** @var \SplFileInfo $file */
			$filename = $file->getFilename();
			if ($simulate) {
				$this->outputFormatted("* Would import asset %s", array($filename));
			} else if (!$status) {
				$this->outputFormatted("* Failed importing asset %s, check the log", array($filename));
			} else {
				$mediaType = $asset->getMediaType();
				$this->outputFormatted("* Imported asset %s of type %s", array($filename, $mediaType));
			}
		});
	}

	/**
	 * @param boolean $simulate
	 */
	public function undoCommand($simulate = FALSE) {
		$this->importAssetService->removeImportedAssets($simulate, function ($assetsRemoved, $simulate) {
			if ($simulate) {
				$this->outputFormatted("Would have removed %d assets", array($assetsRemoved));
			} else {
				$this->outputFormatted("Removed %d assets", array($assetsRemoved));
			}
		});
	}

}
