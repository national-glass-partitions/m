<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionDependency\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionBase\Model\Schema\SortTableColumnsHandler;
use MageWorx\OptionDependency\Model\Config;

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
            Config::TABLE_NAME,
            Config::OPTIONTEMPLATES_TABLE_NAME
        ];
        $this->sortTableColumnsHandler->sortTableColumnProcess('MageWorx_OptionDependency', $tableData);
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
