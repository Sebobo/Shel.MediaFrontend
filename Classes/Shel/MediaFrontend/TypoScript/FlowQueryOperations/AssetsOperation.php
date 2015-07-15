<?php
namespace Shel\MediaFrontend\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the Flow package "Shel.MediaFrontend".          *
 *                                                                        *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQueryException;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\TagRepository;

/**
 * EEL operation to fetch assets
 *
 * Use it like this:
 *
 *    ${q(site).assets('searchword'[, array(tags), AssetCollection])}
 */
class AssetsOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'assets';

	/**
	 * {@inheritdoc}
	 *
	 * @var integer
	 */
	static protected $priority = 100;

	/**
	 * @Flow\Inject
	 * @var AssetRepository
	 */
	protected $assetRepository;

	/**
	 * @Flow\Inject
	 * @var TagRepository
	 */
	protected $tagRepository;

	/**
	 * {@inheritdoc}
	 *
	 * The context doesn't really matter.
	 *
	 * @param mixed $context
	 * @return boolean
	 */
	public function canEvaluate($context) {
		return TRUE;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return mixed
	 */
	public function evaluate(FlowQuery $flowQuery, array $arguments) {
		$searchWord = '';
		$tags = array();
		$assetCollection = NULL;

		if (isset($arguments[0]) && !empty($arguments[0])) {
			$searchWord = $arguments[0];
		}

		if (isset($arguments[1])) {
			if (!is_array($arguments[1])) {
				throw new FlowQueryException('Argument 2 must be empty or an array', 1436931492);
			}
			$tags = $arguments[1];
		}

		if (isset($arguments[2])) {
			if (!$arguments[2] instanceof AssetCollection) {
				throw new FlowQueryException('Argument 3 must be empty or instance of AssetCollection', 1436931483);
			}
			$assetCollection = $arguments[2];
		}

		// Retrieve all assets matching the arguments
		$assets = $this->assetRepository->findBySearchTermOrTags($searchWord, $tags, $assetCollection);

		// Find all tags for the current asset collection or all if no collection is specified
		if ($assetCollection !== NULL) {
			$tags = $this->tagRepository->findByAssetCollections(array($assetCollection));
		} else {
			$tags = $this->tagRepository->findAll();
		}

		$flowQuery->setContext(array(
			'assets' => $assets,
			'tags' => $tags,
		));
	}
}
