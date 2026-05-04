/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/grid/columns/multiselect',
    'uiRegistry'
], function (jQuery, MultiSelect, uiRegistry) {
    'use strict';

    return MultiSelect.extend({

        /**
         * Initializes column component.
         *
         * @returns {Column} Chainable.
         */
        initialize: function () {
            this._super()
                .initFieldClass();

            var form = uiRegistry.get('mageworx_optiontemplates_group_form.mageworx_optiontemplates_group_form');
            var products = form.source.data.mageworx_optiontemplates_group.products;
            var parent = this;
            jQuery(products).each(function (i, t) {
                parent.select(t, false);
            });

            return this;
        },

        /**
         * Selects specified record.
         *
         * @param {*} id - See definition of 'getId' method.
         * @param {Boolean} [isIndex=false] - See definition of 'getId' method.
         * @returns {Multiselect} Chainable.
         */
        select: function (id, isIndex) {
            this._setSelection(id, isIndex, true);
            this.addToForm(id);
            return this;
        },

        /**
         * Deselects specified record.
         *
         * @param {*} id - See definition of 'getId' method.
         * @param {Boolean} [isIndex=false] - See definition of 'getId' method.
         * @returns {Multiselect} Chainable.
         */
        deselect: function (id, isIndex) {
            this._setSelection(id, isIndex, false);
            this.removeFromForm(id);
            return this;
        },

        /**
         * Toggles selection of a specified record.
         *
         * @param {*} id - See definition of 'getId' method.
         * @param {Boolean} [isIndex=false] - See definition of 'getId' method.
         * @returns {Multiselect} Chainable.
         */
        toggleSelect: function (id, isIndex) {
            var isSelected = this.isSelected(id, isIndex);
            this._setSelection(id, isIndex, !isSelected);
            if (isSelected) {
                this.removeFromForm(id);
            } else {
                this.addToForm(id);
            }

            return this;
        },

        addToForm: function (id) {
            var products = this.getProductsFromFromSource();
            var position = jQuery.inArray(id, products);
            if (position == -1) {
                products.push(id);
            }
        },

        removeFromForm: function (id) {
            var products = this.getProductsFromFromSource();
            var position = jQuery.inArray(id, products);
            if (position != -1) {
                delete products[position];
            }
        },

        getProductsFromFromSource: function () {
            var form = uiRegistry.get('mageworx_optiontemplates_group_form.mageworx_optiontemplates_group_form');
            try {
                var products = form.source.data.mageworx_optiontemplates_group.products;
            } catch (e) {
                products = [];
            }

            return products;
        },

        /**
         * Callback method to handle changes of selected items.
         *
         * @param {Array} selected - An array of currently selected items.
         */
        onSelectedChange: function (selected) {
            this.updateExcluded(selected)
                .countSelected()
                .updateState();
        },
    });
});
