/**
 * Scommerce InfiniteScrolling js script
 *
 * @category   Scommerce
 * @package    Scommerce_InfiniteScrolling
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
define([
    "jquery",
    "jqueryIas",
    "infinitescrolling"
], function ($, jqueryIas, infinitescrolling) {
    "use strict";


    window.SgyIAS = {
        debug: window.iasConfig.debug,
        init: function () {
            jQuery(function ($) {

                var result = window.iasConfig.mode.split('.');
                var isClass;
                $.each(result, function (key, val) {
                    if (val) {
                        if ($(".main").find("." + val).length > 0) {
                            isClass = true;
                            return false;

                        }
                    }
                });

                $.fn.isOnScreen = function () {

                    var win = $(window);

                    var viewport = {
                        top: win.scrollTop(),
                        left: win.scrollLeft()
                    };
                    viewport.right = viewport.left + win.width();
                    viewport.bottom = viewport.top + win.height();

                    var bounds = this.offset();
                    bounds.right = bounds.left + this.outerWidth();
                    bounds.bottom = bounds.top + this.outerHeight();

                    return (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));

                };


                if (isClass) {
                    var config = {
                        item: window.iasConfig.mode,
                        container: window.iasConfig.container,
                        pagination: window.iasConfig.pagination,
                        delay: '',
                        spinner: {
                            html: window.iasConfig.spinnerHtml
                        },
                        trigger: {
                            text: window.iasConfig.trigger.text,
                            html: window.iasConfig.trigger.html,
                            textPrev: window.iasConfig.trigger.textPrev,
                            htmlPrev: window.iasConfig.trigger.htmlPrev,
                            offset: window.iasConfig.trigger.offset
                        }
                    };

                    if (window.iasConfigCustom) {
                        $.extend(config, window.iasConfigCustom);
                    }

                    window.ias = $.ias(config);

                    window.ias.extension(new IASPagingExtension());

                    if (typeof (IASSpinnerExtension) !== "undefined")
                        window.ias.extension(new IASSpinnerExtension(config.spinner));


                    window.ias.extension(new IASNoneLeftExtension(config.noneleft));
                    window.ias.extension(new IASTriggerExtension(config.trigger));

                    window.ias.on('pageChange', function (pageNum, scrollOffset, url) {
                        $('#navbar-current').text(pageNum);
                    });

                    window.ias.on('scroll', function (items) {
                        $('img.lazy').each(function () {
                            var _this = $(this);
                            if (_this.isOnScreen()) {
                                _this.removeClass('swatch-option-loading');
                            }
                        });
                    });

                    window.ias.on('load', function (event) {
                        event.ajaxOptions.cache = true;

                    });

                    window.ias.on('rendered', function () {
                        $("[data-role='tocart-form'], .form.map.checkout").attr('data-mage-init', JSON.stringify({'catalogAddToCart': {}}));
                        $('body').trigger('contentUpdated');
                    });
                    
                    /*
                    window.ias.on('ready', function () {
                        var last = $.ias().getLastItem();
                        if (last.length == 0) {
                            $('.infinite-scrolling-bar').css('display', 'none');
                        } else {

                            $('.infinite-scrolling-bar').css('display', 'block');
                            $('.infinite-scrolling-bar').css({"background-color": window.iasConfig.buttonLabelBackground, "color": window.iasConfig.labelColor});

                        }
                    }); 
                     **/

                    $(document).trigger("infiniteScrollReady", [window.ias]);
                } else {
                    $('.infinite-scrolling-bar').css('display', 'none');
                }
            });

        }
    };
});
