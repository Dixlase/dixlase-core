<?php

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
 */

return [
    'heading' => 'Add Theme',
    'description' => 'Upload a ZIP file to install a new theme.',
    'upload_title' => 'Upload Theme',
    'file_select_label' => 'Select File',
    'drag_drop_text' => 'Drag file here or click to upload',
    'supported_format' => 'Supported format:',
    'upload_limit' => 'Maximum upload file size:',
    'upload_button' => 'Upload and Add',
    'uploading_title' => 'Uploading Theme',
    'uploading_wait' => 'Please wait while the file is being uploaded and extracted.',
    'name' => 'Theme Name',

    // Tabs
    'tab_zip' => 'From ZIP File',
    'tab_online' => 'From Online',

    // Online install
    'online' => [
        'title' => 'Install from Online',
        'description' => 'Browse and download themes from the configured extension source.',
        'loading' => 'Loading available themes...',
        'no_themes' => 'No themes available from this source.',
        'connection_error' => 'Failed to connect to the extension source.',
        'download' => 'Download',
        'downloading' => 'Downloading...',
        'downloading_title' => 'Downloading Theme',
        'downloading_wait' => 'Please wait until the download completes.',
    ],

    // Controller Messages
    'messages' => [
        'download_success' => 'Theme ":name" downloaded successfully. Please install from the list.',
        'download_failed' => 'Theme download failed: :error',
        'zip_extract_failed' => 'Failed to extract ZIP file.',
        'no_valid_directory' => 'No valid theme directory found in the ZIP file.',
        'directory_exists' => "Theme directory ':directory' already exists.",
        'theme_json_not_found' => 'theme.json not found.',
    ],
];
