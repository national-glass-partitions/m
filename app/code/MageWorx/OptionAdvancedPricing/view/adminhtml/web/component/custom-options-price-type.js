/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/select',
    'Magento_ConfigurableProduct/js/components/custom-options-price-type',
    'uiRegistry',
    'mageUtils'
], function (_, Select, priceType, registry, utils) {
    'use strict';

    return priceType.extend({

        initialize: function () {
            var self = this;
            this._super();
            this.savedOptions = this.getSavedOptions();
            var optionType = this.getOptionType();

            if (typeof optionType !== 'undefined') {
                this.optionType.value.subscribe(function (value) {
                    self.updateOptionsByType(value);
                });

                this.updateOptionsByType(optionType.value());
            }

            return this;
        },

        /**
         *
         * @param value
         * @returns {updateOptionsByType}
         */
        updateOptionsByType: function (value) {
            var currentValue;
                if (value === 'field' || value === 'area') {
                    currentValue = this.value();
                    this.options(this.getSavedOptions());
                    this.value(currentValue);
                } else {
                    currentValue = this.value();
                    var newOptions = utils.copy(this.getSavedOptions());
                    newOptions.pop();
                    this.options(newOptions);
                    this.value(currentValue);
                }

            return this;
        },

        /**
         * Updates options.
         *
         * @param {Boolean} variationsEmpty
         * @returns {Boolean}
         */
        updateOptions: function (variationsEmpty) {
            var result = this._super();
            var optionType = this.getOptionType();

            if (typeof optionType !== 'undefined') {
                this.updateOptionsByType(optionType.value());
            }

            return result;
        },

        getOptionType: function () {
            if (typeof this.optionType === 'undefined') {
                var parents = this.parentName.split('.');
                var name = parents[0] + '.' + parents[1] + '.' + parents[2] + '.' +
                    parents[3] + '.' + parents[4] + '.container_option.container_common.type';
                this.optionType = registry.get(name);
            }

            return this.optionType;
        },

        getSavedOptions: function () {
            if (typeof this.savedOptions === 'undefined') {
                this.savedOptions = utils.copy(this.options());
            }

            return this.savedOptions;
        }
    });
});
