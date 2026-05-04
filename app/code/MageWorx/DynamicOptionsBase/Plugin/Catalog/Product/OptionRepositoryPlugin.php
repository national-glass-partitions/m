<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\DynamicOptionsBase\Plugin\Catalog\Product;

use Magento\Framework\Exception\NoSuchEntityException;
use MageWorx\DynamicOptionsBase\Api\Data\DynamicOptionInterface;

class OptionRepositoryPlugin
{
    protected \MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface $dynamicOptionRepository;

    /**
     * OptionRepositoryPlugin constructor.
     *
     * @param \MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface $dynamicOptionRepository
     */
    public function __construct(
        \MageWorx\DynamicOptionsBase\Api\DynamicOptionRepositoryInterface $dynamicOptionRepository
    ) {
        $this->dynamicOptionRepository = $dynamicOptionRepository;
    }

    /**
     * @param \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject
     * @param $result
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterSave(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        $result,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
    ) {
        if (array_search($option->getType(), DynamicOptionInterface::COMPATIBLE_TYPES) === false) {
            try {
                $dynamicOption = $this->dynamicOptionRepository->getById($option->getOptionId());
            } catch (NoSuchEntityException $e) {
                return $result;
            }

            $this->dynamicOptionRepository->delete($dynamicOption);
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject
     * @param $result
     * @param \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterDelete(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        $result,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
    ) {
        try {
            $dynamicOption = $this->dynamicOptionRepository->getById($option->getOptionId());
        } catch (NoSuchEntityException $e) {
            return $result;
        }

        $this->dynamicOptionRepository->delete($dynamicOption);

        return $result;
    }
}
