<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model;

use MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface;
use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;

/**
 * Class SaveHandler
 */
class SaveHandler implements ExtensionInterface
{
    protected DynamicOptionRepositoryInterface $repository;

    public function __construct(
        DynamicOptionRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Perform action on relation/extension attribute
     *
     * @param object $entity
     * @param array $arguments
     * @return \Magento\Catalog\Api\Data\ProductInterface|object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        $dynamicOptionsData = $entity->getMageworxDynamicOptionsData();
        $dynamicOptions     = $entity->getMageworxDynamicOptions();
        $this->repository->deleteByProductId((int)$entity->getEntityId());

        if ($dynamicOptions) {
            foreach ($dynamicOptions as $key => $dynamicOption) {
                if (!$entity->getOptionById($dynamicOption)) {
                    continue;
                }

                if (array_search(
                    $entity->getOptionById($dynamicOption)->getType(),
                    DynamicOptionInterface::COMPATIBLE_TYPES) === false) {
                    continue;
                }

                if (is_string($dynamicOption) && array_key_exists($key, $dynamicOptionsData)) {
                    $option = $this->repository->getEmptyEntity();
                    $option->setData($dynamicOptionsData[$key]);
                    $option->setProductId((int)$entity->getEntityId());
                    $option->setOptionId((int)$dynamicOption);
                } else {
                    $option = $dynamicOption;
                }

                if ($option instanceof DynamicOptionInterface) {
                    $this->repository->save($option);
                }
            }
        }

        return $entity;
    }
}
