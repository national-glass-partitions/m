/**
 * Copyright © 2018 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/fieldset',
    'underscore'
], function (Fieldset, _) {
    'use strict';

    return Fieldset.extend({

        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         *
         * @returns {Object} Reference to instance
         */
        initObservable: function () {
            this._super().observe(true, 'label');
            return this;
        }
    });
});
