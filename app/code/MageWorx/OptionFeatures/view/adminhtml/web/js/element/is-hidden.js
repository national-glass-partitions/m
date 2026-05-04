/**
 * Copyright © Magento. All rights reserved.
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
     * Used in the: \MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier\Features
     * for "Is Hidden" flag for checkbox options
     */
    return uiCheckbox.extend({

        /**
         * Index of isRequired in dynamic-row record element
         */
        isRequiredIndex: 41,

        /**
         * Index of isDefault in dynamic-row record element
         */
        isDefaultIndex: 148,

        /**
         * List of valid option types (show element if they are selected for the current option)
         */
        availableTypes: [
            'checkbox'
        ],

        selectableTypesWithoutCheckbox: [
            'drop_down',
            'radio',
            'multiple'
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
                        if (self.availableTypes.indexOf(optionType) === -1) {
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
                        if (self.availableTypes.indexOf(result.value()) !== -1) {
                            self.show();
                        } else {
                            self.hide();
                            if (self.checked() === true) {
                                self.checked(false);
                                var option = self.containers[0];
                                var values = registry.get(
                                    "ns = " + option.ns + ", parentScope = " + option.dataScope + ".values"
                                );
                                option._elems[self.isRequiredIndex].disabled(false);
                                values.containers[0].elems.each(function (record) {
                                    record._elems[self.isDefaultIndex].disabled(false);
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
        },

        /**
         * Invokes onCheckedChanged method of parent class,
         * Check option's isRequired and disable it
         * Check value's isDefaults and disable them
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
                        var option = self.containers[0];
                        var path = 'source.' + option.dataScope,
                            optionType = self.get(path).type,
                            typeSelect = registry.get("ns = " + option.ns +
                                ", parentScope = " + option.dataScope +
                                ", index = type");
                        if (self.availableTypes.indexOf(optionType) !== -1) {
                            var values = registry.get(
                                "ns = " + option.ns + ", parentScope = " + option.dataScope + ".values"
                            );
                            if (self.checked() === true) {
                                option._elems[self.isRequiredIndex].checked(true);
                                option._elems[self.isRequiredIndex].disabled(true);
                                values.containers[0].elems.each(function (record) {
                                    record._elems[self.isDefaultIndex].checked(true);
                                    record._elems[self.isDefaultIndex].disabled(true);
                                });
                            } else {
                                option._elems[self.isRequiredIndex].disabled(false);
                                values.containers[0].elems.each(function (record) {
                                    record._elems[self.isDefaultIndex].disabled(false);
                                });
                            }
                        }
                        resolve(typeSelect);
                    }
                }, 500);
            }).then(
                function (result) {
                    var newOptionType = result.value();
                    var option = self.containers[0];
                    if (self.selectableTypesWithoutCheckbox.indexOf(newOptionType) !== -1) {
                        var values = registry.get(
                            "ns = " + option.ns + ", parentScope = " + option.dataScope + ".values"
                        );
                        option._elems[self.isRequiredIndex].disabled(false);
                        values.containers[0].elems.each(function (record) {
                            record._elems[self.isDefaultIndex].disabled(false);
                        });
                    }
                },
                function (error) {
                    console.log(error);
                }
            );

            return this;
        }
    });
});
