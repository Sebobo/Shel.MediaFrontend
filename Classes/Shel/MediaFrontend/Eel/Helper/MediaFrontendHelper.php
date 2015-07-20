<?php
namespace Shel\MediaFrontend\Eel\Helper;

/*                                                                        *
 * This script belongs to the Flow package "Shel.MediaFrontend".          *
 *                                                                        */

use Doctrine\Common\Collections\Collection;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\ProtectedContextAwareInterface;

/**
 * Media Frontend helpers for Eel contexts
 *
 * @Flow\Proxy(false)
 */
class MediaFrontendHelper implements ProtectedContextAwareInterface {

	/**
	 * Intersect two asset collection arrays
	 *
	 * @param array $availableCollections The available collection titles as strings
	 * @param Collection $assetCollections The assets selected collections as AssetCollections
	 * @return array The array with intersected AssetCollections
	 */
	public function intersectAssetCollections($availableCollections, Collection $assetCollections) {
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
	public function allowsCallOfMethod($methodName) {
		return TRUE;
	}

}
