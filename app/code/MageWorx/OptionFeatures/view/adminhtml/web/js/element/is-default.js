/**
 * Copyright © 2018 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/single-checkbox',
    'uiRegistry',
    'ko',
    'jquery'
], function (uiCheckbox, registry, ko, $) {
    'use strict';

    /**
     * Extend base checkbox element.
     * Uncheck other checkboxes for single-selection option if one is selected.
     * Uncheck all checked values if there are more then one checked value and new option type is drop_down/radio
     * Used in the: \MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier\Features
     * for "Is Default" feature
     */
    return uiCheckbox.extend({

        /**
         * Index of isDefault in dynamic-row record element
         */
        isDefaultIndex: 148,

        /**
         * List of valid option types (show element if they are selected for the current option)
         */
        availableTypes: [
            'drop_down',
            'radio'
        ],

        /**
         * List of valid option types for Load Linked Product (show element if they are selected for the current option)
         */
        availableTypesForLLP: [
            'drop_down',
        ],

        /**
         * Invokes initialize method of parent class,
         * contains initialization logic
         */
        initialize: function () {
            this._super();
            var self = this;
            /**
             * Wait for the option type select render and observe its value
             */
            new Promise(function (resolve, reject) {
                var timer_search_container = setInterval(function () {
                    if (typeof self.containers[0] !== 'undefined') {
                        var option = self.containers[0].containers[0];
                        if (typeof option !== 'undefined') {
                            clearInterval(timer_search_container);
                            var path = 'source.' + option.dataScope,
                                optionType = self.get(path).type,
                                typeSelect = registry.get("ns = " + option.ns +
                                    ", parentScope = " + option.dataScope +
                                    ", index = type");
                            if (self.availableTypesForLLP.indexOf(optionType) !== -1) {
                                option.elems.each(function (record) {
                                    var option = self.containers[0].containers[0],
                                        provider = registry.get(record.provider),
                                        isVisibleLLP = provider.value_settings.data.product.custom_data.load_linked_product,
                                        currentOption = provider.get(option.dataScope),
                                        valueIndex = record.index,
                                        is_swatch = currentOption.is_swatch,
                                        loadLinkedProduct = currentOption.values[valueIndex].load_linked_product,
                                        productSku = registry.get(option.provider).data.product.sku,
                                        isValidSku = currentOption.values[valueIndex].sku_is_valid,
                                        valueSku = currentOption.values[valueIndex].sku,
                                        isDefault = record._elems[self.isDefaultIndex];

                                    if (typeof isVisibleLLP == 'undefined') {
                                        isDefault.disabled(false);
                                        return;
                                    }

                                    if (Number(is_swatch) && Number(loadLinkedProduct) && Number(isValidSku)) {
                                        isDefault.disabled(true);
                                        if (productSku === valueSku) {
                                            isDefault.checked(true);
                                        }
                                    } else {
                                        isDefault.disabled(false);
                                    }
                                });
                            }
                            resolve(typeSelect);
                        }
                    }
                }, 500);
            });

            return this;
        },

        /**
         * @inheritdoc
         */
        setInitialValue: function () {
            this._super();

            var optionDataScope = this.dataScope.split('.values.')[0] + '.is_hidden',
                isHidden = registry.get("ns = " + this.ns + ", dataScope = " + optionDataScope),
                isHiddenChecked = isHidden.checked();

            if (isHiddenChecked) {
                this.checked(isHiddenChecked);
                this.disabled(isHiddenChecked);
            }

            return this;
        },

        /**
         * Invokes onCheckedChanged method of parent class,
         * Contains radiobutton logic for single selection options (drop_down, radio)
         * Contains checkbox logic for multi selection options (multiselect, checkbox)
         */
        onCheckedChanged: function () {
            this._super();
            var self = this;
            /**
             * Wait for the option type select render and observe its value
             */
            new Promise(function (resolve, reject) {
                var timer_search_container = setInterval(function () {
                    if (typeof self.containers[0] !== 'undefined') {
                        var option = self.containers[0].containers[0];
                        if (typeof option !== 'undefined') {
                            clearInterval(timer_search_container);
                            var path = 'source.' + option.dataScope,
                                optionType = self.get(path).type,
                                typeSelect = registry.get("ns = " + option.ns +
                                    ", parentScope = " + option.dataScope +
                                    ", index = type");
                            if (self.availableTypes.indexOf(optionType) !== -1) {
                                if (self.checked() === true) {
                                    option.elems.each(function (record) {
                                        var isDefault = record._elems[self.isDefaultIndex];
                                        if (isDefault.containers[0].dataScope !== self.containers[0].dataScope) {
                                            isDefault.checked(false);
                                        }
                                    });
                                }
                            }
                            resolve(typeSelect);
                        }
                    }
                }, 500);
            }).then(
                function (result) {
                    result.on('update', function (e) {
                        var option = self.containers[0].containers[0],
                            newOptionType = result.value(),
                            checkedCounter = 0;
                        option.elems.each(function (record) {
                            var isDefault = record._elems[self.isDefaultIndex];
                            if (isDefault.checked() === true) {
                                checkedCounter += 1;
                            }
                        });

                        //do not uncheck values if there is less then 2 checked values
                        //or new option type is drop_down/radio
                        if (self.availableTypes.indexOf(newOptionType) !== -1 && checkedCounter > 1) {
                            option.elems.each(function (record) {
                                var isDefault = record._elems[self.isDefaultIndex];
                                isDefault.checked(false);
                            });
                        }
                    });
                },
                function (error) {
                    console.log(error);
                }
            );

            return this;
        }
    });
});
