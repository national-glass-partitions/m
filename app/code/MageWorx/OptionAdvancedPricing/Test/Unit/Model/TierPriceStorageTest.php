<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use MageWorx\OptionAdvancedPricing\Model\TierPriceStorage;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class TierPriceStorageTest extends TestCase
{
    protected $tierPriceStorage;
    protected $resourceMock;
    protected $connectionMock;

    protected function setUp(): void
    {
        $this->resourceMock   = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);

        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceMock->method('getTableName')->willReturn('tier_price_table');

        $this->tierPriceStorage = new TierPriceStorage($this->resourceMock, []);
    }

    public function testGetTierPriceDataReturnsNullForInvalidProductId()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(null);

        $valueMock = $this->createMock(ProductCustomOptionValuesInterface::class);

        $result = $this->tierPriceStorage->getTierPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetTierPriceDataReturnsNullForInvalidOptionId()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(null);

        $result = $this->tierPriceStorage->getTierPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetTierPriceDataReturnsNullForInvalidValueId()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(1);
        $valueMock->method('getOptionTypeId')->willReturn(null);

        $result = $this->tierPriceStorage->getTierPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetTierPriceDataReturnsNullWhenDataIsEmpty()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(1);
        $valueMock->method('getOptionTypeId')->willReturn(1);

        $result = $this->tierPriceStorage->getTierPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetTierPriceDataReturnsTierPrice()
    {
        $data = [
            1 => [
                1 => [
                    'tier_price' => 'tier_price'
                ]
            ]
        ];

        $tierPriceStorage = new TierPriceStorage($this->resourceMock, $data);

        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(1);
        $valueMock->method('getOptionTypeId')->willReturn(1);

        $result = $tierPriceStorage->getTierPriceData($productMock, $valueMock);

        $this->assertEquals('tier_price', $result);
    }

    public function testLoadDataIsCalledWithCorrectProductId()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(1);
        $valueMock->method('getOptionTypeId')->willReturn(1);

        $tierPriceStorage = $this->getMockBuilder(TierPriceStorage::class)
                                 ->setConstructorArgs([$this->resourceMock, []])
                                 ->onlyMethods(['loadData'])
                                 ->getMock();

        $tierPriceStorage->expects($this->once())
                         ->method('loadData')
                         ->with($this->equalTo($productMock));

        $tierPriceStorage->getTierPriceData($productMock, $valueMock);
    }
}
