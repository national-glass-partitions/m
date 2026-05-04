<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionLink\Setup\Patch\Data;

use Magento\Framework\App\State;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;
use MageWorx\OptionLink\Model\OptionValueAttributeUpdater\SkuIsValidUpdaterProcess as SkuIsValidUpdaterProcess;

class UpdateSkuIsValidData implements DataPatchInterface, PatchVersionInterface
{
    private State $state;
    private ModuleDataSetupInterface $moduleDataSetup;
    private SkuIsValidUpdaterProcess $skuIsValidUpdaterProcess;

    /**
     * UpdateSkuIsValidData constructor.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param SkuIsValidUpdaterProcess $skuIsValidUpdaterProcess
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SkuIsValidUpdaterProcess $skuIsValidUpdaterProcess,
        State $state
    ) {
        $this->moduleDataSetup          = $moduleDataSetup;
        $this->skuIsValidUpdaterProcess = $skuIsValidUpdaterProcess;
        $this->state                    = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $this->moduleDataSetup->getConnection()->beginTransaction();
        try {
            $this->skuIsValidUpdaterProcess->updateSkuIsValidAttributeDataOnSetup(true);
            $this->moduleDataSetup->getConnection()->commit();
        } catch (\Exception $e) {
            $this->moduleDataSetup->getConnection()->rollback();
            throw($e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion(): string
    {
        return '2.0.2.';
    }
}
