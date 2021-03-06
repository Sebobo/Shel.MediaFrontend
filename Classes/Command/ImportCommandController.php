<?php
declare(strict_types=1);

namespace Shel\MediaFrontend\Command;

/*                                                                        *
 * This script belongs to the Neos package "Shel.MediaFrontend".          *
 *                                                                        *
 * @author Sebastian Helzle <sebastian@helzle.it>                         *
 *                                                                        */

use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Media\Exception\AssetServiceException;
use Shel\MediaFrontend\Service\ImportAssetService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Media\Domain\Model\Asset;

/**
 * @Flow\Scope("singleton")
 */
class ImportCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var ImportAssetService
     */
    protected $importAssetService;

    /**
     * Import files from a folder as resources to the asset management
     *
     * This command imports files from the file system and imports them as assets for Neos.
     * The type of the imported asset is determined by the file extension provided by the
     * Resource object.
     *
     * @param string $path The folder which the files should be read from
     * @param boolean $simulate If set, this command will only tell what it would do instead of doing it right away
     * @throws IllegalObjectTypeException
     */
    public function filesCommand(string $path, bool $simulate = false): void
    {
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
     * Completely removes previously imported resources from asset management
     *
     * @param boolean $simulate
     * @throws IllegalObjectTypeException
     * @throws AssetServiceException
     */
    public function purgeCommand(bool $simulate = false): void
    {
        $this->importAssetService->removeImportedAssets($simulate, function ($assetsRemoved, $simulate) {
            if ($simulate) {
                $this->outputFormatted("Would have removed %d assets", array($assetsRemoved));
            } else {
                $this->outputFormatted("Removed %d assets", array($assetsRemoved));
            }
        });
    }

}
