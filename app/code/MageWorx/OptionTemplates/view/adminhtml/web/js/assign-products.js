/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'mage/adminhtml/grid'
], function (jQuery) {
    'use strict';

    return function (config) {
        var selectedProducts = config.selectedProducts,
            products = $H(selectedProducts),
            gridJsObject = window[config.gridJsObjectName],
            tabIndex = 1000,
            fieldId = config.fieldId;

        $(fieldId).value = Object.toJSON(products);

        /**
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerProducts(grid, element, checked) {
            if (checked) {
                if (element.value !== 'on') {
                    products.set(element.value, element.value);
                }
            } else {
                if (element.value !== 'on') {
                    products.unset(element.value);
                }
            }
            $(fieldId).value = Object.toJSON(products);
            var selectedIds = [];
            var selectedCounter = 0;
            products.each(function(item) {
                if (typeof item.value !== 'function') {
                    selectedIds.push(item.key);
                    selectedCounter += 1;
                }
            });
            jQuery('#optiontemplates_group_products_massaction-count')
                .find(("strong[data-role='counter']"))
                .html(selectedCounter);
            grid.reloadParams = {
                'selected_products[]': selectedIds
            };
        }

        /**
         *
         * @param {Object} grid
         * @param {String} event
         */
        function productRowClick(grid, event) {
            var trElement = Event.findElement(event, 'tr'),
                isInput = Event.element(event).tagName === 'INPUT',
                checked = false,
                checkbox = null;

            if (trElement) {
                checkbox = Element.getElementsBySelector(trElement, 'input');

                if (checkbox[0]) {
                    checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                    gridJsObject.setCheckboxChecked(checkbox[0], checked);
                }
            }
        }

        /**
         *
         * @param {Object} grid
         * @param {String} row
         */
        function productsRowInit(grid, row) {
            var checkbox = $(row).getElementsByClassName('checkbox')[0],
                position = $(row).getElementsByClassName('input-text')[0];

            if (checkbox && position) {
                checkbox.positionElement = position;
                position.checkboxElement = checkbox;
                position.disabled = !checkbox.checked;
                position.tabIndex = tabIndex++;
            }
        }

        gridJsObject.checkboxCheckCallback = registerProducts;
        gridJsObject.initRowCallback = productsRowInit;
        gridJsObject.rowClickCallback = productRowClick;

        if (gridJsObject.rows) {
            gridJsObject.rows.each(function (row) {
                productsRowInit(gridJsObject, row);
            });
        }
    };
});
