define([
    'jquery',
    'underscore',
    'jquery/validate',
    'mage/translate'
], function($, _){
    'use strict';
    return function(config) {
        $.validator.addMethod(
            "mageworx-selection-limit",
            function(value, element) {

                var optionId = element.name.replace( /(^.+\D)(\d+)(\D.+$)/i,'$2');

                if (_.isUndefined(config.config[optionId])) {
                    return true;
                }

                var $container = $(element).closest('.field');
                if ($container.length > 0 && $container.css('display') === 'none') {
                    return true;
                }

                var selectionLimitFrom = parseInt(config.config[optionId]['selection_limit_from']);
                var selectionLimitTo = parseInt(config.config[optionId]['selection_limit_to']);

                if (!selectionLimitFrom && !selectionLimitTo) {
                    return true;
                }

                var selectionCounter = 0;
                if (element.type === 'checkbox') {
                    selectionCounter = $('input[name="' + element.name + '"]:checked').length;
                } else if (!_.isNull(value)) {
                    selectionCounter = value.length;
                }

                return selectionLimitFrom <= selectionCounter && (!selectionLimitTo || selectionLimitTo >= selectionCounter);
            },
            $.mage.__("Please, choose required number of values.")
        );
    }
});