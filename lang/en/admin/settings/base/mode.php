<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
