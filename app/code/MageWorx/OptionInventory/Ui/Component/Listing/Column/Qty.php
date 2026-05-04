<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\OptionInventory\Ui\Component\Listing\Column;

use MageWorx\OptionInventory\Helper\Stock as HelperStock;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use \Magento\Backend\Helper\Data as BackendHelper;

/**
 * Class Qty
 *
 * @package MageWorx\OptionInventory\Ui\Component\Listing\Column
 */
class Qty extends Column
{
    // TODO unused ?
    protected BackendHelper $backendHelper;
    protected HelperStock $helperStock;

    /**
     * Qty constructor.
     *
     * @param HelperStock $helperStock
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param BackendHelper $backendHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        HelperStock $helperStock,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        BackendHelper $backendHelper,
        array $components = [],
        array $data = []
    ) {
        $this->helperStock   = $helperStock;
        $this->backendHelper = $backendHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $qty       = $item['qty'] ?? null;
                $productId = $item['product_id'] ?? null;
                if (!$qty || !$productId) {
                    continue;
                }
                $formattedQty = $this->helperStock->isfloatingQty((int)$productId) ? (float)$qty : (int)$qty;

                $item[$this->getData('name')] = $formattedQty;
            }
        }

        return $dataSource;
    }
}
