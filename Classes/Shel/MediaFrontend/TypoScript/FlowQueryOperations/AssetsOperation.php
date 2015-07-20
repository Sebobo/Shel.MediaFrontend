<?php
namespace Shel\MediaFrontend\TypoScript\FlowQueryOperations;

/*                                                                        *
 * This script belongs to the Flow package "Shel.MediaFrontend".          *
 *                                                                        *
 *                                                                        */

use Doctrine\DBAL\Query\QueryBuilder;
use TYPO3\Eel\FlowQuery\FlowQueryException;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Persistence\QueryInterface;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\TagRepository;

/**
 * EEL operation to fetch assets
 *
 * Use it like this:
 *
 *    ${q(site).assets('searchword'[, array(ids of tags), string(name of maincollection), array(names of subcollections)])}
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
	 * @var AssetCollectionRepository
	 */
	protected $assetCollectionRepository;

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
		$mainCollection = NULL;
		$subCollections = array();

		if (isset($arguments[0]) && !empty($arguments[0])) {
			$searchWord = $arguments[0];
		}

		// Retrieve main assetcollection from repository by its title
		if (isset($arguments[1]) && $arguments !== '') {
			$mainCollection = $this->assetCollectionRepository->findByTitle($arguments[1])->getFirst();

			if ($mainCollection === NULL) {
				throw new \Exception(sprintf('No AssetCollection with the title %s could be found!', $arguments[1]), 1435854751);
			}
		}

		if (isset($arguments[2]) && !empty($arguments[2])) {
			if (is_array($arguments[2])) {
				$tags = $arguments[2];
			} else {
				$tags = array($arguments[2]);
			}

			$tagRepository = $this->tagRepository;
			$tags = array_map(function ($value) use ($tagRepository) {
				$tag = $tagRepository->findByIdentifier($value);
				return $tag != NULL ? $tag : FALSE;
			}, $tags);
		}

		// Retrieve selected sub collections from repository by their titles
		if (isset($arguments[3]) && !empty($arguments[3])) {
			if (is_array($arguments[3])) {
				$subCollections = $arguments[3];
			} else {
				$subCollections = array($arguments[3]);
			}
			$assetCollectionRepository = $this->assetCollectionRepository;
			$subCollections = array_map(function ($value) use ($assetCollectionRepository) {
				$collection = $assetCollectionRepository->findByTitle($value)->getFirst();
				return $collection !== NULL ? $collection : FALSE;
			}, $subCollections);
		}

		// Retrieve all assets matching the tags and collections
		$assets = $this->findAssets($searchWord, $mainCollection, $tags, $subCollections);

		// Find all tags for the main asset collection or all if no collection is specified
		if ($mainCollection === NULL) {
			$availableTags = $this->tagRepository->findAll();
		} else {
			$availableTags = $mainCollection->getTags();
		}

		$flowQuery->setContext(array(
			'assets' => $assets,
			'tags' => $availableTags,
		));
	}

	/**
	 * Find assets by title or given tags
	 *
	 * @param string $searchTerm
	 * @param AssetCollection $mainCollection
	 * @param array $tags
	 * @param array<AssetCollection> $subCollections
	 * @return \TYPO3\Flow\Persistence\QueryResultInterface
	 */
	protected function findAssets($searchTerm, AssetCollection $mainCollection = NULL, $tags = array(), array $subCollections = array()) {
		/** @var QueryInterface $query */
		$query = $this->assetRepository->createQuery();

		if (!empty($searchTerm)) {
			$query->matching($query->logicalOr(array(
				$query->like('title', '%' . $searchTerm . '%'),
				$query->like('resource.filename', '%' . $searchTerm . '%')
			)));
		}

		// Add given tags to constraints
		if (!empty($tags)) {
			$constraints = array();
			foreach ($tags as $tag) {
				$constraints[] = $query->contains('tags', $tag);
			}
			$query->matching($query->logicalOr($constraints));
		}

		// Add main asset collection to constraints
		if ($mainCollection instanceof AssetCollection) {
			$previousConstraints = $query->getConstraint();
			$query->matching($query->logicalAnd($previousConstraints, $query->contains('assetCollections', $mainCollection)));
		}

		// Add given sub assetcollections to constraints
		if (!empty($subCollections)) {
			$previousConstraints = $query->getConstraint();
			$constraints = array();
			foreach ($subCollections as $collection) {
				$constraints[] = $query->contains('assetCollections', $collection);
			}
			$query->matching($query->logicalAnd($previousConstraints, $query->logicalOr($constraints)));
		}

		// Remove image variants as they are duplicates of normal image assets
		/** @var QueryBuilder $queryBuilder */
		$queryBuilder = $query->getQueryBuilder();
		$queryBuilder->andWhere('e NOT INSTANCE OF TYPO3\Media\Domain\Model\ImageVariant');

		return $query->execute();
	}
}
