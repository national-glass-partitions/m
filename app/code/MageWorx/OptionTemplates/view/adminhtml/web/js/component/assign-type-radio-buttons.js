/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/element/checkbox-set',
    'uiRegistry'
], function (jQuery, CheckboxSet, uiRegistry) {
    'use strict';

    return CheckboxSet.extend({

        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            var dependentField = uiRegistry.get('visibleByAssignValue = ' + value);
            if (typeof dependentField != 'undefined') {
                var dependentFields = uiRegistry.filter('dependsOn = ' + this.index);
                jQuery(dependentFields).each(function (e, t) {
                    t.visible(false);
                });

                dependentField.visible(true);
            }

            return this._super();
        },

    });
});
