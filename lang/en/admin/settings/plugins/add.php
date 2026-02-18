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
    'heading' => 'Add Plugin',
    'description' => 'Upload a ZIP file to install a new plugin.',
    'upload_title' => 'Plugin Upload',
    'file_select_label' => 'Select ZIP File:',
    'drag_drop_text' => 'Drag file here or click to upload',
    'supported_format' => 'Supported format:',
    'upload_limit' => 'Maximum upload file size:',
    'upload_button' => 'Upload and Add',
    'enable_plugin_text' => 'To enable the plugin,',
    'enable_from_here' => 'click here',
    'enable_instruction' => 'to enable.',
    'name' => 'Plugin Name',

    // Controller Messages
    'messages' => [
        'upload_success' => 'Plugin upload completed. Please install from the list.',
        'upload_failed' => 'Plugin upload failed: :error',
        'zip_extract_failed' => 'Failed to extract ZIP file.',
        'no_valid_directory' => 'No valid plugin directory found in the ZIP file.',
        'directory_exists' => "Plugin directory ':directory' already exists.",
        'composer_not_found' => 'composer.json not found.',
    ],
];
