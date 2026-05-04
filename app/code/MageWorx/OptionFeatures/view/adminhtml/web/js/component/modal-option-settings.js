/**
 * Copyright © MageWorx. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiRegistry',
    'jquery',
    'underscore',
    'MageWorx_OptionBase/component/abstract-modal-component'
], function (registry, $, _, ModalComponent) {
    'use strict';

    return ModalComponent.extend({

        defaults: {
            pathModal: 'option_settings_modal.content.fieldset'
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
            this.isEnabledHideProductPageValuePrice = params.isEnabledHideProductPageValuePrice;
            this.pathHideValuePrice = params.pathHideValuePrice;
        },

        /**
         * Initialize fields
         */
        initFields: function () {
            this.initField('mageworx_option_image_mode');
            this.initField('mageworx_option_gallery');
            this.initField('div_class');

            var optionType = registry
                .get(this.entityProvider)
                .get(this.entityDataScope + '.type');

            var selectionLimitFromField = registry.get(
                this.formName + '.' + this.formName + '.' + this.pathModal + '.' + 'selection_limit_from'
            );
            var selectionLimitToField = registry.get(
                this.formName + '.' + this.formName + '.' + this.pathModal + '.' + 'selection_limit_to'
            );
            if (optionType !== 'multiple' && optionType !== 'checkbox') {
                selectionLimitFromField.hide();
                selectionLimitToField.hide(true);
            } else {
                selectionLimitFromField.show();
                this.initField('selection_limit_from');
                selectionLimitToField.show();
                this.initField('selection_limit_to');
            }

            if (this.isEnabledHideProductPageValuePrice) {
                this.isHideValuePriceCheckbox = registry.get(
                    this.formName + '.' + this.formName + '.' + this.pathModal + '.' + this.pathHideValuePrice
                );
                this.isHideValuePriceCheckbox.show();
                this.initField('hide_product_page_value_price');
            }
        },

        /**
         * Save data before close modal, update button status
         */
        saveData: function () {
            this.processDataItem('mageworx_option_image_mode', this.conditionGreaterThanZero);
            this.processDataItem('mageworx_option_gallery', this.conditionGreaterThanZero);
            this.processDataItem('div_class', this.conditionNonEmptyString);
            this.processDataItem('selection_limit_from', this.conditionNonZero);
            this.processDataItem('selection_limit_to', this.conditionNonZero);

            if (this.isEnabledHideProductPageValuePrice) {
                this.processDataItem('hide_product_page_value_price', this.conditionGreaterThanZero);
            }
        }
    });
});
