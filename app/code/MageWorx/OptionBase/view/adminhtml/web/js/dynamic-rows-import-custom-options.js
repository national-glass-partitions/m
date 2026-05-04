/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid',
    'underscore',
    'mageUtils'
], function (DynamicRows, _, utils) {
    'use strict';

    return DynamicRows.extend({
        defaults: {
            mappingSettings: {
                enabled: false,
                distinct: false
            },
            update: true,
            map: {
                'option_id': 'option_id'
            },
            identificationProperty: 'option_id',
            identificationDRProperty: 'option_id'
        },

        /** @inheritdoc */
        processingInsertData: function (data) {
            var options = [],
                currentOption,
                generalContext = this;

            if (!data) {
                return;
            }
            _.each(data, function (item) {
                if (!item.options) {
                    return;
                }
                _.each(item.options, function (option) {
                    currentOption = utils.copy(option);

                    if (currentOption.hasOwnProperty('sort_order')) {
                        delete currentOption['sort_order'];
                    }

                    if (currentOption.values.length > 0) {
                        generalContext.removeOptionsIds(currentOption.values);
                    }
                    options.push(currentOption);
                });
            });

            if (!options.length) {
                return;
            }
            this.cacheGridData = options;
            _.each(options, function (opt) {
                this.mappingValue(opt);
            }, this);

            this.insertData([]);
        },

        /**
         * Standard logic: Removes option_id and option_type_id from every option
         * Modified by MageWorx - leave as it is, because we should map optionIds for dependencies
         *
         * @param {Array} options
         */
        removeOptionsIds: function (options) {
            return;
        },

        /** @inheritdoc */
        processingAddChild: function (ctx, index, prop) {
            if (!ctx) {
                this.showSpinner(true);
                this.addChild(ctx, index, prop);

                return;
            }

            this._super(ctx, index, prop);
        },

        /**
         * Set empty array to dataProvider
         */
        clearDataProvider: function () {
            this.source.set(this.dataProvider, []);
        },

        /**
         * Mutes parent method
         */
        updateInsertData: function () {
            return false;
        }
    });
});
