<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionBase\Model\Schema\SortTableColumnsHandler;
use MageWorx\OptionAdvancedPricing\Model\TierPrice;
use MageWorx\OptionAdvancedPricing\Model\SpecialPrice;

class SortTableColumns implements DataPatchInterface
{
    protected SortTableColumnsHandler $sortTableColumnsHandler;

    /**
     * SortTableColumns constructor.
     *
     * @param SortTableColumnsHandler $sortTableColumnsHandler
     */
    public function __construct(
        SortTableColumnsHandler $sortTableColumnsHandler
    ) {
        $this->sortTableColumnsHandler  = $sortTableColumnsHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): void
    {
        $tableData = [
            TierPrice::TABLE_NAME,
            TierPrice::OPTIONTEMPLATES_TABLE_NAME,
            SpecialPrice::TABLE_NAME,
            SpecialPrice::OPTIONTEMPLATES_TABLE_NAME
        ];
        $this->sortTableColumnsHandler->sortTableColumnProcess('MageWorx_OptionAdvancedPricing', $tableData);
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
}
