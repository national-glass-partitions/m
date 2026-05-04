define([
    'jquery',
    'underscore',
    'jquery-ui-modules/widget',
    'jquery/validate',
    'mage/translate'
], function($, _){
    'use strict';
    return function(config) {
        var msg = 'Max. value is ',
            maxValue = '',
            /** @inheritdoc */
            messager = function () {
                return $.mage.__(msg) + maxValue;
            };

        $.validator.addMethod(
            "mageworx-dynamic-option-max-value-rule",
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

                maxValue = parseFloat(config.config['options_data'][optionId]['max_value']);

                if (!maxValue) {
                    return true;
                }

                return value <= maxValue;
            },
            messager
        );
    }
});
