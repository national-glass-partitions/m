/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'MageWorx_OptionBase/dynamicRows22x'
], function (dynamicRows22x) {
    'use strict';
    
    if (!Array.prototype.last) {
        Array.prototype.last = function () {
            return this[this.length - 1];
        }
    }

    return dynamicRows22x;
});