<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionDependency\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionDependency\Model\InitialStatesProcess as InitialStatesModel;

/**
 * The patch makes the "mageworx_optionbase_product_attributes" table structure correct due to a change in the dependency rule logic
 */
class UpdatingDependencyRuleAccordingToNewLogicPatch implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private InitialStatesModel $initialStates;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param InitialStatesModel $initialStates
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        InitialStatesModel $initialStates
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->initialStates = $initialStates;
    }

    /**
     * Do Upgrade.
     *
     * @return void
     * @throws \Exception
     */
    public function apply()
    {
        $this->updateMageworxOptionbaseProductAttributesTable();
    }

    /**
     * @inheirtdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheirtdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Re-create "mageworx_optionbase_product_attributes" records in the table based on records from "mageworx_option_dependency"
     * due to changes in the module logic
     * @return void
     * @throws \Exception
     */
    protected function updateMageworxOptionbaseProductAttributesTable(): void
    {
        $this->moduleDataSetup->getConnection()->beginTransaction();
        try {
            $this->initialStates->processDependencyRulesUpdate();
            $this->moduleDataSetup->getConnection()->commit();
        } catch (\Exception $e) {
            $this->moduleDataSetup->getConnection()->rollback();
            throw($e);
        }
    }
}
