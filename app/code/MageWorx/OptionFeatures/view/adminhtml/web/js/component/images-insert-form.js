/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/components/insert-form',
    'uiRegistry',
    'mage/translate'
], function ($, InsertForm, registry, $t) {
    'use strict';

    return InsertForm.extend({

        defaults: {
            buttonName: ''
        },

        saveImagesData: function () {
            var serializedForm = $('#optionfeatures_media_gallery_content :input').serialize();
            var optionfeatures_provider = $('#optionfeatures_provider').val();
            var optionfeatures_datascope = $('#optionfeatures_datascope').val() + '.images_data';
            this.updateButtonStatus(serializedForm);
            registry.get(optionfeatures_provider).set(optionfeatures_datascope, serializedForm);
        },

        loadImagesData: function (params) {
            var optionValue = registry.get(params.provider).get(params.dataScope);
            var option = registry.get(params.provider).get(this._getOptionScope(params.dataScope));
            var imagesData = '';
            this.buttonName = params.buttonName;

            if (optionValue.images_data != '') {
                imagesData = optionValue.images_data;
            }
            var self = this;
            $.ajax({
                type: 'POST',
                url: params.loadImageUrl,
                data: {
                    data: imagesData,
                    form_key: FORM_KEY,
                    option_type_id: optionValue.option_type_id,
                    option_type: option.type,
                    form_name: params.formName
                },
                beforeSend: function () {
                    $('#option_value_image_container').html('');
                    $('body').trigger('processStart');
                },
                dataType: 'json'
            }).done(function (data) {
                if (!data.error) {
                    if (!$('#option_value_image_container').length) {
                        var i = 0;
                        var target = document.querySelector('.' + params.formName + '_' + params.formName + '_option_value_images_modal');
                        var observer = new MutationObserver(function (mutations) {
                            mutations.forEach(function (mutation) {
                                if ($('#option_value_image_container').length && i === 0) {
                                    i++;
                                    self._setLabelHtml(option, optionValue);
                                    self._htmlImagesData(params, data);
                                    observer.disconnect();
                                }
                            });
                        });
                        var config = {childList: true, subtree: true};
                        observer.observe(target, config);
                    } else {
                        self._setLabelHtml(option, optionValue);
                        self._htmlImagesData(params, data);
                    }
                } else {
                    $('body').trigger('processStop');
                    alert(data.error);
                }
            }).fail(
                function () {
                    $('body').trigger('processStop');
                    alert($t('Something goes wrong'));
                }
            );
        },

        _getOptionScope: function (optionValueScope) {
            var splitResult = optionValueScope.split('.values');
            return splitResult[0];
        },

        _htmlImagesData: function (params, data) {
            $('#optionfeatures_provider').val(params.provider);
            $('#optionfeatures_datascope').val(params.dataScope);
            $('#option_value_image_container').html(data.result);
            $('body').trigger('processStop');
        },

        _setLabelHtml: function (option, optionValue) {
            var fieldsetLabelSpan = $("div").find("[data-index='images_data'] > div > strong > span").first();
            var optionValueLabel = $t('Option') + ' "' + option.title + '" - "' + optionValue.title + '"';
            fieldsetLabelSpan.html(optionValueLabel);
        },

        /**
         * Update button status
         *
         * @param serializedForm
         */
        updateButtonStatus: function (serializedForm) {
            if (serializedForm) {
                $('*[data-name="' + this.buttonName + '"]').addClass('active');
            } else {
                $('*[data-name="' + this.buttonName + '"]').removeClass('active');
            }
        }
    });
});
