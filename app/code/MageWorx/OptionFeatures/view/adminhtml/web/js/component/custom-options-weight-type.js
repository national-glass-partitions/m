/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function (_, Select, uiRegistry) {
    'use strict';

    return Select.extend({
        /**
         * {@inheritdoc}
         */
        onUpdate: function () {
            this._super();

            this.updateAddBeforeForWeight();
        },

        /**
         * {@inheritdoc}
         */
        setInitialValue: function () {
            this._super();

            this.updateAddBeforeForWeight();

            return this;
        },

        /**
         * Update addbefore for weight field. Change it to weight unit or % depends of weight_type value.
         */
        updateAddBeforeForWeight: function () {
            var addBefore, currentValue, weightIndex, weightName, uiWeight;

            weightIndex = typeof this.imports.weightIndex == 'undefined' ? 'weight' : this.imports.weightIndex;
            weightName = this.parentName + '.' + weightIndex;

            uiWeight = uiRegistry.get(weightName);

            if (uiWeight && uiWeight.addbeforePool) {
                currentValue = this.value();

                uiWeight.addbeforePool.forEach(function (item) {
                    if (item.value === currentValue) {
                        addBefore = item.label;
                    }
                });

                if (typeof addBefore != 'undefined') {
                    uiWeight.addBefore(addBefore);
                }
            }
        }
    });
});
