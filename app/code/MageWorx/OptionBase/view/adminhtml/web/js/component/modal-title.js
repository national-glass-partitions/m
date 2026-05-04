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
            fieldName: '',
            storeIds: '',
            titleModalDataScope: '',
            formName: '',
            buttonName: '',
            isSchedule: false,
            productEntityProvider: 'product_form.product_form_data_source',
            entityProvider: '',
            entityDataScope: '',
            pathTitleModal: 'title_modal.content.fieldset',
            pathGroupContainer: '',
            pathTitle: '',
            pathUseGlobal: '',
            currentStoreId: '',
            globalStoreId: '0'
        },

        /**
         * Reload modal
         *
         * @param params
         */
        reloadModal: function (params) {
            this.initVariables(params);
            this.initFields();
            this.initTitleChange();
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
            this.pathTitle = params.pathTitle;
            this.pathUseGlobal = params.pathUseGlobal;
            this.fieldName = params.fieldName;
            this.titleModalDataScope = this.fieldName + '.data.product.custom_data';
            this.currentStoreId = params.currentStoreId;
            this.isValidSku = registry.get(this.entityProvider).get(this.entityDataScope).sku_is_valid === '1';
            this.linkedFields = !_.isUndefined(registry.get(this.entityProvider).get('data.product.option_link_fields'))
                ? registry.get(this.entityProvider).get('data.product.option_link_fields')
                : {};

        },

        /**
         * Initialize modal fields
         */
        initFields: function () {
            var self = this;
            var data = null;
            var jsonData = registry.get(this.entityProvider).get(this.entityDataScope + '.' + this.fieldName);

            if (jsonData !== null && jsonData) {
                data = $.parseJSON(jsonData);
            }

            var stores = this.storeIds;
            var actualData = {};

            stores.forEach(function (storeId, index) {
                var storeData = {};

                storeData['use_default'] = true;
                if (storeId == self.currentStoreId) {
                    storeData['title'] = self.getMainFormTitleInputValue();
                    if (storeId != self.globalStoreId) {
                        storeData['use_default'] = self.getMainFormTitleIsUseDefaultValue();
                    }
                } else {
                    storeData['title'] = '';
                }
                storeData['store_id'] = storeId;
                actualData[storeId] = storeData;
            });

            if (data === null || data.length < 1) {
                this.setFieldData(actualData);
                return;
            }

            $.each(data, function(index, dataItem) {
                var storeData = {};
                if (actualData[dataItem['store_id']]['title'] === '') {
                    storeData['store_id'] = dataItem['store_id'];
                    storeData['title'] = self.decodeHtml(dataItem['title']);
                    storeData['use_default'] = false;
                    actualData[dataItem['store_id']] = storeData;
                }
            });

            this.setFieldData(actualData);
        },

        /**
         * Set actual data for modal fields
         *
         * @param {object} data
         */
        setFieldData: function (data) {
            var self = this;
            $.each(data, function(storeId, dataItem) {
                var titleInput = self.getModalTitleInput(storeId);
                var $title = self.getTitleSelector(storeId);
                if (!_.isUndefined(self.linkedFields.name)) {
                    titleInput.disabled(self.isValidSku);
                }

                titleInput.value(dataItem['title']);
                if (storeId != self.globalStoreId) {
                    var useGlobalCheckbox = self.getUseGlobalCheckbox(storeId);
                    if (!_.isUndefined(self.linkedFields.name)) {
                        useGlobalCheckbox.disabled(self.isValidSku);
                    }
                    useGlobalCheckbox.checked(dataItem['use_default']);
                    if (dataItem['use_default']) {
                        $title.css('display', 'none');
                    } else {
                        $title.css('display', 'block');
                    }
                }
            });
        },

        /**
         * Initialize title change event
         */
        initTitleChange: function () {
            var self = this;
            var stores = this.storeIds;

            stores.forEach(function (storeId, index) {
                if (storeId != self.globalStoreId) {
                    $('[name="data[product][custom_data][use_global_' + storeId + ']"]').on('change', function () {
                        var $title = self.getTitleSelector(storeId);
                        if (this.checked == true) {
                            if (self.getTitleSelector(self.globalStoreId).find(':input').val() === '') {
                                registry
                                    .get(self.entityProvider)
                                    .set(
                                        self.titleModalDataScope + '.title_' + self.globalStoreId,
                                        $title.find(':input').val()
                                    );
                            }
                            $title.css('display', 'none');
                        } else {
                            $title.css('display', 'block');
                        }
                    });
                }
            });
        },

        /**
         * Get title input element by storeId
         *
         * @param {string} storeId
         * @returns {object}
         */
        getModalTitleInput: function (storeId) {
            return registry.get(
                this.formName + '.' + this.formName + '.' + this.pathTitleModal +
                '.' + this.pathGroupContainer + storeId + '.' + this.pathTitle + storeId
            );
        },

        /**
         * Get title selector by storeId
         *
         * @param {string} storeId
         * @returns {object}
         */
        getTitleSelector: function (storeId) {
            return $('*[data-index="title_' + storeId + '"]');
        },

        /**
         * Get use global checkbox element by storeId
         *
         * @param {string} storeId
         * @returns {object}
         */
        getUseGlobalCheckbox: function (storeId) {
            return registry.get(
                this.formName + '.' + this.formName + '.' + this.pathTitleModal +
                '.' + this.pathGroupContainer + storeId + '.' + this.pathUseGlobal + storeId
            );
        },

        /**
         * Get main form title input value
         *
         * @returns {string}
         */
        getMainFormTitleInputValue: function () {
            return registry.get(this.entityProvider).get(this.entityDataScope + '.' + 'title');
        },

        /**
         * Get main form title input object
         *
         * @returns {object}
         */
        getMainFormTitleInput: function () {
            var scopeSplitted = this.entityDataScope.split('options.')[1].split('.');
            var optionPart = scopeSplitted[0];
            if (scopeSplitted[2] !== undefined) {
                var valuePart = scopeSplitted[2];
            }
            if (!valuePart) {
                return $('[name="product[options][' + optionPart + '][title]"]');
            } else {
                return $('[name="product[options][' + optionPart + '][values][' + valuePart + '][title]"]');
            }
        },

        /**
         * Get main form title input IsUseDefault value
         *
         * @returns {boolean}
         */
        getMainFormTitleIsUseDefaultValue: function () {
            return this.getMainFormTitleIsUseDefault().prop('checked');
        },

        /**
         * Set main form title input IsUseDefault value
         *
         * @param {boolean} value
         */
        setMainFormTitleIsUseDefaultValue: function (value) {
            this.getMainFormTitleIsUseDefault().prop('checked', value);
        },

        /**
         * Get main form title input IsUseDefault object
         *
         * @returns {object}
         */
        getMainFormTitleIsUseDefault: function () {
            return this.getMainFormTitleInput()
                .parent()
                .find(':checkbox');
        },

        /**
         * Validate and save prices, close modal
         */
        save: function () {
            this.saveTitles();
            this.toggleModal();
        },

        /**
         * Save titles before close modal
         */
        saveTitles: function () {
            var self = this;
            var data = {};
            var stores = this.storeIds;
            var isEmptyData = true;

            stores.forEach(function (storeId, index) {
                var useGlobalValue = registry
                    .get(self.entityProvider)
                    .get(self.titleModalDataScope + '.use_global_' + storeId);

                if (storeId == self.globalStoreId || useGlobalValue == 0) {
                    var storeData = {};
                    storeData['title'] = registry
                        .get(self.entityProvider)
                        .get(self.titleModalDataScope + '.title_' + storeId);
                    storeData['store_id'] = storeId;
                    if (storeData['title'] !== '') {
                        isEmptyData = false;
                    }
                    data[index] = storeData;
                    if (storeId == self.currentStoreId) {
                        registry
                            .get(self.entityProvider)
                            .set(self.entityDataScope + '.' + 'title', storeData['title']);
                    }
                }

                if (storeId == self.currentStoreId && storeId != self.globalStoreId) {
                    var isUseDefaultValue = self.getMainFormTitleIsUseDefaultValue();
                    if ((useGlobalValue && !isUseDefaultValue) || (!useGlobalValue && isUseDefaultValue)) {
                        self.getMainFormTitleIsUseDefault().trigger('click');
                    }
                }
            });

            var jsonData = JSON.stringify(data);
            this.updateButtonStatus(isEmptyData);
            registry.get(this.entityProvider).set(this.entityDataScope + '.' + self.fieldName, jsonData);
        },

        /**
         * Decode html
         *
         * @param {string} str
         * @returns {string}
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
         * @param {boolean} isEmptyData
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
