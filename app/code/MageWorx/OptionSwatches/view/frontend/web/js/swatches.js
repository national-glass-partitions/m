/**
 * Copyright © 2017 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
define(
    [
        'jquery',
        'underscore',
        'mage/translate',
        'Magento_Catalog/js/price-utils',
        'jquery/validate',
        'jquery-ui-modules/widget',
        'jquery/jquery.parsequery'
    ],
    function ($, _, $t, priceUtils) {
        'use strict';

        $.widget('mageworx.optionSwatches', {
            options: {
                hiddenSelectClass: 'mageworx-swatch',
                optionClass: 'mageworx-swatch-option'
            },

            /**
             * Triggers one time at first run (from base.js)
             * @param optionConfig
             * @param productConfig
             * @param base
             * @param self
             */
            firstRun: function firstRun(optionConfig, productConfig, base, self)
            {
                this._observeStyleOptions();
                this._grayoutDisabledOptions();
                this._initEventListener();
                this._validateRequiredSwatches();
            },

            /**
             * Observe style changes of select to show/hide swatch divs
             * Example: OptionDependency hides child option - divs must be unchecked and hidden
             */
            _observeStyleOptions: function ()
            {
                var self = this,
                    target = $('.' + this.options.optionClass).parent().next('select').find('option');

                //in case of cart reconfigure
                $.each(target, function () {
                    self.processSwatchLabel($(this));
                });

                var observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutationRecord) {
                        self._toggleSwatch($(mutationRecord.target));
                    });
                });

                $.each(target, function (i, e) {
                    observer.observe(e, {attributes: true, attributeFilter: ['style']});
                });
            },

            /**
             * Disable swatch image if the corresponding option value is disabled.
             */
            _grayoutDisabledOptions: function ()
            {
                $('.' + this.options.optionClass).each(function () {
                    var el = $(this),
                        optionId = el.attr('data-option-id'),
                        optionValueId = el.attr('data-option-type-id');
                    var optionValue = $('#select_' + optionId + ' option[value="' + optionValueId + '"]');

                    if (optionValue.prop('disabled')) {
                        el.addClass('disabled');
                    }
                });
            },

            /**
             * Show/hide swatch divs
             * @param $selectOption
             */
            _toggleSwatch: function ($selectOption)
            {
                var $swatch = $('[data-option-type-id="' + $selectOption.val() + '"]');
                this.processSwatchLabel($selectOption, $swatch);
                if ($selectOption.css('display') == 'block' || $selectOption.css('display') == 'inline') {
                    $swatch.parent().css('display', 'inline-block');
                } else if ($selectOption.css('display') == 'none') {
                    $swatch.parent().css('display', 'none');
                    $swatch.removeClass('selected');
                }
            },

            /**
             * Process swatch div according to hidden select changes
             * @param $selectOption
             */
            processSwatchLabel: function ($selectOption)
            {
                var $select = $selectOption.parents('select');
                var optionId = priceUtils.findOptionId($select);
                var selectOptions = $('#select_' + optionId + ' option');
                if (!selectOptions) {
                    return;
                }

                var optionLabel = $select.parents('.field').find('label');
                if (optionLabel.parent().find('span#value').length <= 0) {
                    optionLabel.after('<span id="value"></span>');
                }

                var labelText = [];
                var isSelectedOptionExist = false;
                var inArrayValue = -1;
                if ($select.val()) {
                    $(selectOptions).each(function () {
                        if (Array.isArray($select.val())) {
                            inArrayValue = $.inArray($(this).attr('value'), $select.val());
                        }
                        if (inArrayValue !== -1 || $select.val() === $(this).attr('value')) {
                            isSelectedOptionExist = true;
                            var $swatch = $("[data-option-type-id='" + $(this).attr('value') + "']");
                            $swatch.addClass('selected');
                            if ($swatch.attr('data-option-price') >= 0) {
                                labelText.push($(this).text());
                            } else {
                                labelText.push($swatch.attr('data-option-label'));
                            }
                        }
                    });
                }
                var $el = optionLabel.parent().find('span#value');
                if (isSelectedOptionExist === false) {
                    $el.html('');
                } else {
                    $el.html(' - ' + labelText.join(', '));
                }
            },

            /**
             * Triggers each time when option is updated\changed (from the base.js)
             * @param option
             * @param optionConfig
             * @param productConfig
             * @param base
             */
            update: function update(option, optionConfig, productConfig, base) {
                if ($(option).hasClass(this.options.hiddenSelectClass)) {
                    if ($(option).val() == '') {
                        $(option).parent().parent().find('.selected').removeClass('selected');
                        $(option).parents('.field').find('label').parent().find('span#value').html('');
                    }

                    var optionId = priceUtils.findOptionId(option);
                    var $selectOption = $('#select_' + optionId + ' option').first();
                    this.processSwatchLabel($selectOption);
                }
            },

            /**
             * Initialize event listener for swatch div's click
             */
            _initEventListener: function ()
            {
                var self = this;

                $('body').on('click', '.' + this.options.optionClass, function () {
                    self._onClick(this);
                });

                $('body').on('keydown', '.' + this.options.optionClass, function (e) {
                    if (e.keyCode === 32 || e.keyCode === 13) {
                        document.activeElement.click();
                        e.preventDefault();
                    }
                });
            },

            /**
             * Click event for swatch div
             * Process all needed actions for hidden select
             * @param option
             */
            _onClick: function (option)
            {
                if ($(option).hasClass('disabled')) {
                    return;
                }

                var self = this;
                var optionId = $(option).attr('data-option-id');
                var optionValueId = $(option).attr('data-option-type-id');
                var optionType = $(option).attr('data-option-type');
                var select = $('#select_' + optionId);
                var selectOptions = $('#select_' + optionId + ' option');
                if (!selectOptions) {
                    return;
                }

                if ($(option).parents('.field').find('label').parent().find('span#value').length <= 0) {
                    $(option).parents('.field').find('label').after('<span id="value"></span>');
                }
                $(selectOptions).each(function () {
                    if (optionType === 'multiple') {
                        if ($(this).val() == optionValueId) {
                            if ($(option).hasClass('selected')) {
                                if (_.contains($(select).val(), $(this).attr('value'))) {
                                    var notRemoved = $(select).val().filter(function(value, index, arr){
                                        return value != optionValueId;
                                    });
                                    if (notRemoved.length < 1) {
                                        $(select).val('');
                                    } else {
                                        $(select).val(notRemoved);
                                    }
                                }
                                $(option).removeClass('selected');
                            } else {
                                if (!$(select).val()) {
                                    $(select).val(optionValueId)
                                } else {
                                    var values = $(select).val();
                                    values.push(optionValueId);
                                    $(select).val(values);
                                }
                                $(option).addClass('selected');
                            }
                            $(select).trigger('change');
                            return;
                        }
                    } else {
                        if ($(this).val() == optionValueId) {
                            var selectdOptionElement = $(option).parent().parent().find('.selected');

                            if ($(option).hasClass('selected')) {
                                $(select).val('');
                                $(option).parents('.field').find('label').parent().find('span#value').html('');
                                selectdOptionElement.removeClass('selected');

                                // for ADA support
                                if ($(option).attr('aria-checked')) {
                                    $(option).attr('aria-checked', 'false');
                                }

                            } else {
                                $(select).val(optionValueId);
                                var $el = $(option).parents('.field').find('label').parent().find('span#value');
                                $el.html(' - ' + $(option).attr('data-option-label'));
                                if ($(option).attr('data-option-price') > 0) {
                                    $el.html($el.html() + ' +' + priceUtils.formatPrice($(option).attr('data-option-price')));
                                }

                                // for ADA support
                                if (!!$(option).attr('aria-checked')) {
                                    $(option).attr('aria-checked', 'true');
                                }
                                selectdOptionElement.attr('aria-checked', 'false');
                                selectdOptionElement.removeClass('selected');
                                $(option).addClass('selected');
                            }
                            $(select).trigger('change');
                            return;
                        }
                    }
                });
            },

            /**
             * Validator for required swatch options
             */
            _validateRequiredSwatches: function ()
            {
                var self = this;
                $('#product_addtocart_form').mage('validation', {
                    ignore: ':hidden:not(.' + self.options.hiddenSelectClass + ')',
                    radioCheckboxClosest: '.nested',
                    submitHandler: function (form) {
                        var widget = $(form).catalogAddToCart({
                            bindSubmit: false
                        });
                        widget.catalogAddToCart('submitForm', $(form));
                        return false;
                    }
                });
            }
        });

        return $.mageworx.optionSwatches;
    }
);
