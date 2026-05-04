/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/button',
    'uiRegistry',
    'underscore'
], function (Button, registry, _) {
    'use strict';

    return Button.extend({

        /**
         * Initializes component.
         *
         * @returns {Object} Chainable.
         */
        initialize: function () {
            return this._super()
                ._setClasses()
                ._setButtonClasses();
        },

        /**
         * Extends 'buttonClasses' object.
         *
         * @returns {Object} Chainable.
         */
        _setButtonClasses: function () {
            var additional = this.buttonClasses;
            var mageworxAttributes = this.mageworxAttributes;

            if (_.isString(additional)) {
                this.buttonClasses = {};

                if (additional.trim().length) {
                    additional = additional.trim().split(' ');

                    additional.forEach(function (name) {
                        if (name.length) {
                            this.buttonClasses[name] = true;
                        }
                    }, this);
                }

                delete mageworxAttributes['__disableTmpl'];

                if (!_.isUndefined(mageworxAttributes)) {
                    Object.values(mageworxAttributes).forEach(function (mageworxAttribute) {
                        var mageworxAttributeData = registry.get(this.provider).get(mageworxAttribute);
                        if (!_.isUndefined(mageworxAttributeData)
                            && mageworxAttributeData !== ''
                            && mageworxAttributeData !== null
                            && mageworxAttributeData.replace(/^[0.]+/, "")
                        ) {
                            this.buttonClasses['active'] = true;
                        }
                    }, this);
                }
            }

            _.extend(this.buttonClasses, {
                'action-basic': !this.displayAsLink,
                'action-additional': this.displayAsLink
            });

            return this;
        },

        /**
         * Apply action on target component,
         * but previously create this component from template if it is not existed
         *
         * @param {Object} action - action configuration
         */
        applyAction: function (action) {
            var targetName = action.targetName,
                params = action.params || [],
                actionName = action.actionName,
                target;

            if (!registry.has(targetName)) {
                this.getFromTemplate(targetName);
            }
            target = registry.async(targetName);

            if (target && typeof target === 'function' && actionName) {
                if (params.length > 1) {
                    params.shift();
                }
                params.unshift(actionName);
                target.apply(target, params);
            }
        }
    });
});
