/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mageworx.optionInventory', {
        options: {
            optionConfig: {}
        },

        firstRun: function firstRun(optionConfig, productConfig, base, self) {
            base.setOptionValueTitle();
            var manageStockValues = self.options.manage_stock_values_data;

            $.each(manageStockValues, function (optIndex, optElement) {
                $.each(optElement, function (valIndex, valElement) {
                    manageStockValues[optIndex][valIndex] = optionConfig[optIndex][valIndex];
                });
            });

            if (manageStockValues.length !== 0) {
                $.ajax({
                    url: self.options.stock_message_url,
                    data: {'opConfig': JSON.stringify(manageStockValues)},
                    type: 'post',
                    dataType: 'json'
                })
                    .done(function (response) {
                        base.setOptionValueTitle(response.result);
                    });
            }
        },

        update: function update(option, optionConfig, productConfig, base) {
            return;
        }
    });

    return $.mageworx.optionInventory;

});
