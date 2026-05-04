/**
 * Copyright © MageWorx. All rights reserved.
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
            formName: '',
            buttonName: '',
            isActiveButton: false,
            isSchedule: false,
            entityProvider: '',
            entityDataScope: '',
            pathModal: '',
            conditionGreaterThanZero: 'greater-than-zero',
            conditionNonEmptyString: 'non-empty-string',
            conditionNonZero: 'non-zero'
        },

        /**
         * Reload modal
         *
         * @param params
         */
        reloadModal: function (params) {
            this.initVariables(params);
            this.initFields();
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
        },

        /**
         * Initialize fields
         */
        initFields: function () {},

        /**
         * Initialize field value by key
         *
         * @param key
         */
        initField: function (key) {
            var value = registry
                .get(this.entityProvider)
                .get(this.entityDataScope + '.' + key);
            var field = registry.get(
                this.formName + '.' + this.formName + '.' + this.pathModal + '.' + key
            );
            field.value(value);
        },

        /**
         * save and close modal
         */
        save: function () {
            this.valid = true;
            this.elems().forEach(this.validate, this);
            if (this.valid) {
                this.isActiveButton = false;
                this.saveData();
                this.updateButtonIcon();
                this.toggleModal();
            }
        },

        /**
         * Save data before close modal
         */
        saveData: function () {},

        /**
         * Save data item by key, change button status
         *
         * @param key
         * @param conditionRule
         */
        processDataItem: function (key, conditionRule) {
            var field = registry.get(
                this.formName + '.' + this.formName + '.' + this.pathModal + '.' + key
            );
            var value = field.value();

            registry
                .get(this.entityProvider)
                .set(this.entityDataScope + '.' + key, value);

            this.updateButtonStatus(value, conditionRule);
        },

        /**
         * Update button status
         *
         * @param value
         * @param conditionRule
         */
        updateButtonStatus: function (value, conditionRule) {
            if (conditionRule === this.conditionGreaterThanZero && value > 0) {
                this.isActiveButton = true;
            } else if (conditionRule === this.conditionNonEmptyString && value !== '') {
                this.isActiveButton = true;
            } else if (conditionRule === this.conditionNonZero && value !== '0' && value !== 0) {
                this.isActiveButton = true;
            }
        },

        /**
         * Update button icon
         */
        updateButtonIcon: function () {
            if (this.isActiveButton === true) {
                $('*[data-name="' + this.buttonName + '"]').addClass('active');
            } else {
                $('*[data-name="' + this.buttonName + '"]').removeClass('active');
            }
        }
    });
});
