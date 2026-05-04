/**
 * Copyright © 2017 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract',
    'uiRegistry',
    'underscore'
], function (input, registry, _) {
    'use strict';

    return input.extend({
        initialize: function () {
            this._super();
            var self = this;

            new Promise(function (resolve, reject) {
                var timer_search_container = setInterval(function () {
                    var container = self.containers[0];
                    if (typeof container !== 'undefined') {
                        clearInterval(timer_search_container);
                        var path = 'source.' + container.dataScope,
                            optionType = self.get(path).template_name,
                            typeSelect = registry.get("ns = " + container.ns +
                                ", parentScope = " + container.dataScope +
                                ", index = type");
                            if (_.isEmpty(optionType)) {
                                self.hide();
                            }

                        resolve(typeSelect);
                    }
                }, 500);
            });

            return this;
        }
    });
});
