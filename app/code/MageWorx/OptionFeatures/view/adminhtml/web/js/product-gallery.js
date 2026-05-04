/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint jquery:true*/
define([
    'jquery',
    'underscore',
    'mage/template',
    'uiRegistry',
    'jquery-ui-modules/widget',
    'baseImage',
    'productGallery'
], function ($, _, mageTemplate, registry) {
    'use strict';

    // Extension for mage.productGallery - Add advanced settings block
    $.widget('mage.mageworxProductGallery', $.mage.productGallery, {

        /**
         * Set image as main
         * @param {Object} imageData
         * @private
         */
        setBase: function (imageData) {
            return;
        }
    });

    return $.mage.mageworxProductGallery;
});
