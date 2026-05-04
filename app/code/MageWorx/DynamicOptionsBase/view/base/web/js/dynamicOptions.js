/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'dynamicOptionsDefaultCalculator',
    'Magento_Catalog/js/price-utils',
    'qTip',
    'underscore',
    'priceBox',
    'mage/validation',
    'jquery-ui-modules/widget'
], function ($, defaultCalculator, utils, qTip, _) {
    'use strict';

    $.widget('mageworx.dynamicOptions', {

        options: {},
        extended_tier_prices: {},

        /**
         * Triggers one time at first run (from base.js)
         * @param optionConfig
         * @param productConfig
         * @param base
         * @param self
         */
        firstRun: function firstRun(optionConfig, productConfig, base, self) {
            this.priceBox = $('.price-box', form);

            var form = base.getFormElement(),
                config = base.options,
                options = $(config.optionsSelector, form);

            options.filter('input[type="text"], textarea').each(function (index, element) {
                var $element = $(element),
                    optionId = utils.findOptionId($element);
                $('#mageworx_dynamic_option_hint_icon_' + optionId).qtip({
                    content: {
                        text: $('#mageworx_dynamic_option_hint_' + optionId).html()
                    },
                    style: {
                        classes: 'qtip-light'
                    },
                    position: {
                        target: false
                    }
                });
                $('#mageworx_dynamic_option_hint_' + optionId).hide();
            });
        },

        /**
         * Triggers each time after the all updates when option was changed (from the base.js)
         * @param base
         * @param productConfig
         */
        applyChanges: function (base, productConfig) {
            var self = this,
                exit = false,
                form = base.getFormElement(),
                config = base.options,
                options = $(config.optionsSelector, form),
                dynamicOptions = this.options['options_data'],
                isAbsolutePrice = parseInt(productConfig['absolute_price']);

            if (dynamicOptions.length === 0) {
                return;
            }

            options.filter('input[type="text"], textarea').each(function (index, element) {
                var $element = $(element),
                    optionId = utils.findOptionId($element),
                    value = parseFloat($element.val());

                if (typeof dynamicOptions[optionId] !== 'undefined') {
                    if ($element.closest('.field').css('display') === 'none') {
                        exit = true;

                        return;
                    }

                    if (Number.isNaN(value) && $element.val() === '') {
                        exit = true;

                        return;
                    }

                    if (!$.validator.validateElement($element)) {
                        exit = true;
                        return;
                    }
                    dynamicOptions[optionId]['value'] = value;
                }
            });

            if (exit) {
                productConfig.dynamicPriceExclTax = 0;
                productConfig.dynamicPriceInclTax = 0;

                return;
            }

            productConfig['isUsedDynamicOptions'] = true;

            var pricePerUnitData = this.options.price_per_unit,
                dynamicPriceExclTax = defaultCalculator.calculate(dynamicOptions, pricePerUnitData.amount_excl_tax),
                dynamicPriceInclTax = defaultCalculator.calculate(dynamicOptions, pricePerUnitData.amount_incl_tax);

            if (productConfig['type_id'] === 'configurable') {
                var additionalPrice = {};
                additionalPrice['mwDynamicOptions'] = {
                    'basePrice': {
                        'amount': pricePerUnitData.amount
                    }
                };

                this.priceBox.trigger('updatePrice', additionalPrice);
            } else {
                productConfig.dynamicPriceExclTax = dynamicPriceExclTax;
                productConfig.dynamicPriceInclTax = dynamicPriceInclTax;

                if (!_.isUndefined(productConfig.extended_tier_prices) && productConfig.extended_tier_prices.length > 0) {
                    var tierPrices = productConfig.extended_tier_prices;

                    _.each(tierPrices, function (tier, index) {
                        productConfig.extended_tier_prices[index]['price_incl_tax'] =
                            self.extended_tier_prices[index]['price_incl_tax'] + dynamicPriceInclTax;
                        productConfig.extended_tier_prices[index]['price_incl_tax'] =
                            self.extended_tier_prices[index]['price_incl_tax'] + dynamicPriceInclTax;
                    });
                }
            }
        },
    });

    return $.mageworx.dynamicOptions;
});
