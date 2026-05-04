/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'jquery/colorpicker/js/colorpicker'
], function ($) {
    'use strict';

    return function (config, element) {
        var $element = $(element),
            $cpPlaceholder = $element.find('#color-picker-button'),
            submitUrl = config.uploader_url,
            hexStorage,
            $uploaderEl = $('#optionfeatures_media_gallery_content').find('.uploader');

        $element.ColorPicker({
            color: "",
            onChange: function (hsb, hex, rgb) {
                $element.css("backgroundColor", "#" + hex).val("#" + hex);
                hexStorage = hex;
            },
            onShow: function () {
                $cpPlaceholder.hide();
                $element.css('border', '1px solid #ccc');
            },
            onSubmit: function () {
                $.ajax(submitUrl, {
                    data: {'hex': hexStorage},
                    success: function (data) {
                        var parsedData = JSON.parse(data);
                        $uploaderEl.trigger('addItem', parsedData);
                    },
                    error: function (e) {
                        console.log('Error');
                        console.log(e);
                    }
                });
            }
        });
    };
});
