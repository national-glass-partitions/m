<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Api;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

interface DynamicOptionRepositoryInterface
{

    /**
     * Get empty dynamic option
     *
     * @return \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface
     */
    public function getEmptyEntity(): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

    public function save(
        \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface $option
    ): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

    /**
     * Retrieve Dynamic Option.
     *
     * @param int $id
     *
     * @return \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

    /**
     * Delete Dynamic Option
     *
     * @param \MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface $option
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     */
    public function delete(\MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface $option): bool;

    /**
     * Delete Dynamic Option by given Identity
     *
     * @param int $id
     *
     * @return bool
     *
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $id): bool;

    /**
     * Get DynamicOption Collection
     *
     * @param int $productId
     * @param bool $useCache
     * @return \MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption\Collection
     */
    public function getProductDynamicOptionCollection(int $productId, bool $useCache = true);

    /**
     * Get DynamicOption Ids
     *
     * @param int $productId
     * @param bool $useCache
     * @return \MageWorx\DynamicOptionsBase\Model\ResourceModel\DynamicOption\Collection
     */
    public function getProductDynamicOptionIds(int $productId, bool $useCache = true);
}
