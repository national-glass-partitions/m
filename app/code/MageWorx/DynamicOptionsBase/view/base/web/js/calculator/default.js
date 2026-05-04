/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return {
        calculate: function (dynamicOptions, pricePerUnit) {
            var dynamicPrice = 1;
            $.each(dynamicOptions, function(index, element) {
                if (typeof element['value'] !== 'undefined' && element.value) {
                    dynamicPrice *= element['value'];
                } else if (index !== 'price_per_unit') {
                    dynamicPrice *= 0;
                }
            });
            dynamicPrice *= pricePerUnit;

            return dynamicPrice;
        }
    };
});
