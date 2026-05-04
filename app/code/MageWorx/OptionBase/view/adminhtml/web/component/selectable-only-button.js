/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'MageWorx_OptionBase/component/button',
    'uiRegistry',
    'underscore'
], function (Button, registry, _) {
    'use strict';

    return Button.extend({

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
         * Initializes component.
         *
         * @returns {Object} Chainable.
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
                            self.visible(false);
                        } else {
                            self.visible(true);
                        }

                        resolve(typeSelect);
                    }
                }, 500);
            }).then(
                function (result) {
                    result.on('update', function (e) {
                        if (self.availableTypes.indexOf(result.value()) != -1) {
                            self.visible(true);
                        } else {
                            self.visible(false);
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
