/**
 * Copyright © MageWorx. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiRegistry',
    'jquery',
    'underscore',
    'MageWorx_OptionBase/component/abstract-modal-component',
], function (registry, $, _, ModalComponent) {
    'use strict';

    return ModalComponent.extend({

        defaults: {
            pathModal: 'value_settings_modal.content.fieldset'
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
            this.isWeightEnabled = params.isWeightEnabled;
            this.isCostEnabled = params.isCostEnabled;
            this.isNotConfigurableProduct = params.isNotConfigurableProduct;
            this.isLoadLinkedProductEnabled = params.isLoadLinkedProductEnabled;
            this.pathLoadLinkedProduct = params.pathLoadLinkedProduct;
            if (this.entityProvider === 'catalogstaging_update_form.catalogstaging_update_form_data_source') {
                this.isSchedule = true;
            }
            this.formName = params.formName;
            this.registryEntityProvider = registry.get(this.entityProvider);
            this.currentDataScopeParams = this.registryEntityProvider.get(this.entityDataScope);
            this.isValidSku = this.currentDataScopeParams.sku_is_valid === '1';
            this.linkedFields = !_.isUndefined(this.registryEntityProvider.get('data.product.option_link_fields'))
                ? this.registryEntityProvider.get('data.product.option_link_fields')
                : {};
        },

        /**
         * Initialize fields
         */
        initFields: function () {
            if (this.isCostEnabled) {
                var cost = registry
                    .get(this.entityProvider)
                    .get(this.entityDataScope).cost;
                this.costField = registry.get(
                    this.formName + '.' + this.formName + '.' + this.pathModal + '.cost'
                );
                this.costField.value(cost);
                if (!_.isUndefined(this.linkedFields.cost)) {
                    this.costField.disabled(this.isValidSku);
                }
            }

            if (this.isWeightEnabled) {
                var weight = registry
                    .get(this.entityProvider)
                    .get(this.entityDataScope).weight;
                this.weightField = registry.get(
                    this.formName + '.' + this.formName + '.' + this.pathModal + '.weight'
                );
                this.weightField.value(weight);
                if (!_.isUndefined(this.linkedFields.weight)) {
                    this.weightField.disabled(this.isValidSku);
                }

                var weightType = registry
                    .get(this.entityProvider)
                    .get(this.entityDataScope).weight_type;
                weightType = weightType ? weightType : 'fixed';
                this.weightTypeField = registry.get(
                    this.formName + '.' + this.formName + '.' + this.pathModal + '.weight_type'
                );
                this.weightTypeField.value(weightType);
                if (!_.isUndefined(this.linkedFields.weight)) {
                    this.weightTypeField.disabled(this.isValidSku);
                }
            }
            if (this.isNotConfigurableProduct) {
                this.initField('qty_multiplier');
            }

            if (this.isLoadLinkedProductEnabled) {
                this.isLoadLinkedProductCheckbox = registry.get(
                    this.formName + '.' + this.formName + '.' + this.pathModal + '.' + this.pathLoadLinkedProduct
                );
                var isSwatch = 0,
                    registryOptionDataScope = this.registryEntityProvider.get(this.entityDataScope.split('.values.')[0]);

                if (registryOptionDataScope !== 'undefined') {
                    isSwatch = parseFloat(registryOptionDataScope.is_swatch);
                }

                if (isSwatch === 0) {
                    this.isLoadLinkedProductCheckbox.hide();
                } else {
                    var loadLinkedProductCheckboxStatus = this.registryEntityProvider.get(this.entityDataScope +
                        '.' + this.pathLoadLinkedProduct);

                    this.isLoadLinkedProductCheckbox.checked(loadLinkedProductCheckboxStatus === '1');
                    this.isLoadLinkedProductCheckbox.show();
                }
            }
        },

        /**
         * Save data before close modal, update button status
         */
        saveData: function () {
            if (this.isCostEnabled) {
                this.processDataItem('cost', this.conditionGreaterThanZero);
            }
            if (this.isWeightEnabled) {
                this.processDataItem('weight', this.conditionGreaterThanZero);
                this.processDataItem('weight_type', this.conditionNonEmptyString);
            }

            if (this.isNotConfigurableProduct) {
                this.processDataItem('qty_multiplier', this.conditionGreaterThanZero);
            }

            if (this.isLoadLinkedProductEnabled) {
                this._super();
                var self = this,
                    isDefaultValue = this.entityDataScope + '.is_default';
                self.isDefault = registry.get("ns = " + this.ns + ", dataScope = " + isDefaultValue);

                if (this.isLoadLinkedProductCheckbox.checked()) {
                    self.isDefault.disabled(true);
                    self.isDefault.checked(false);
                    if (this.currentDataScopeParams.sku === this.registryEntityProvider.data.product.sku) {
                        self.isDefault.checked(true);
                    }
                } else {
                    self.isDefault.disabled(false);
                }
                this.processDataItem('load_linked_product', this.conditionGreaterThanZero);
            }
        }
    });
});
