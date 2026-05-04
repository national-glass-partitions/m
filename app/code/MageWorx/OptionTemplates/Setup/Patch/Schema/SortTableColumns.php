<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionTemplates\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionBase\Model\Schema\SortTableColumnsHandler;
use MageWorx\OptionTemplates\Helper\Data;


class SortTableColumns implements DataPatchInterface
{
    private SortTableColumnsHandler $sortTableColumnsHandler;

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
            Data::TABLE_NAME_GROUP,
            Data::TABLE_NAME_GROUP_OPTION,
            Data::TABLE_NAME_RELATION,
            Data::TABLE_NAME_GROUP_OPTION_PRICE,
            Data::TABLE_NAME_GROUP_OPTION_TITLE,
            Data::TABLE_NAME_GROUP_OPTION_TYPE_VALUE,
            Data::TABLE_NAME_GROUP_OPTION_TYPE_PRICE,
            Data::TABLE_NAME_GROUP_OPTION_TYPE_TITLE

        ];
        $this->sortTableColumnsHandler->sortTableColumnProcess('MageWorx_OptionTemplates', $tableData);
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
