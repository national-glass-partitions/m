/**
 * Copyright © 2019 Magento. All rights reserved.
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
     * for "Qty Input" feature
     */
    return uiCheckbox.extend({

        /**
         * List of valid option types (show element if they are selected for the current option)
         */
        availableTypes: [
            'drop_down',
            'radio',
            'multiple',
            'checkbox'
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
        }
    });
});
