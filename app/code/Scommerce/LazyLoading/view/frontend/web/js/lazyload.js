/**
 * Scommerce LazyLoading js script
 *
 * @category   Scommerce
 * @package    Scommerce_LazyLoading
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */
define([
    'jquery',
    'Scommerce_LazyLoading/js/jquery.lazyload'
], function ($) {
    return function (config) {
        $(function () {
            var options = {
                threshold: -50
            };
            $("img.lazy").lazyload(options);
            $("img.lazy").one("appear", function () {
                var _this = $(this);
                setTimeout(function () {
                    _this.removeClass('swatch-option-loading');
                }, 100);
            });
        });
    };
});
