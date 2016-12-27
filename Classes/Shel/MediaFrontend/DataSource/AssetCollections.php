<?php

namespace Shel\MediaFrontend\DataSource;

/*                                                                        *
 * This script belongs to the Flow package "Shel.MediaFrontend".          *
 *                                                                        *
 * @author Sebastian Helzle <sebastian@helzle.it>                         *
 *                                                                        */

use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use TYPO3\Neos\Service\DataSource\AbstractDataSource;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;

class AssetCollections extends AbstractDataSource
{

    /**
     * @var string
     */
    static protected $identifier = 'shel-mediafrontend-assetcollections';

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * Fetch all asset collections
     *
     * @param NodeInterface $node The node that is currently edited (optional)
     * @param array $arguments Additional arguments (key / value)
     * @return mixed JSON serializable data
     * @api
     */
    public function getData(NodeInterface $node = null, array $arguments)
    {
        $assetCollections = $this->assetCollectionRepository->findAll();
        $data = array(
          array(
            'label' => '-',
            'value' => '',
          )
        );

        /** @var AssetCollection $assetCollection */
        foreach ($assetCollections as $assetCollection) {
            $data[] = array(
              'label' => $assetCollection->getTitle(),
              'value' => $assetCollection->getTitle(),
            );
        }

        return $data;
    }
}
