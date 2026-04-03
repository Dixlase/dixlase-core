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
    'index' => [
        'heading' => 'Front Page Master',
        'description' => 'Manage front page content. Create, edit, or reset the front page.',

        'content_exists_title' => 'Front Page Content',
        'language' => 'Language',
        'editor_type' => 'Editor Type',
        'storage_type' => 'Storage Type',
        'last_updated' => 'Last Updated',
        'status' => 'Status',

        'edit_button' => 'Edit Content',
        'reset_button' => 'Reset',
        'reset_confirm_title' => 'Reset Front Page',
        'reset_confirm' => 'Are you sure you want to reset the front page content? This action cannot be undone.',

        'no_content_title' => 'No Content Yet',
        'no_content_description' => 'Front page content has not been created yet. Click the button below to create it.',
        'create_button' => 'Create Front Page',

        'reset_success' => 'Front page content has been reset.',
    ],

    'create' => [
        'heading' => 'Create Front Page',
        'description' => 'Set up the front page content with your preferred language and editor type.',

        'lang_label' => 'Language',
        'editor_type_label' => 'Editor Type',
        'content_label' => 'Content',
        'content_placeholder' => 'Enter your front page content...',

        'custom_css_placeholder' => 'Enter custom CSS styles...',
        'custom_js_placeholder' => 'Enter custom JavaScript...',

        'confirm_title' => 'Create Front Page',
        'confirm_message' => 'Create the front page content with the selected settings?',

        'create_success' => 'Front page content has been created.',

        'sidebar_open' => 'Open sidebar',
        'sidebar_close' => 'Close sidebar',

        'validation' => [
            'lang_required' => 'Please select a language.',
            'lang_in' => 'The selected language is not supported.',
            'editor_type_required' => 'Please select an editor type.',
            'editor_type_in' => 'The selected editor type is not valid.',
            'storage_type_required' => 'Please select a storage type.',
            'storage_type_in' => 'The selected storage type is not valid.',
            'content_max' => 'Content must not exceed 500,000 characters.',
            'custom_js_max' => 'JavaScript must not exceed 500,000 characters.',
            'custom_css_max' => 'CSS must not exceed 500,000 characters.',
        ],
    ],

    'edit' => [
        'heading' => 'Edit Front Page',
        'description' => 'Edit the front page content.',

        'editor_type_label' => 'Editor Type',
        'lang_label' => 'Language',
        'content_label' => 'Content',
        'content_placeholder' => 'Enter your front page content...',

        'custom_css_placeholder' => 'Enter custom CSS styles...',
        'custom_js_placeholder' => 'Enter custom JavaScript...',

        'confirm_title' => 'Save Changes',
        'confirm_message' => 'Save the changes to the front page content?',

        'save_success' => 'Front page content has been updated.',

        'sidebar_open' => 'Open sidebar',
        'sidebar_close' => 'Close sidebar',

        'preview_title' => 'Preview',
        'preview_show' => 'Show preview',
        'preview_hide' => 'Hide preview',
        'scroll_to_editor' => 'Scroll to editor',
        'scroll_to_preview' => 'Scroll to preview',
        'device_mobile' => 'Mobile',
        'device_tablet' => 'Tablet',
        'device_desktop' => 'Desktop',
        'device_free' => 'Free size',
        'preview_width' => 'Width',
        'preview_height' => 'Height',

        'reset_section_title' => 'Danger Zone',
        'reset_description' => 'Reset the front page content. This action cannot be undone.',
        'reset_button' => 'Reset',
        'reset_confirm_title' => 'Reset Front Page',
        'reset_confirm' => 'Are you sure you want to reset the front page content? All content, CSS, and JavaScript will be permanently deleted.',

        'validation' => [
            'storage_type_required' => 'Please select a storage type.',
            'storage_type_in' => 'The selected storage type is not valid.',
            'content_max' => 'Content must not exceed 500,000 characters.',
            'custom_js_max' => 'JavaScript must not exceed 500,000 characters.',
            'custom_css_max' => 'CSS must not exceed 500,000 characters.',
        ],
    ],

    'settings' => [
        'heading' => 'Front Page Settings',
        'description' => 'Configure front page settings.',

        'no_settings' => 'No additional settings are available at this time.',

        'settings_updated' => 'Front page settings have been updated.',

        'confirm_title' => 'Save Settings',
        'confirm_message' => 'Save the front page settings?',
    ],
];
