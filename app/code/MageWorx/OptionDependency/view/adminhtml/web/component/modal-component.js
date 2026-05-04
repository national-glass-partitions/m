/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiRegistry',
    'underscore',
    'Magento_Ui/js/modal/modal-component',
    'jquery',
    'ko'
], function (registry, _, ModalComponent, $, ko) {
    'use strict';

    return ModalComponent.extend({

        defaults: {
            dependencyTree: '',
            dependencyType: '',
            entityProvider: '',
            entityDataScope: '',
            buttonName: '',
            savedDependencies: ''
        },

        /**
         * Open modal
         */
        openModal: function () {
            this._super();
            this.dependencyTree = registry.get('index = ' + this.indexies.dependency_tree);
            this.dependencyType = registry.get('index = ' + this.indexies.dependency_type);
        },

        /**
         * Close modal
         */
        closeModal: function () {
            this.clearDependencies();
            this._super()
        },

        /**
         * Reload modal
         *
         * @param params
         */
        reloadModal: function (params) {
            this.initVariables(params);
            var name = '';
            if (params.isProductPage) {
                name = registry.get(this.entityProvider).data.product.name;
            } else {
                name = registry.get(this.entityProvider).data.mageworx_optiontemplates_group.title;
            }

            this.dependencyTree.resetOptionsTree(name);
            this.initDependencyTree();
            this.initDependencyType();
            this.saveDependenciesBackup();
            this.dependencyTree.closeChildLevel();
        },

        /**
         * Initialize variables
         *
         * @param params
         */
        initVariables: function (params) {
            this.entityProvider = params.provider;
            this.entityDataScope = params.dataScope;
            this.buttonName = params.buttonName;
            this.dependencyTree.isEnabledTitleId = params.isEnabledTitleId;
        },

        /**
         * Initialize dependency tree
         */
        initDependencyTree: function () {
            var self = this,
                i = -1,
                isEmptyTree = true,
                options = registry.get(this.entityProvider).get(this._getOptionScope(this.entityDataScope)),
                currentOptionIndex = this._getCurrentOptionId(this.entityDataScope),
                currentOptionValueIndex = this._getCurrentOptionValueId(this.entityDataScope),
                isSchedule = false;

            if (this.entityProvider == 'catalogstaging_update_form.catalogstaging_update_form_data_source') {
                isSchedule = true;
            }

            _.each(options, function (optionItem) {
                i += 1;
                if (currentOptionIndex == i) {
                    var j = -1;
                    if (optionItem.values.length == 0) {
                        self.dependencyTree.setTitle([], optionItem);
                    }
                    _.each(optionItem.values, function (optionValueItem) {
                        j += 1;
                        if (currentOptionValueIndex == j) {
                            self.dependencyTree.setTitle(optionValueItem, optionItem);
                            return;
                        }
                    });
                    return;
                }
                if (_.isUndefined(optionItem.values) || optionItem.values.length < 1 || optionItem.is_delete == '1') {
                    return;
                }
                var valuesDeleted = true;
                _.each(optionItem.values, function (optionValueItem) {
                    if (optionValueItem.is_delete != '1') {
                        valuesDeleted = false;
                        return;
                    }
                });
                if (valuesDeleted) {
                    return;
                }
                var option = self.dependencyTree.addOption(optionItem, isSchedule);
                isEmptyTree = false;
                _.each(optionItem.values, function (optionValueItem) {
                    self.dependencyTree.addOptionValue(option, optionValueItem, optionItem, isSchedule);
                });
                self.dependencyTree.root[self.dependencyTree.separator]
                    ? self.dependencyTree.root[self.dependencyTree.separator].push(option)
                    : self.dependencyTree.root[self.dependencyTree.separator] = [option];
            });

            self.dependencyTree.pushRoot();
            if (isEmptyTree) {
                self.dependencyTree.addNoOptionsMessage();
            } else {
                self.dependencyTree.initOptions();
            }
        },

        /**
         * Initialize dependency type
         */
        initDependencyType: function () {
            this.dependencyType.value(registry.get(this.entityProvider).get(this.entityDataScope).dependency_type);
        },

        /**
         * Save dependencies backup to revert changes if modal will be closed
         */
        saveDependenciesBackup: function () {
            var dependency = registry.get(this.entityProvider).get(this.entityDataScope).dependency,
                parsedDependency = dependency ? JSON.parse(dependency) : [],
                savedDependencies = [];

            _.each(parsedDependency, function(value) {
                savedDependencies.push(value[0] + ',' + value[1]);
            });
            this.dependencyTree.value(savedDependencies);
            this.savedDependencies = dependency;
        },

        /**
         * Get option data scope
         *
         * @param optionValueScope
         */
        _getOptionScope: function (optionValueScope) {
            var productScope = optionValueScope.split('options')[0];
            return productScope + 'options';
        },

        /**
         * Get current option ID
         *
         * @param optionValueScope
         */
        _getCurrentOptionId: function (optionValueScope) {
            var optionScope = optionValueScope.split('.values')[0],
                optionId = optionScope.split('.').pop();
            return optionId;
        },

        /**
         * Get current option value ID
         *
         * @param optionValueScope
         */
        _getCurrentOptionValueId: function (optionValueScope) {
            return optionValueScope.split('.values.')[1];
        },

        /**
         * Save dependencies and close modal
         */
        save: function () {
            this.saveDependency();
            this.toggleModal();
        },

        /**
         * Clear dependencies before close modal window.
         */
        clearDependencies: function () {
            var self = this;
            var values = [];
            _.each(self.dependencyTree.value(), function(value) {
                values.push(value);
            });

            _.each(values, function (value) {
                self.dependencyTree.removeSelected(value);
            });
        },

        /**
         * Restore saved dependencies in registry before close modal window.
         */
        restoreSavedDependencies: function () {
            var dependencies = this.entityDataScope + '.dependency';
            registry.get(this.entityProvider).set(dependencies, this.savedDependencies);
        },

        /**
         * Save dependencies before close modal.
         */
        saveDependency: function () {
            var dependency_type = this.entityDataScope + '.dependency_type';
            registry.get(this.entityProvider).set(dependency_type, this.dependencyType.value());

            var values = [];
            _.each(this.dependencyTree.value(), function (value) {
                var parts = value.split(',');
                values.push([parts[0],parts[1]]);
            });

            this.updateButtonStatus(values);
            values = values.length ? JSON.stringify(values) : "";

            var dependencies = this.entityDataScope + '.dependency';
            registry.get(this.entityProvider).set(dependencies, values);
        },

        /**
         * Update button status
         *
         * @param object
         */
        updateButtonStatus: function (object) {
            if (object && object.length > 0) {
                $('*[data-name="' + this.buttonName + '"]').addClass('active');
            } else {
                $('*[data-name="' + this.buttonName + '"]').removeClass('active');
            }
        }
    });
});
