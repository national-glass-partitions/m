<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\DynamicOptionsBase\Model\Source;

use MageWorx\DynamicOptionsBase\Model\Source;

class MeasurementUnits extends Source
{
    const METER      = 'meters';
    const CENTIMETER = 'centimeters';
    const FOOT       = 'feet';
    const INCH       = 'inches';
    const MILLIMETER = 'millimeters';
    const LITER      = 'liters';
    const MILLILITER = 'milliliters';
    const GALLON     = 'gallons';
    const KILOGRAM   = 'kilograms';
    const GRAM       = 'grams';
    const POUND      = 'pounds';
    const TON        = 'tons';
    const OUNCE      = 'ounces';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::METER, 'label' => __('Meter')],
            ['value' => self::CENTIMETER, 'label' => __('Centimeter')],
            ['value' => self::MILLIMETER, 'label' => __('Millimeter')],
            ['value' => self::FOOT, 'label' => __('Foot')],
            ['value' => self::INCH, 'label' => __('Inch')],
            ['value' => self::LITER, 'label' => __('Liter')],
            ['value' => self::MILLILITER, 'label' => __('Milliliter')],
            ['value' => self::GALLON, 'label' => __('Gallon')],
            ['value' => self::GRAM, 'label' => __('Gram')],
            ['value' => self::KILOGRAM, 'label' => __('Kilogram')],
            ['value' => self::TON, 'label' => __('Ton')],
            ['value' => self::POUND, 'label' => __('Pound')],
            ['value' => self::OUNCE, 'label' => __('Ounce')]
        ];
    }
}
