<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types = 1);

namespace MageWorx\OptionInventory\Test\Unit\Model;

use Magento\Catalog\Model\Product\Option;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\Store;
use MageWorx\OptionInventory\Model\RefundQty;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\Product\Option\Value as ProductOptionValueModel;
use Magento\Sales\Api\Data\OrderItemInterface;

class RefundQtyTest extends TestCase
{
    protected ObjectManager $objectManager;
    protected RefundQty     $refundQtyModel;

    protected function setUp(): void
    {
        $this->objectManager  = new ObjectManager($this);
        $this->refundQtyModel = $this->getMockBuilder(RefundQty::class)
                                     ->disableOriginalConstructor()
                                     ->onlyMethods(['getOrderItemQtyReturned', 'getValueIds', 'updateValuesQty', 'getValuesCollectionItems'])
                                     ->getMock();
    }

    /**
     * Dummy run test
     * @covers \MageWorx\OptionInventory\Model\RefundQty::refund
     */
    public function testRefundWithEmptyItems()
    {
        $items        = [];
        $qtyFieldName = 'qty_refunded';
        $this->refundQtyModel->expects($this->never())->method('getOrderItemQtyReturned');
        $this->refundQtyModel->expects($this->never())->method('getValueIds');
        $this->refundQtyModel->expects($this->never())->method('updateValuesQty');

        $result = $this->refundQtyModel->refund($items, $qtyFieldName);

        $this->assertInstanceOf(RefundQty::class, $result);
    }

    /**
     * Test refund with item without qty: getValueIds and updateValuesQty should not be called
     *
     * @covers \MageWorx\OptionInventory\Model\RefundQty::refund
     */
    public function testRefundWithItemWithoutQty()
    {
        $item1 = $this->createMock(OrderItemInterface::class);
        $items = [
            $item1
        ];

        $qtyFieldName = 'qty_refunded';
        $this->refundQtyModel->expects($this->once())->method('getOrderItemQtyReturned');
        $this->refundQtyModel->expects($this->never())->method('getValueIds');
        $this->refundQtyModel->expects($this->never())->method('updateValuesQty');

        $result = $this->refundQtyModel->refund($items, $qtyFieldName);

        $this->assertInstanceOf(RefundQty::class, $result);
    }

    /**
     * Test refund with item with dummy qty: getValueIds and updateValuesQty must be called
     *
     * @covers \MageWorx\OptionInventory\Model\RefundQty::refund
     */
    public function testRefundWithItemWithQty()
    {
        $item1 = $this->createMock(OrderItemInterface::class);
        $items = [
            $item1
        ];

        $qtyFieldName = 'qty_refunded';
        $this->refundQtyModel->expects($this->once())->method('getOrderItemQtyReturned')
                             ->with($item1, $qtyFieldName)
                             ->willReturn(5.0);
        $this->refundQtyModel->expects($this->once())->method('getValueIds');
        $this->refundQtyModel->expects($this->once())->method('updateValuesQty');

        $result = $this->refundQtyModel->refund($items, $qtyFieldName);

        $this->assertInstanceOf(RefundQty::class, $result);
    }

