<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

return [
    'heading' => 'Mode Settings',
    'description' => 'Switch the admin panel display mode. In Simple Mode, you can customize the visibility level of each menu.',

    // Mode selection
    'mode_selection' => 'Mode Selection',
    'mode_selection_description' => 'Select the display mode for the admin panel. You can change the mode at any time.',

    'simple_mode' => 'Simple Mode',
    'simple_mode_description' => 'Shows only essential menus and automatically optimizes advanced settings. Ideal for beginners and daily operations.',
    'simple_feature_auto' => 'Advanced settings like security are automatically optimized',
    'simple_feature_clean' => 'Clean menu layout for easy navigation',
    'simple_feature_customize' => 'Menu visibility levels are fully customizable',

    'advanced_mode' => 'Advanced Mode',
    'advanced_mode_description' => 'All menus and settings are displayed. For users who need fine-grained system control.',
    'advanced_feature_full' => 'Access to all menus and settings',
    'advanced_feature_control' => 'Fine-grained security and system management',

    'recommended' => 'Recommended',

    // Simple mode cautions (displayed on card)
    'simple_caution_settings_reset' => 'Some security settings will be reset to recommended values',
    'simple_caution_menu_hidden' => 'Some menus will be hidden',
    'simple_caution_auto_optimize' => 'Hidden menu settings will be automatically optimized',

    // Advanced mode cautions (displayed on card)
    'advanced_caution_all_visible' => 'All menus and settings will be displayed',
    'advanced_caution_manual' => 'Settings optimization must be done manually',
    'advanced_caution_knowledge' => 'Please use with understanding of system implications',

    // Mode switch warning modal
    'switch_modal_title' => 'Switch mode?',
    'switch_to_simple_warning' => 'Switching to Simple Mode will apply the following changes:',
    'switch_to_simple_warn_1' => 'Some security settings may be reset to recommended values',
    'switch_to_simple_warn_2' => 'Some menus will be hidden',
    'switch_to_simple_warn_3' => 'Hidden menu settings will be automatically optimized',
    'switch_to_simple_note' => 'You can switch back to Advanced Mode at any time.',
    'switch_to_advanced_warning' => 'Switching to Advanced Mode will apply the following changes:',
    'switch_to_advanced_warn_1' => 'All menus and settings will be displayed',
    'switch_to_advanced_warn_2' => 'Menu visibility customization settings will be cleared',
    'switch_to_advanced_warn_3' => 'Settings optimization must be done manually',
    'switch_to_advanced_note' => 'You can switch back to Simple Mode at any time.',
    'switch_confirm' => 'Switch',
    'switch_cancel' => 'Cancel',

    // Menu customization
    'menu_customize' => 'Menu Visibility Customization',
    'menu_customize_description' => 'Adjust the visibility level of each menu in Simple Mode. Hidden menu settings are automatically optimized.',

    'always_visible' => 'Always Visible',
    'reset_to_defaults' => 'Reset to Defaults',

    // Visibility levels
    'visibility' => [
        'full' => 'Full Access',
        'full_description' => 'All features are displayed and fully operational.',
        'partial' => 'Partial',
        'partial_description' => 'Only key features are shown; detailed settings are automatically optimized.',
        'hidden' => 'Hidden',
        'hidden_description' => 'Menu is hidden and settings are automatically optimized.',
        'read_only' => 'Read Only',
        'read_only_description' => 'Current settings can be viewed but not modified.',
        'guide_only' => 'Guide Only',
        'guide_only_description' => 'Menu is shown but changing settings requires switching to Advanced Mode.',
    ],

    // Advanced mode info
    'advanced_info' => 'In Advanced Mode, all menus and settings are displayed. Menu visibility customization is only available in Simple Mode.',

    // Save
    'settings_updated' => 'Mode settings have been updated.',
    'save_confirmation_message' => 'Save mode settings? Menu visibility may change.',

    // Overview page
    'current_mode' => 'Current Mode',
];
