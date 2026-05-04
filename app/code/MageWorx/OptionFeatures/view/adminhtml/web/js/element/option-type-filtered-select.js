/**
 * Copyright © 2018 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'MageWorx_OptionFeatures/js/element/selectable-filtered-select'
], function (filteredSelect) {
    'use strict';

    /**
     * Extend base select element. Adds filtration (toggle view) based on the option type selected.
     * Used in the: \MageWorx\OptionFeatures\Ui\DataProvider\Product\Form\Modifier\Features
     * for the Image Mode select and Option Gallery Display Mode select
     */
    return filteredSelect.extend({

        /**
         * List of valid option types (show element if they are selected for the current option)
         */
        availableTypes: [
            'drop_down',
            'radio',
            'checkbox'
        ]
    });
});
