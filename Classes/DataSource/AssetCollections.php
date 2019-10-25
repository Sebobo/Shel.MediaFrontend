<?php
declare(strict_types=1);

namespace Shel\MediaFrontend\DataSource;

/*                                                                        *
 * This script belongs to the Neos package "Shel.MediaFrontend".          *
 *                                                                        *
 * @author Sebastian Helzle <sebastian@helzle.it>                         *
 *                                                                        */

use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;
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
     * @return array JSON serializable data
     * @api
     */
    public function getData(NodeInterface $node = null, array $arguments = []): array
    {
        $assetCollections = $this->assetCollectionRepository->findAll();
        $data = [
          [
            'label' => '-',
            'value' => '',
          ]
        ];

        /** @var AssetCollection $assetCollection */
        foreach ($assetCollections as $assetCollection) {
            $data[] = [
              'label' => $assetCollection->getTitle(),
              'value' => $assetCollection->getTitle(),
            ];
        }

        return $data;
    }
}
