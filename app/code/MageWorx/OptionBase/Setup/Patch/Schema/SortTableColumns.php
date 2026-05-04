<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionBase\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionBase\Model\Schema\SortTableColumnsHandler;
use MageWorx\OptionBase\Model\ProductAttributes;

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
        $tableData = [ProductAttributes::TABLE_NAME];
        $this->sortTableColumnsHandler->sortTableColumnProcess('MageWorx_OptionBase', $tableData);
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
