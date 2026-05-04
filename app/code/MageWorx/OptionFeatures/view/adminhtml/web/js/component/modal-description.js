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
            storeIds: '',
            isWysiwygEnabled: false,
            descriptionModalDataScope: 'description.data.product.custom_data',
            formName: '',
            buttonName: '',
            isSchedule: false,
            productEntityProvider: 'product_form.product_form_data_source',
            entityProvider: '',
            entityDataScope: '',
            pathDescriptionModal: 'description_modal.content.fieldset',
            pathGroupContainer: '',
            pathDescription: '',
            pathUseGlobal: ''
        },

        /**
         * Reload modal
         *
         * @param params
         */
        reloadModal: function (params) {
            this.initVariables(params);
            this.initFields();
            this.initDescriptionChange();
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
            this.isWysiwygEnabled = params.isWysiwygEnabled;
            this.storeIds = $.parseJSON(params.storeIds);
            this.pathGroupContainer = params.pathGroupContainer;
            this.pathDescription = params.pathDescription;
            this.pathUseGlobal = params.pathUseGlobal;
        },

        /**
         * Initialize wysiwyg
         */
        initFields: function () {
            var self = this;
            var data = null;
            var jsonData = registry.get(this.entityProvider).get(this.entityDataScope).description;

            if (jsonData !== null && jsonData) {
                data = $.parseJSON(jsonData);
            }

            var stores = this.storeIds;

            stores.forEach(function (storeId, index) {
                var descriptionInput = self.getDescriptionInput(storeId);
                self.setInputValue(descriptionInput, storeId, '');
                var $description = self.getDescriptionSelector(storeId);

                if (storeId != 0) {
                    var useGlobalCheckbox = self.getUseGlobalCheckbox(storeId);
                    useGlobalCheckbox.checked(true);
                    $description.css('display', 'none');
                } else {
                    $description.css('display', 'block');
                }
            });

            if (data === null || data.length < 1) {
                return;
            }

            $.each(data, function(index, dataItem) {
                data[index]['description'] = self.decodeHtml(dataItem['description']);
                var descriptionInput = self.getDescriptionInput(dataItem['store_id']);
                self.setInputValue(descriptionInput, dataItem['store_id'], dataItem['description']);
                var $description = self.getDescriptionSelector(dataItem['store_id']);

                if (dataItem['store_id'] != 0) {
                    var useGlobalCheckbox = self.getUseGlobalCheckbox(dataItem['store_id']);
                    useGlobalCheckbox.checked(false);
                    $description.css('display', 'block');
                }
            });
        },

        /**
         * Initialize description change event
         */
        initDescriptionChange: function () {
            var self = this;
            var stores = this.storeIds;

            stores.forEach(function (storeId, index) {
                if (storeId != 0) {
                    $('[name="data[product][custom_data][use_global_' + storeId + ']"]').on('change', function () {
                        var $description = self.getDescriptionSelector(storeId);
                        if (this.checked == true) {
                            $description.css('display', 'none');
                        } else {
                            $description.css('display', 'block');
                        }
                    });
                }
            });
        },

        /**
         * Get description input element by storeId
         */
        getDescriptionInput: function (storeId) {
            return registry.get(
                this.formName + '.' + this.formName + '.' + this.pathDescriptionModal +
                '.' + this.pathGroupContainer + storeId + '.' + this.pathDescription + storeId
            );
        },

        /**
         * Get description selector by storeId
         */
        getDescriptionSelector: function (storeId) {
            return $('*[data-index="description_' + storeId + '"]');
        },

        /**
         * Get use global checkbox element by storeId
         */
        getUseGlobalCheckbox: function (storeId) {
            return registry.get(
                this.formName + '.' + this.formName + '.' + this.pathDescriptionModal +
                '.' + this.pathGroupContainer + storeId + '.' + this.pathUseGlobal + storeId
            );
        },

        /**
         * Update input value
         */
        setInputValue: function (descriptionInput, storeId, value) {
            if (this.isWysiwygEnabled) {
                $('#toggle' + this.formName + '_description_' + storeId).trigger('click');
            }
            descriptionInput.value(value);
            if (this.isWysiwygEnabled) {
                $('#toggle' + this.formName + '_description_' + storeId).trigger('click');
            }
        },

        /**
         * Validate and save prices, close modal
         */
        save: function () {
            this.saveDescriptions();
            this.toggleModal();
        },

        /**
         * Save descriptions before close modal
         */
        saveDescriptions: function () {
            var self = this;
            var data = {};
            var stores = this.storeIds;
            var isEmptyData = true;

            stores.forEach(function (storeId, index) {
                if (storeId == 0 || registry.get(self.entityProvider).get(self.descriptionModalDataScope + '.use_global_' + storeId) == 0) {
                    var storeData = {};
                    storeData['description'] = registry.get(self.entityProvider).get(self.descriptionModalDataScope + '.description_' + storeId);
                    storeData['store_id'] = storeId;
                    if (storeData['description'] !== '') {
                        isEmptyData = false;
                    }
                    data[index] = storeData;
                }
            });

            var jsonData = JSON.stringify(data);
            this.updateButtonStatus(isEmptyData);
            registry.get(this.entityProvider).set(this.entityDataScope + '.description', jsonData);
        },

        /**
         * Decode html
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
         * @param isEmptyData
         */
        updateButtonStatus: function (isEmptyData) {
            if (isEmptyData === false) {
                $('*[data-name="' + this.buttonName + '"]').addClass('active');
            } else {
                $('*[data-name="' + this.buttonName + '"]').removeClass('active');
            }
        }
    });
});
