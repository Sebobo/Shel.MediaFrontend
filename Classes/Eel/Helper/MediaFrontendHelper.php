<?php
declare(strict_types=1);

namespace Shel\MediaFrontend\Eel\Helper;

/*                                                                        *
 * This script belongs to the Neos package "Shel.MediaFrontend".          *
 *                                                                        *
 * @author Sebastian Helzle <sebastian@helzle.it>                         *
 *                                                                        */

use Doctrine\Common\Collections\Collection;
use Neos\Flow\Annotations as Flow;
use Neos\Eel\ProtectedContextAwareInterface;

/**
 * Media Frontend helpers for Eel contexts
 *
 * @Flow\Proxy(false)
 */
class MediaFrontendHelper implements ProtectedContextAwareInterface
{

    /**
     * Intersect two asset collection arrays
     *
     * @param array $availableCollections The available collection titles as strings
     * @param Collection $assetCollections The assets selected collections as AssetCollections
     * @return \Traversable The array with intersected AssetCollections
     */
    public function intersectAssetCollections($availableCollections, Collection $assetCollections): \Traversable
    {
        return $assetCollections->filter(function ($collection) use ($availableCollections) {
            return in_array($collection->getTitle(), $availableCollections);
        });
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }

}
