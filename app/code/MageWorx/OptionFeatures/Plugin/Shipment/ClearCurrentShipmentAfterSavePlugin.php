<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionFeatures\Plugin\Shipment;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Sales\Model\Order\Shipment;

class ClearCurrentShipmentAfterSavePlugin
{
    protected DataPersistorInterface $dataPersistor;

    public function __construct(DataPersistorInterface $dataPersistor)
    {
        $this->dataPersistor = $dataPersistor;
    }

    public function afterAfterSave(Shipment $subject, Shipment $result): Shipment
    {
        $this->dataPersistor->clear(RegisterCurrentShipmentAfterSavePlugin::SHIPMENT_KEY);

        return $result;
    }
}
