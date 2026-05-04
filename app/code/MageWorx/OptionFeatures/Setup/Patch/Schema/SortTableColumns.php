<?php
/**
 * Copyright ©  MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionFeatures\Setup\Patch\Schema;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MageWorx\OptionBase\Model\Schema\SortTableColumnsHandler;
use MageWorx\OptionFeatures\Model\OptionDescription;
use MageWorx\OptionFeatures\Model\OptionTypeDescription;
use MageWorx\OptionFeatures\Model\OptionTypeIsDefault;
use MageWorx\OptionFeatures\Model\Image;


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
            OptionDescription::TABLE_NAME,
            OptionDescription::OPTIONTEMPLATES_TABLE_NAME,
            OptionTypeDescription::TABLE_NAME,
            OptionTypeDescription::OPTIONTEMPLATES_TABLE_NAME,
            OptionTypeIsDefault::TABLE_NAME,
            OptionTypeIsDefault::OPTIONTEMPLATES_TABLE_NAME,
            Image::TABLE_NAME,
            Image::OPTIONTEMPLATES_TABLE_NAME,
        ];
        $this->sortTableColumnsHandler->sortTableColumnProcess('MageWorx_OptionFeatures', $tableData);
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
