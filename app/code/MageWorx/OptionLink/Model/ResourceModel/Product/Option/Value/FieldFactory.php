<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value;

use Magento\Framework\ObjectManagerInterface as ObjectManager;

/**
 * Class FieldFactory. Load field object.
 */
class FieldFactory
{
    protected ObjectManager $objectManager;
    protected array $fieldMap;

    public function __construct(
        ObjectManager $objectManager,
        array $fieldMap = []
    ) {
        $this->objectManager = $objectManager;
        $this->fieldMap      = $fieldMap;
    }

    /**
     * Create option field object.
     *
     * @param string $field
     * @param array $arguments
     * @return mixed
     */
    public function create(string $field, array $arguments = []): FieldInterface
    {
        if (isset($this->fieldMap[$field])) {
            $fieldName = $this->fieldMap[$field];
        } else {
            $fieldName = '\MageWorx\OptionLink\Model\ResourceModel\Product\Option\Value\Fields\\' . ucfirst($field);
        }

        $instance = $this->objectManager->create($fieldName, $arguments);

        if (!$instance instanceof FieldInterface) {
            throw new \UnexpectedValueException(
                'Class ' . get_class($instance) . ' should be an instance of ' . FieldInterface::class
            );
        }

        return $instance;
    }
}
