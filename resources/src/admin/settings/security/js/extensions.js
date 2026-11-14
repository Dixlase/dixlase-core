/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

import Alpine from 'alpinejs';

const SECURITY_PRESETS = {
    strict: {
        requireSignature: true,
        requirePermissionDefinition: true,
        allowUndefinedPermissions: false,
        pluginMaxHealthLevel: 0,
        themeMaxHealthLevel: 0,
        allowLogicThemes: false,
    },
    balanced: {
        requireSignature: false,
        requirePermissionDefinition: false,
        allowUndefinedPermissions: true,
        pluginMaxHealthLevel: 1,
        themeMaxHealthLevel: 2,
        allowLogicThemes: true,
    },
    development: {
        requireSignature: false,
        requirePermissionDefinition: false,
        allowUndefinedPermissions: true,
        pluginMaxHealthLevel: 3,
        themeMaxHealthLevel: 3,
        allowLogicThemes: true,
    },
};

const HEALTH_LEVEL_CLASSES = {
    0: 'text-green-600 dark:text-green-400',
    1: 'text-yellow-600 dark:text-yellow-400',
    2: 'text-orange-600 dark:text-orange-400',
    3: 'text-gray-600 dark:text-gray-400',
};

/**
 * Extension security section of the extension settings screen.
 *
 * The saved values are read from the element's data attributes.
 */
Alpine.data('extensionSecuritySettings', () => ({
    preset: '',
    requireSignature: false,
    requirePermissionDefinition: false,
    allowUndefinedPermissions: false,
    pluginMaxHealthLevel: 0,
    themeMaxHealthLevel: 0,
    allowLogicThemes: false,
    permissionMismatchAction: '',

    init() {
        const { dataset } = this.$el;

        this.preset = dataset.preset;
        this.requireSignature = dataset.requireSignature === '1';
        this.requirePermissionDefinition = dataset.requirePermissionDefinition === '1';
        this.allowUndefinedPermissions = dataset.allowUndefinedPermissions === '1';
        this.pluginMaxHealthLevel = Number(dataset.pluginMaxHealthLevel);
        this.themeMaxHealthLevel = Number(dataset.themeMaxHealthLevel);
        this.allowLogicThemes = dataset.allowLogicThemes === '1';
        this.permissionMismatchAction = dataset.permissionMismatchAction;

        this.$watch('preset', (value) => {
            if (value !== 'custom') {
                this.applyPreset(value);
            }
        });
    },

    applyPreset(presetValue) {
        if (SECURITY_PRESETS[presetValue]) {
            Object.assign(this, SECURITY_PRESETS[presetValue]);
        }
    },

    getHealthLevelClass(level) {
        return HEALTH_LEVEL_CLASSES[level] || HEALTH_LEVEL_CLASSES[0];
    },
}));

/**
 * Extension source section of the extension settings screen.
 */
Alpine.data('extensionSourceSettings', () => ({
    sourceType: '',
    testUrl: '',
    testStatus: 'idle',
    testMessage: '',
    testDetails: {},

    init() {
        this.sourceType = this.$el.dataset.sourceType;
        this.testUrl = this.$el.dataset.testUrl;
    },

    async testConnection() {
        this.testStatus = 'testing';
        this.testMessage = '';
        this.testDetails = {};

        try {
            const tokenInput = document.getElementById('extension_source_token');
            const response = await fetch(this.testUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    type: this.sourceType,
                    token: tokenInput ? tokenInput.value : '',
                }),
            });

            const data = await response.json();
            this.testStatus = data.success ? 'success' : 'failed';
            this.testMessage = data.message || '';
            this.testDetails = data.details || {};
        } catch (error) {
            this.testStatus = 'failed';
            this.testMessage = error.message;
        }
    },
}));