    /**
     * Test refund with item with qty and values: getValueIds and updateValuesQty must be called
     * and values must be updated with new qty (112, 1011, 5661)
     * Currently the test throws a "Resource not installed" exception during the element update step, but that's okay
     * because we catch that exception and the entire chain of critical method calls is checked before that.
     *
     * @covers \MageWorx\OptionInventory\Model\RefundQty::refund
     */
    public function testRefundWithItemWithQtyWithValues()
    {
        // Configure items (order items to refund)
        $item1 = $this->getMockBuilder(OrderItemInterface::class)
                      ->disableOriginalConstructor()
                      ->addMethods(['getData', 'getQty'])
                      ->getMockForAbstractClass();
        $item1->method('getQty')->willReturn(5.0);
        $item1->method('getData')->willReturn(
            [
                'qty_invoiced'    => 9.0,
                'qty_refunded'    => 3.0,
                'product_options' => [
                    'info_buyRequest' => [
                        'options'     => [
                            100 => 10,
                            200 => '20,50'
                        ],
                        'options_qty' => null // not used in this test, allows the test to pass through excessive validation
                    ],
                    'options'         => [
                        [
                            'option_id'    => 100,
                            'option_value' => 10,
                            'option_type'  => Option::OPTION_TYPE_DROP_DOWN
                        ],
                        [
                            'option_id'    => 200,
                            'option_value' => '20,30,40,50',
                            'option_type'  => Option::OPTION_TYPE_CHECKBOX
                        ]
                    ]
                ]
            ]
        );

        // Create list of items with options which must be refunded
        $items = [$item1];

        // Configure product option values model mocks
        $valueModel10Mock = $this->getMockBuilder(ProductOptionValueModel::class)
                                 ->disableOriginalConstructor()
                                 ->addMethods(['getManageStock', 'getSkuIsValid', 'getQty', 'getOptionId', 'setQty'])
                                 ->onlyMethods(['getOptionTypeId'])
                                 ->getMock();
        $valueModel10Mock->method('getManageStock')->willReturn(true);
        $valueModel10Mock->method('getSkuIsValid')->willReturn(true);
        $valueModel10Mock->method('getQty')->willReturn(101.0);
        $valueModel10Mock->method('getOptionId')->willReturn(100);
        $valueModel10Mock->method('getOptionTypeId')->willReturn(10);
        $valueModel10Mock->expects($this->once())->method('setQty')->with(112)->willReturnSelf();

        $valueModel20Mock = $this->getMockBuilder(ProductOptionValueModel::class)
                                 ->disableOriginalConstructor()
                                 ->addMethods(['getManageStock', 'getSkuIsValid', 'getQty', 'getOptionId', 'setQty'])
                                 ->onlyMethods(['getOptionTypeId'])
                                 ->getMock();
        $valueModel20Mock->method('getManageStock')->willReturn(true);
        $valueModel20Mock->method('getSkuIsValid')->willReturn(true);
        $valueModel20Mock->method('getQty')->willReturn(1000.0);
        $valueModel20Mock->method('getOptionId')->willReturn(200);
        $valueModel20Mock->method('getOptionTypeId')->willReturn(20);
        $valueModel20Mock->expects($this->once())->method('setQty')->with(1011)->willReturnSelf();

        $valueModel50Mock = $this->getMockBuilder(ProductOptionValueModel::class)
                                 ->disableOriginalConstructor()
                                 ->addMethods(['getManageStock', 'getSkuIsValid', 'getQty', 'getOptionId', 'setQty'])
                                 ->onlyMethods(['getOptionTypeId'])
                                 ->getMock();
        $valueModel50Mock->method('getManageStock')->willReturn(true);
        $valueModel50Mock->method('getSkuIsValid')->willReturn(true);
        $valueModel50Mock->method('getQty')->willReturn(5650.0);
        $valueModel50Mock->method('getOptionId')->willReturn(200);
        $valueModel50Mock->method('getOptionTypeId')->willReturn(50);
        $valueModel50Mock->expects($this->once())->method('setQty')->with(5661)->willReturnSelf();

        // Configure custom option values collection
        $valueCollectionMock =
            $this->objectManager->getCollectionMock(
                \Magento\Catalog\Model\ResourceModel\Product\Option\Value\Collection::class,
                []
            );
        $valueCollectionMock->expects($this->once())->method('addPriceToResult')
                            ->with(Store::DEFAULT_STORE_ID)
                            ->willReturnSelf();
        $valueCollectionMock->expects($this->once())->method('getValuesByOption')
                            ->with([10, 20, 50])
                            ->willReturnSelf();
        $valueCollectionMock->expects($this->once())->method('load')
                            ->willReturnSelf();
        $valueCollectionMock->expects($this->once())->method('getItems')
                            ->willReturn(
                                [
                                    10 => $valueModel10Mock,
                                    20 => $valueModel20Mock,
                                    50 => $valueModel50Mock
                                ]
                            );

        $valueCollectionFactoryMock =
            $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory::class)
                 ->disableOriginalConstructor()
                 ->onlyMethods(['create'])
                 ->getMockForAbstractClass();
        $valueCollectionFactoryMock->method('create')->willReturn($valueCollectionMock);

        // Configure base helper
        $baseHelperMock = $this->getMockBuilder(\MageWorx\OptionBase\Helper\Data::class)
                               ->disableOriginalConstructor()
                               ->onlyMethods(['isSelectableOption'])
                               ->getMock();
        $baseHelperMock->method('isSelectableOption')->willReturnCallback(
            function ($optionType) {
                return in_array(
                    $optionType,
                    [
                        Option::OPTION_TYPE_DROP_DOWN,
                        Option::OPTION_TYPE_RADIO,
                        Option::OPTION_TYPE_CHECKBOX,
                        Option::OPTION_TYPE_MULTIPLE
                    ]
                );
            }
        );

        // Creating RefundQty model
        $refundQtyModel = $this->objectManager->getObject(
            RefundQty::class, [
                                'valueCollection' => $valueCollectionFactoryMock,
                                'stockRegistry'   => $this->createMock(\Magento\CatalogInventory\Api\StockRegistryInterface::class),
                                'helperStock'     => $this->createMock(\MageWorx\OptionInventory\Helper\Stock::class),
                                'baseHelper'      => $baseHelperMock,
                                'logger'          => $this->createMock(\Psr\Log\LoggerInterface::class)
                            ]
        );

        $qtyFieldName = 'qty_refunded';

        $result = $refundQtyModel->refund($items, $qtyFieldName);

        $this->assertInstanceOf(RefundQty::class, $result);
    }
}
