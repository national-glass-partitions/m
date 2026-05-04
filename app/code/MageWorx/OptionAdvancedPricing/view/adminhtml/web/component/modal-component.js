/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiRegistry',
    'jquery',
    'underscore',
    'Magento_Ui/js/modal/modal-component'
], function (registry, $, _, ModalComponent) {
    'use strict';

    return ModalComponent.extend({

        defaults: {
            isSpecialPriceEnabled: true,
            isTierPriceEnabled: true,
            dynamicRowsPath: 'option_advanced_pricing_modal.content.fieldset',
            dynamicRowsDataScope: 'option_advanced_pricing.data.product.custom_data',
            specialPriceDynamicRows: '',
            tierPriceDynamicRows: '',
            formName: '',
            isSchedule: false,
            buttonName: '',
            productEntityProvider: 'product_form.product_form_data_source',
            entityProvider: '',
            entityDataScope: ''
        },

        /**
         * Reload modal
         *
         * @param params
         */
        reloadModal: function (params) {
            this.initVariables(params);
            if (this.isSpecialPriceEnabled) {
                this.initSpecialPrice();
            }
            if (this.isTierPriceEnabled) {
                this.initTierPrice();
            }
        },

        /**
         * Initialize variables
         *
         * @param params
         */
        initVariables: function (params) {
            this.entityProvider = params.provider;
            this.entityDataScope = params.dataScope;
            this.buttonName = params.buttonName;
            this.isSchedule = params.isSchedule;
            if (this.entityProvider === 'catalogstaging_update_form.catalogstaging_update_form_data_source') {
                this.isSchedule = true;
            }
            this.formName = params.formName;
            this.isSpecialPriceEnabled = params.isSpecialPriceEnabled;
            this.isTierPriceEnabled = params.isTierPriceEnabled;
            this.isValidSku = registry.get(this.entityProvider).get(this.entityDataScope).sku_is_valid === '1';
            this.linkedFields = !_.isUndefined(registry.get(this.entityProvider).get('data.product.option_link_fields'))
                ? registry.get(this.entityProvider).get('data.product.option_link_fields')
                : {};
        },

        /**
         * Initialize special price for selected option value
         */
        initSpecialPrice: function () {
            var self = this,
                specialPriceData = null,
                jsonSpecialPrice = registry.get(this.entityProvider).get(this.entityDataScope).special_price;
            if (jsonSpecialPrice !== '') {
                specialPriceData = $.parseJSON(jsonSpecialPrice);
            }

            this.specialPriceDynamicRows = registry.get(
                this.formName + '.' + this.formName + '.' + this.dynamicRowsPath + '.special_pricing'
            );

            this.specialPriceDynamicRows.recordData([]);
            this.specialPriceDynamicRows.clear();
            if (!_.isUndefined(this.linkedFields.special_price)) {
                this.specialPriceDynamicRows.disabled(this.isValidSku);
            }
            if (specialPriceData === null) {
                return;
            }
            specialPriceData.forEach(function (specialPriceDataItem, index) {
                specialPriceData[index]['comment'] = self.decodeHtml(specialPriceDataItem['comment']);
            });
            if (this.isSchedule === true) {
                registry.get(this.productEntityProvider)
                    .set(this.dynamicRowsDataScope + '.special_pricing', specialPriceData);
            } else {
                registry.get(this.entityProvider).set(this.dynamicRowsDataScope + '.special_pricing', specialPriceData);
            }

            this.specialPriceDynamicRows.initChildren();
        },

        /**
         * Initialize tier price for selected option value
         */
        initTierPrice: function () {
            var tierPriceData = null,
                jsonTierPrice = registry.get(this.entityProvider).get(this.entityDataScope).tier_price;
            if (jsonTierPrice !== '') {
                tierPriceData = $.parseJSON(jsonTierPrice);
            }

            this.tierPriceDynamicRows = registry.get(
                this.formName + '.' + this.formName + '.' + this.dynamicRowsPath + '.tier_pricing'
            );
            this.tierPriceDynamicRows.recordData([]);
            this.tierPriceDynamicRows.clear();

            if (!_.isUndefined(this.linkedFields.tier_price)) {
                this.tierPriceDynamicRows.disabled(this.isValidSku);
            }
            if (tierPriceData === null) {
                return;
            }
            if (this.isSchedule === true) {
                registry.get(this.productEntityProvider)
                    .set(this.dynamicRowsDataScope + '.tier_pricing', tierPriceData);
            } else {
                registry.get(this.entityProvider).set(this.dynamicRowsDataScope + '.tier_pricing', tierPriceData);
            }

            this.tierPriceDynamicRows.initChildren();
        },

        /**
         * Validate and save prices, close modal
         */
        save: function () {
            this.valid = true;
            var specialPrices = null;
            var tierPrices = null;
            if (this.isSpecialPriceEnabled) {
                this.validate(this.specialPriceDynamicRows);
                if (!this.valid) {
                    return;
                }
                specialPrices = this.saveSpecialPrice();
            }
            if (this.isTierPriceEnabled) {
                this.validate(this.tierPriceDynamicRows);
                if (!this.valid) {
                    return;
                }
                tierPrices = this.saveTierPrice();
            }

            this.updateButtonStatus(specialPrices, tierPrices);
            this.toggleModal();
        },

        /**
         * Save special price before close modal.
         */
        saveSpecialPrice: function () {
            var specialPrices = [];
            this.specialPriceDynamicRows.relatedData.forEach(function (data, index) {
                specialPrices.push(data);
            });
            var jsonData = specialPrices.length ? JSON.stringify(specialPrices) : "";
            this.updateButtonStatus(specialPrices);
            registry.get(this.entityProvider).set(this.entityDataScope + '.special_price', jsonData);
            return specialPrices;
        },

        /**
         * Save tier price before close modal.
         */
        saveTierPrice: function () {
            var tierPrices = [];
            this.tierPriceDynamicRows.relatedData.forEach(function (data, index) {
                tierPrices.push(data);
            });
            var jsonData = tierPrices.length ? JSON.stringify(tierPrices) : "";
            registry.get(this.entityProvider).set(this.entityDataScope + '.tier_price', jsonData);
            return tierPrices;
        },

        /**
         * Decode html
         *
         * @param str
         */
        decodeHtml: function (str) {
            var map =
                {
                    '&amp;': '&',
                    '&lt;': '<',
                    '&gt;': '>',
                    '&quot;': '"',
                    '&#039;': "'"
                };
            return str.replace(/&amp;|&lt;|&gt;|&quot;|&#039;/g, function (m) {
                return map[m];
            });
        },

        /**
         * Update button status
         *
         * @param specialPrices
         * @param tierPrices
         */
        updateButtonStatus: function (specialPrices, tierPrices) {
            if ((specialPrices && specialPrices.length > 0) || (tierPrices && tierPrices.length > 0)) {
                $('*[data-name="' + this.buttonName + '"]').addClass('active');
            } else {
                $('*[data-name="' + this.buttonName + '"]').removeClass('active');
            }
        }
    });
});
