define([
    'jquery',
    'underscore',
    'jquery-ui-modules/widget',
    'jquery/validate',
    'mage/translate'
], function($, _){
    'use strict';
    return function(config) {
        var msg = ' Step is ',
            step = '',
            /** @inheritdoc */
            messager = function () {
                return $.mage.__(msg) + step;
            };

        $.validator.addMethod(
            "mageworx-dynamic-option-step-rule",
            function(value, element) {
                var name = element.name;
                var optionId = name.replace("options[",'').replace("]", '');

                if (_.isUndefined(config.config['options_data'][optionId])) {
                    return true;
                }

                var $container = $(element).closest('.field');
                if ($container.length > 0 && $container.css('display') === 'none') {
                    return true;
                }

                step = parseFloat(config.config['options_data'][optionId]['step']);

                if (!step) {
                    return true;
                }

                return Math.round(value * 100) % Math.round(step * 100) === 0 && (value * 100) % 1 === 0;
            },
            messager
        );
    }
});
