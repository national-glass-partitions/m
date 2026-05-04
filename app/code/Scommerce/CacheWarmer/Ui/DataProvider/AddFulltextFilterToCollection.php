<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Scommerce\CacheWarmer\Ui\DataProvider;

use Magento\Framework\Data\Collection;
use Scommerce\CacheWarmer\Model\ResourceModel\CacheWarmer\Collection as SearchCollection;
use Magento\Ui\DataProvider\AddFilterToCollectionInterface;

/**
 * Class AddFulltextFilterToCollection
 */
class AddFulltextFilterToCollection implements AddFilterToCollectionInterface
{
    /**
     * Search Collection
     *
     * @var SearchCollection
     */
    private $searchCollection;

    /**
     * @param SearchCollection $searchCollection
     */
    public function __construct(SearchCollection $searchCollection)
    {
        $this->searchCollection = $searchCollection;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function addFilter(Collection $collection, $field, $condition = null)
    {
        /** @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        if (isset($condition['fulltext']) && !empty($condition['fulltext'])) {
            $this->searchCollection->addBackendSearchFilter($condition['fulltext']);
            $productIds = $this->searchCollection->load()->getAllIds();
            $collection->addIdFilter($productIds);
        }
    }
}