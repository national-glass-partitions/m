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
            isCustomerGroupEnabled: true,
            isStoreViewEnabled: true,
            isDisableEnabled: true,
            path: 'visibility_modal.content.fieldset',
            formName: '',
            buttonName: '',
            toggleDisabled: 0,
            multiselectCustomerGroup: '',
            multiselectStoreView: ''
        },

        /**
         * Reload modal
         *
         * @param params
         */
        reloadModal: function (params) {
            this.initVariables(params);
            if (this.isCustomerGroupEnabled) {
                this.initCustomerGroup();
            }
            if (this.isStoreViewEnabled) {
                this.initStoreView();
            }
            if (this.isDisableEnabled) {
                this.initDisabled();
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
            this.formName = params.formName;
            this.isCustomerGroupEnabled = params.isCustomerGroupEnabled;
            this.isStoreViewEnabled = params.isStoreViewEnabled;
            this.isDisableEnabled = params.isDisableEnabled;
        },

        /**
         *  Initialize disabled toggle
         */
        initDisabled: function () {
            var disabledValue = registry.get(this.entityProvider).get(this.entityDataScope).disabled;

            this.toggleDisabled = registry.get(
                this.formName + '.' + this.formName + '.' + this.path + '.disabled'
            );
            this.toggleDisabled.value(disabledValue);
        },

        /**
         *  Initialize customer group
         */
        initCustomerGroup: function () {
            var customerGroupData = null,
                ids = [],
                customerGroupValue,
                jsonCustomerGroup = registry.get(this.entityProvider).get(this.entityDataScope).customer_group;

            if (typeof jsonCustomerGroup !== 'undefined' && jsonCustomerGroup !== '') {
                customerGroupData = $.parseJSON(jsonCustomerGroup);
            }

            if (customerGroupData !== null) {
                customerGroupData.map(function (item) {
                    customerGroupValue = item.customer_group_id;
                    if (customerGroupValue) {
                        if (customerGroupValue == '32000') {
                            customerGroupValue = Number(customerGroupValue);
                        }
                        ids.push(customerGroupValue);
                    }
                });
            }

            this.multiselectCustomerGroup = registry.get(
                this.formName + '.' + this.formName + '.' + this.path + '.customer_group'
            );
            this.multiselectCustomerGroup.clear();

            if (customerGroupData !== null && ids.length > 0) {
                this.multiselectCustomerGroup.value(ids);
            }
        },

        /**
         *  Initialize store view
         */
        initStoreView: function () {
            var storeViewData = null,
                ids = [],
                storeViewValue,
                jsonStoreView = registry.get(this.entityProvider).get(this.entityDataScope).store_view;
            if (typeof jsonStoreView !== 'undefined' && jsonStoreView !== '') {
                storeViewData = $.parseJSON(jsonStoreView);
            }

            if (storeViewData !== null) {
                storeViewData.map(function (item) {
                    storeViewValue = item.customer_store_id;
                    if (storeViewValue) {
                        if (storeViewValue == '0') {
                            storeViewValue = Number(storeViewValue);
                        }
                        ids.push(storeViewValue);
                    }
                });
            }

            this.multiselectStoreView = registry.get(
                this.formName + '.' + this.formName + '.' + this.path + '.store_view'
            );
            this.multiselectStoreView.clear();

            if (storeViewData !== null && ids.length > 0) {
                this.multiselectStoreView.value(ids);
            }
        },

        /**
         * Validate and save customer group & store view, close modal
         */
        save: function () {
            this.valid = true;
            var customerGroups = null;
            var storeViews = null;
            var disabled = null;
            if (this.isCustomerGroupEnabled) {
                customerGroups = this.saveCustomerGroup();
            }
            if (this.isStoreViewEnabled) {
                storeViews = this.saveStoreView();
            }
            if (this.isDisableEnabled) {
                registry.get(this.entityProvider).set(this.entityDataScope + '.disabled', this.toggleDisabled.value());
                disabled = this.toggleDisabled.value();
            }
            this.updateButtonStatus(customerGroups, storeViews, disabled);
            this.toggleModal();
        },

        /**
         * save customer group
         */
        saveCustomerGroup: function () {
            var customerGroups = [];
            this.multiselectCustomerGroup.value().forEach(function (item, i, arr) {
                customerGroups.push({"customer_group_id": arr[i]});

            });

            var jsonData = customerGroups.length ? JSON.stringify(customerGroups) : "";
            registry.get(this.entityProvider).set(this.entityDataScope + '.customer_group', jsonData);
            return customerGroups;
        },

        /**
         * save store view
         */
        saveStoreView: function () {
            var storeViews = [];
            this.multiselectStoreView.value().forEach(function (item, i, arr) {
                storeViews.push({"customer_store_id": arr[i]});

            });

            var jsonData = storeViews.length ? JSON.stringify(storeViews) : "";
            registry.get(this.entityProvider).set(this.entityDataScope + '.store_view', jsonData);
            return storeViews;
        },

        /**
         * Update button status
         *
         * @param customerGroups
         * @param storeViews
         * @param disabled
         */
        updateButtonStatus: function (customerGroups, storeViews, disabled) {
            if ((customerGroups && customerGroups.length > 0)
                || (storeViews && storeViews.length > 0)
                || (disabled && disabled == '1')
            ) {
                $('*[data-name="' + this.buttonName + '"]').addClass('active');
            } else {
                $('*[data-name="' + this.buttonName + '"]').removeClass('active');
            }
        }
    });
});
