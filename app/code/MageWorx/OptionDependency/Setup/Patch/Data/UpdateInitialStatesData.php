<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use MageWorx\OptionDependency\Model\InitialStatesProcess as InitialStatesModel;

class UpdateInitialStatesData implements DataPatchInterface, PatchVersionInterface
{
    protected InitialStatesModel $initialStates;
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * UpdateInitialStatesData constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param InitialStatesModel $initialStates
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        InitialStatesModel $initialStates
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->initialStates   = $initialStates;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->beginTransaction();
        try {
            $this->initialStates->processDependencyRulesUpdate();
            $this->initialStates->processPreselectedValuesUpdate();
            $this->moduleDataSetup->getConnection()->commit();
        } catch (\Exception $e) {
            $this->moduleDataSetup->getConnection()->rollback();
            throw($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.10';
    }
}
