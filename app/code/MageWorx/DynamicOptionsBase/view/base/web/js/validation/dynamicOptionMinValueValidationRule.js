define([
    'jquery',
    'underscore',
    'jquery-ui-modules/widget',
    'jquery/validate',
    'mage/translate'
], function($, _){
    'use strict';
    return function(config) {
        var msg = 'Min. value is ',
            minValue = '',
            /** @inheritdoc */
            messager = function () {
                return $.mage.__(msg) + minValue;
            };

        $.validator.addMethod(
            "mageworx-dynamic-option-min-value-rule",
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

                minValue = parseFloat(config.config['options_data'][optionId]['min_value']);

                if (!minValue) {
                    return true;
                }

                return value >= minValue;
            },
            messager
        );
    }
});
