<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See https://www.mageworx.com/terms-and-conditions for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionFeatures\Plugin\Shipment;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Sales\Model\Order\Shipment;

class RegisterCurrentShipmentAfterSavePlugin
{
    const SHIPMENT_KEY = 'mageworx_optionfeatures_current_shipment_after_save';

    protected DataPersistorInterface $dataPersistor;

    public function __construct(DataPersistorInterface $dataPersistor)
    {
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * @param Shipment $subject
     * @return null
     */
    public function beforeAfterSave(Shipment $subject)
    {
        $this->dataPersistor->set(self::SHIPMENT_KEY, $subject);

        return null;
    }
}
