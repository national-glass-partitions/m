<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionDependency\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use MageWorx\OptionBase\Model\ForeignKeyInstallProcess as ForeignKeyInstallProcess;
use Magento\Framework\DB\Ddl\Table;

class ForeignKeyChecker implements ObserverInterface
{
    protected ForeignKeyInstallProcess $foreignKeyInstallProcess;

    /**
     * ForeignKeyChecker constructor.
     *
     * @param ForeignKeyInstallProcess $foreignKeyInstallProcess
     */
    public function __construct(
        ForeignKeyInstallProcess $foreignKeyInstallProcess
    ) {
        $this->foreignKeyInstallProcess = $foreignKeyInstallProcess;
    }

    public function execute(EventObserver $observer)
    {
        $foreignKey = [
            'table_name'            => 'mageworx_option_dependency',
            'column_name'           => 'product_id',
            'reference_table_name'  => 'catalog_product_entity',
            'on_delete'             => Table::ACTION_CASCADE
        ];
        $this->foreignKeyInstallProcess->installProcess($foreignKey);
    }
}
