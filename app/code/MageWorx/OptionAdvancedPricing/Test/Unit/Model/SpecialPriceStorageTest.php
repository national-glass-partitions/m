<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageWorx\OptionAdvancedPricing\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionValuesInterface;
use MageWorx\OptionAdvancedPricing\Model\SpecialPriceStorage;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class SpecialPriceStorageTest extends TestCase
{
    protected $specialPriceStorage;
    protected $resourceMock;
    protected $connectionMock;

    protected function setUp(): void
    {
        $this->resourceMock   = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);

        $this->resourceMock->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceMock->method('getTableName')->willReturn('special_price_table');

        $this->specialPriceStorage = new SpecialPriceStorage($this->resourceMock, []);
    }

    public function testGetSpecialPriceDataReturnsNullForInvalidProductId()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(null);

        $valueMock = $this->createMock(ProductCustomOptionValuesInterface::class);

        $result = $this->specialPriceStorage->getSpecialPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetSpecialPriceDataReturnsNullForInvalidOptionId()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(null);

        $result = $this->specialPriceStorage->getSpecialPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetSpecialPriceDataReturnsNullForInvalidValueId()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(1);
        $valueMock->method('getOptionTypeId')->willReturn(null);

        $result = $this->specialPriceStorage->getSpecialPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetSpecialPriceDataReturnsNullWhenDataIsEmpty()
    {
        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(1);
        $valueMock->method('getOptionTypeId')->willReturn(1);

        $result = $this->specialPriceStorage->getSpecialPriceData($productMock, $valueMock);

        $this->assertNull($result);
    }

    public function testGetSpecialPriceDataReturnsSpecialPrice()
    {
        $data = [
            1 => [
                1 => [
                    'special_price' => 'special_price'
                ]
            ]
        ];

        $specialPriceStorage = new SpecialPriceStorage($this->resourceMock, $data);

        $productMock = $this->createMock(ProductInterface::class);
        $productMock->method('getId')->willReturn(1);

        $valueMock = $this->getMockBuilder(ProductCustomOptionValuesInterface::class)
                          ->onlyMethods(['getOptionTypeId'])
                          ->addMethods(['getOptionId'])
                          ->getMockForAbstractClass();

        $valueMock->method('getOptionId')->willReturn(1);
        $valueMock->method('getOptionTypeId')->willReturn(1);

        $result = $specialPriceStorage->getSpecialPriceData($productMock, $valueMock);

        $this->assertEquals('special_price', $result);
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

        $specialPriceStorage = $this->getMockBuilder(SpecialPriceStorage::class)
                                    ->setConstructorArgs([$this->resourceMock, []])
                                    ->onlyMethods(['loadData'])
                                    ->getMock();

        $specialPriceStorage->expects($this->once())
                            ->method('loadData')
                            ->with($this->equalTo($productMock));

        $specialPriceStorage->getSpecialPriceData($productMock, $valueMock);
    }
}
