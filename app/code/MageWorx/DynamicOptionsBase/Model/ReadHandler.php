<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model;

use MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Class ReadHandler
 */
class ReadHandler implements ExtensionInterface
{
    protected DynamicOptionRepositoryInterface $repository;

    public function __construct(
        DynamicOptionRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * @param object $entity
     * @param array $arguments
     * @return \Magento\Catalog\Api\Data\ProductInterface|object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $dynamicOptions = $this->repository->getProductDynamicOptionCollection((int)$entity->getEntityId());
        $entity->setMageworxDynamicOptions($dynamicOptions);

        return $entity;
    }
}
