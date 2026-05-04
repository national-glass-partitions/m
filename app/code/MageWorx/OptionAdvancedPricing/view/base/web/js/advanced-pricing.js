/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'priceBox',
    'jquery-ui-modules/widget'
], function ($, utils) {
    'use strict';

    $.widget('mageworx.optionAdvancedPricing', {
        options: {
            optionConfig: {}
        },

        firstRun: function firstRun(optionConfig, productConfig, base, self) {
            base.setOptionValueTitle();
            this.priceBox = $('.price-box', form);
            this.optionConfigSaved = utils.deepClone(optionConfig);
            this.productConfig = productConfig;

            var form = base.getFormElement(),
                config = base.options,
                options = $(config.optionsSelector, form);

            options.filter('input[type!="hidden"]').each(function (index, element) {
                var $element = $(element),
                    optionId = utils.findOptionId($element),
                    values = $element.val();

                if (optionConfig[optionId]['type'] === 'char') {
                    self.updatePricePerCharacter(optionId, optionConfig, values);
                }
            });
        },

        updatePricePerCharacter: function update(optionId, optionConfig, values) {
            optionConfig[optionId]['prices']['basePrice']['amount'] =
                values.length * this.optionConfigSaved[optionId]['prices']['basePrice']['amount'];
            optionConfig[optionId]['prices']['finalPrice']['amount'] =
                values.length * this.optionConfigSaved[optionId]['prices']['finalPrice']['amount'];

            $('#price_per_character_' + optionId).html(
                optionConfig[optionId]['prices']['finalPrice']['amount'].toFixed(2)
            );

            if (this.productConfig['type_id'] === 'configurable') {
                var additionalPrice = {};
                additionalPrice['options[' + optionId + ']'] = {
                    'basePrice': {
                        'amount': parseFloat(optionConfig[optionId]['prices']['basePrice']['amount'].toFixed(2))
                    },
                    'finalPrice': {
                        'amount': parseFloat(optionConfig[optionId]['prices']['finalPrice']['amount'].toFixed(2))
                    }
                };

                this.priceBox.trigger('updatePrice', additionalPrice);
            }
        },

        update: function update(option, optionConfig, productConfig, base) {
            var $option = $(option),
                values = $option.val(),
                self = this;

            $('option', $option).each(function (i, e) {
                var tierPrice = $('#value_' + e.value + '_tier_price');
                if (tierPrice.length > 0) {
                    tierPrice.hide();
                }
            });

            var optionId = base.getOptionId($option);
            if (isNaN(optionId)) {
                var name = $option.attr('name');
                var matches = name.match(/(\d+)/);
                if (matches) {
                    optionId = parseInt(matches[0]);
                }
            }
            if ($.inArray(self.options.optionTypes[optionId], ['drop_down', 'multiple', 'checkbox', 'radio', 'area', 'field']) === -1) {
                return;
            }

            if ($.inArray(self.options.optionTypes[optionId], ['area', 'field']) !== -1) {
                if (optionConfig[optionId]['type'] === 'char') {
                    self.updatePricePerCharacter(optionId, optionConfig, values);
                }
            } else if (!values) {
                return;
            } else {
                if (!Array.isArray(values)) {
                    values = [values];
                }

                $(values).each(function (i, e) {
                    var tierPrice = $('#value_' + e + '_tier_price');
                    if (tierPrice.length > 0) {
                        if ($option.is(':checked') || $('option:selected', $option).val()) {
                            tierPrice.show();
                        } else {
                            tierPrice.hide();
                        }
                    }
                });
            }
        }
    });

    return $.mageworx.optionAdvancedPricing;

});
