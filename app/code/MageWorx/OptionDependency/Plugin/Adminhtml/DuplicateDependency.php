<?php

/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Plugin\Adminhtml;

use MageWorx\OptionBase\Helper\Data as OptionBaseHelper;
use MageWorx\OptionDependency\Model\Attribute\OptionValue\Dependency as DependencyAttribute;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Registry;
use Magento\Framework\App\ResourceConnection;

class DuplicateDependency
{
    protected OptionBaseHelper $helper;
    protected HttpRequest $request;
    protected Registry $registry;
    protected ResourceConnection $resource;
    protected \Magento\Framework\DB\Adapter\AdapterInterface $connection;
    protected DependencyAttribute $dependencyAttribute;
    protected bool $isGroup;

    /**
     * @param OptionBaseHelper $helper
     * @param HttpRequest $request
     * @param Registry $registry
     * @param ResourceConnection $resource
     * @param DependencyAttribute $dependencyAttribute
     */
    public function __construct(
        OptionBaseHelper $helper,
        HttpRequest $request,
        Registry $registry,
        DependencyAttribute $dependencyAttribute,
        ResourceConnection $resource
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->registry = $registry;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->dependencyAttribute = $dependencyAttribute;
    }

    /**
     * Process dependency copying
     *
     * @param \Magento\Catalog\Model\Product\Copier|\MageWorx\OptionTemplates\Model\Group\Copier $subject
     * @param \Closure $proceed
     * @param \MageWorx\OptionTemplates\Model\Group|\Magento\Catalog\Model\Product $entity
     * @return \MageWorx\OptionTemplates\Model\Group|\Magento\Catalog\Model\Product
     */
    public function aroundCopy($subject, \Closure $proceed, $entity)
    {
        $result = $proceed($entity);

        $this->isGroup = $entity instanceof \MageWorx\OptionTemplates\Model\Group;

        if ($this->out()) {
            return $result;
        }

        if ($this->isGroup) {
            $oldEntityId = $entity->getGroupId();
            $newEntityId = $result->getGroupId();
        } else {
            $oldEntityId = $this->helper->isEnterprise() ? $entity->getRowId() : $entity->getEntityId();
            $newEntityId = $this->helper->isEnterprise() ? $result->getRowId() : $result->getEntityId();
        }

        $mapOptionId = $this->registry->registry('mapOptionId');
        $mapMOptionTypeId = $this->registry->registry('mapOptionTypeId');

        $this->clearRegistryData();

        $dependency = $this->getDependency($oldEntityId);

        if (!$dependency) {
            return $result;
        }

        $dependency = $this->updateDependency(
            $dependency,
            $newEntityId,
            $mapOptionId,
            $mapMOptionTypeId
        );

        $this->saveDependency($dependency);

        return $result;
    }

    /**
     * Clear the registered data to free memory.
     *
     * @return $this
     */
    protected function clearRegistryData()
    {
        $this->registry->unregister('mapOptionId');
        $this->registry->unregister('mapOptionTypeId');

        return $this;
    }

    /**
     * Get dependencies from the duplicated product/group.
     *
     * @param int $entityId
     * @return array
     */
    protected function getDependency($entityId)
    {
        $sql = $this->connection->select()
            ->from(
                $this->resource->getTableName($this->dependencyAttribute->getTableName($this->getEntityTypeName())),
                [
                    'dp_child_option_id',
                    'dp_child_option_type_id',
                    'dp_parent_option_id',
                    'dp_parent_option_type_id',
                    $this->getEntityFieldName()
                ]
            )
            ->where( $this->getEntityFieldName() . ' = ?', $entityId);

        return $this->connection->fetchAll($sql);
    }

    /**
     * Update old dependencies with new mageworx_id.
     *
     * @param array $dependency
     * @param int $newEntityId
     * @param array $mapMageworxOptionId
     * @param array $mapMageworxOptionTypeId
     * @return array
     */
    protected function updateDependency($dependency, $newEntityId, $mapOptionId, $mapOptionTypeId)
    {
        foreach ($dependency as $id => $row) {
            $dependency[$id]['dp_child_option_id'] = $mapOptionId[$row['dp_child_option_id']];
            $dependency[$id]['dp_parent_option_id'] = $mapOptionId[$row['dp_parent_option_id']];

            if (empty($mapOptionTypeId[$row['dp_child_option_type_id']])) {
                $mapOptionTypeId[$row['dp_child_option_type_id']] = "";
            }

            $dependency[$id]['dp_child_option_type_id'] = $mapOptionTypeId[$row['dp_child_option_type_id']];
            $dependency[$id]['dp_parent_option_type_id'] = $mapOptionTypeId[$row['dp_parent_option_type_id']];
            $dependency[$id][$this->getEntityFieldName()] = $newEntityId;
        }

        return $dependency;
    }

    /**
     * Save duplicated dependencies to the database.
     *
     * @param array $dependency
     * @return void
     */
    protected function saveDependency($dependency)
    {
        $table = $this->resource->getTableName($this->dependencyAttribute->getTableName($this->getEntityTypeName()));
        $this->connection->insertMultiple($table, $dependency);
    }

    /**
     * Check conditions to skip further processing
     *
     * @return bool
     */
    protected function out()
    {
        if (!$this->connection->isTableExists(
            $this->resource->getTableName($this->dependencyAttribute->getTableName($this->getEntityTypeName())))
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get entity type name
     *
     * @return string
     */
    protected function getEntityTypeName()
    {
        return $this->isGroup ? 'group' : 'product';
    }

    /**
     * Get entity field name
     *
     * @return string
     */
    protected function getEntityFieldName()
    {
        return $this->isGroup ? 'group_id' : 'product_id';
    }
}
