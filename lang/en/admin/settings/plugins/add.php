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

    // Tabs
    'tab_zip' => 'From ZIP File',
    'tab_online' => 'From Online',

    // Online install
    'online' => [
        'title' => 'Install from Online',
        'description' => 'Browse and download plugins from the configured extension source.',
        'loading' => 'Loading available plugins...',
        'no_plugins' => 'No plugins available from this source.',
        'connection_error' => 'Failed to connect to the extension source.',
        'source_not_configured' => 'Extension source is not configured.',
        'configure_link' => 'Configure in Security Settings',
        'download' => 'Download',
        'downloading' => 'Downloading...',
        'version' => 'v:version',
        'by_author' => 'by :author',
    ],

    // Controller Messages
    'messages' => [
        'upload_success' => 'Plugin upload completed. Please install from the list.',
        'upload_failed' => 'Plugin upload failed: :error',
        'zip_extract_failed' => 'Failed to extract ZIP file.',
        'no_valid_directory' => 'No valid plugin directory found in the ZIP file.',
        'directory_exists' => "Plugin directory ':directory' already exists.",
        'composer_not_found' => 'composer.json not found.',
        'download_success' => 'Plugin ":slug" downloaded successfully. Please install from the list.',
        'download_failed' => 'Plugin download failed: :error',
    ],
];
