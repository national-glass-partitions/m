/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({

        /**
         * {@inheritdoc}
         */
        setInitialValue: function () {
            this._super();

            this.addBefore(this.addbefore);

            return this;
        },

        /**
         * {@inheritdoc}
         */
        initObservable: function () {
            this._super();

            this.observe('addBefore');

            return this;
        },

        /**
         * Set 'disabled' attribute to field linked by SKU
         *
         * @param skuIsValid
         */
        setDisabled: function (skuIsValid) {
            if (skuIsValid == 1) {
                this.disabled(true);
            }
        }
    });
});