<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionVisibility\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionBase\Model\Schema\SortTableColumnsHandler;
use MageWorx\OptionVisibility\Model\OptionStoreView;
use MageWorx\OptionVisibility\Model\OptionCustomerGroup;


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
            OptionStoreView::TABLE_NAME,
            OptionStoreView::OPTIONTEMPLATES_TABLE_NAME,
            OptionCustomerGroup::TABLE_NAME,
            OptionCustomerGroup::OPTIONTEMPLATES_TABLE_NAME

        ];
        $this->sortTableColumnsHandler->sortTableColumnProcess('MageWorx_OptionVisibility', $tableData);
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
