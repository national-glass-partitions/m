<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionBase\Plugin;

use Magento\Catalog\Api\Data\CustomOptionInterface;
use Magento\Catalog\Model\Product;
use MageWorx\OptionBase\Api\ValidatorInterface;
use MageWorx\OptionBase\Model\ResourceModel\DataSaver;
use MageWorx\OptionBase\Model\ValidationResolver;
use Magento\Catalog\Model\Product\Option\ValueFactory as OptionValueFactory;

class IsRequireChecker
{
    protected DataSaver $dataSaver;
    protected ValidationResolver $validationResolver;
    protected OptionValueFactory $optionValueFactory;

    /**
     * IsRequireChecker constructor.
     *
     * @param ValidationResolver $validationResolver
     * @param OptionValueFactory $optionValueFactory
     * @param DataSaver $dataSaver
     */
    public function __construct(
        ValidationResolver $validationResolver,
        OptionValueFactory $optionValueFactory,
        DataSaver $dataSaver
    ) {
        $this->validationResolver = $validationResolver;
        $this->optionValueFactory = $optionValueFactory;
        $this->dataSaver          = $dataSaver;
    }

    /**
     * @param Product $subject
     * @param Product $product
     * @return mixed
     */
    public function afterAfterSave(
        Product $subject,
        $product
    ) {
        $options          = $product->getOptions();
        $isRequireOptions = false;

        if (!$options) {
            $this->dataSaver->updateValueIsRequire($product->getId(), (int)$isRequireOptions);
            return $product;
        }

        /* @var CustomOptionInterface $option */
        foreach ($options as $option) {
            if (!$option->getIsRequire()) {
                continue;
            }
            //prepare data
            if (is_null($option->getValues()) && is_array($option->getData('values'))) {
                $optionValues = [];
                foreach ($option->getData('values') as $valueDatum) {
                    $optionValues[] = $this->optionValueFactory->create()->setData($valueDatum);
                }
                $option->setValues($optionValues);
            }
            $optionRequireStatus = true;
            /* @var ValidatorInterface $validatorItem */
            foreach ($this->validationResolver->getValidators() as $key => $validatorItem) {
                if (!$validatorItem->canValidateCartCheckout($product, $option)) {
                    $optionRequireStatus = false;
                    break;
                }
            }

            if ($optionRequireStatus) {
                $isRequireOptions = true;
                break;
            }
        }

        $this->dataSaver->updateValueIsRequire($product->getId(), (int)$isRequireOptions);

        return $product;
    }
}
