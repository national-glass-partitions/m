/**
 * Copyright © 2017 Magento. All rights reserved.
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
     * Extend base checkbox element. Adds filtration (toggle view) based on the option type selected.
     * Used in the: \MageWorx\OptionSwatches\Ui\DataProvider\Product\Form\Modifier\Swatches
     * for "Is Swatch" flag for dropdown
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
            'multiple'
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
                    var container = self.containers[0];
                    if (typeof container !== 'undefined') {
                        clearInterval(timer_search_container);
                        var path = 'source.' + container.dataScope,
                            optionType = self.get(path).type,
                            typeSelect = registry.get("ns = " + container.ns +
                                ", parentScope = " + container.dataScope +
                                ", index = type");
                        if (self.availableTypes.indexOf(optionType) == -1) {
                            self.hide();
                        } else {
                            self.show();
                        }

                        resolve(typeSelect);
                    }
                }, 500);
            }).then(
                function (result) {
                    result.on('update', function (e) {
                        if (self.availableTypes.indexOf(result.value()) != -1) {
                            self.show();
                        } else {
                            self.hide();
                        }
                    });
                },
                function (error) {
                    console.log(error);
                }
            );

            return this;
        },

        /**
         * Invokes onCheckedChanged method of parent class,
         * Contains radiobutton logic for single selection options (drop_down, radio)
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
                        clearInterval(timer_search_container);
                        var option = self.containers[0],
                            path = 'source.' + option.dataScope,
                            optionType = self.get(path).type,
                            typeSelect = registry.get("ns = " + option.ns +
                                ", parentScope = " + option.dataScope +
                                ", index = type");
                        if (self.availableTypesForLLP.indexOf(optionType) !== -1) {
                            var values = registry.get(
                                "ns = " + option.ns + ", parentScope = " + option.dataScope + ".values"
                            );
                            if (typeof values !== 'undefined' && typeof values.containers !== 'undefined') {
                                if (typeof values.containers[0] !== 'undefined') {
                                    values.containers[0].elems.each(function (record) {
                                        if (typeof record._elems !== 'undefined') {
                                            var currentDataScope = record.dataScope,
                                                productFormDataSource = registry.get(record.provider),
                                                loadLinkedProduct = productFormDataSource.get(
                                                    currentDataScope + ".load_linked_product"
                                                ),
                                                currentValueSku = productFormDataSource.get(currentDataScope + ".sku"),
                                                productSku = productFormDataSource.data.product.sku,
                                                isDefault = record._elems[self.isDefaultIndex];
                                            if (typeof isDefault == "object") {
                                                if (self.checked() === true &&  Number(loadLinkedProduct)) {
                                                    isDefault.disabled(true);
                                                    isDefault.checked(false);
                                                    if (currentValueSku === productSku) {
                                                        isDefault.checked(true);
                                                    }
                                                } else {
                                                    isDefault.disabled(false);
                                                }
                                            }
                                        }
                                    });
                                }
                            }
                        }
                        resolve(typeSelect);
                    }
                }, 500);
            }).then(
                function (result) {
                    result.on('update', function (e) {
                        if (typeof self.containers[0] !== 'undefined') {
                            var option = self.containers[0],
                                values = registry.get(
                                    "ns = " + option.ns + ", parentScope = " + option.dataScope + ".values"
                                );
                            if (typeof values.containers !== 'undefined') {
                                values.containers[0].elems.each(function (record) {
                                    if (typeof record._elems !== 'undefined') {
                                        var currentDataScope = record.dataScope,
                                            productFormDataSource = registry.get(record.provider),
                                            loadLinkedProduct = productFormDataSource.get(
                                                currentDataScope + ".load_linked_product"
                                            ),
                                            currentValueSku = productFormDataSource.get(currentDataScope + ".sku"),
                                            productSku = productFormDataSource.data.product.sku,
                                            isDefault = record._elems[self.isDefaultIndex];
                                        if (typeof isDefault == "object") {
                                            if (self.availableTypesForLLP.indexOf(result.value()) != -1) {
                                                if (self.checked() === true && Number(loadLinkedProduct)) {
                                                    isDefault.disabled(true);
                                                    if (currentValueSku === productSku) {
                                                        isDefault.checked(true);
                                                    }
                                                } else {
                                                    isDefault.checked(false);
                                                    isDefault.disabled(false);
                                                }
                                            } else {
                                                if (Number(loadLinkedProduct)) {
                                                    isDefault.disabled(false);
                                                }
                                            }
                                        }
                                    }
                                });
                            }
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
