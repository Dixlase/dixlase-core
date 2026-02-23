<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 */

return [
    'heading' => 'Legal Page URLs',
    'description' => 'Manage URLs for legal pages such as Privacy Policy and Terms of Service. These URLs are used by other plugins to link to legal pages.',

    'url_label' => ':name URL',
    'url_placeholder' => 'https://example.com/privacy-policy',
    'required_badge' => 'Required',
    'optional_badge' => 'Optional',

    'save_success' => 'Legal page URLs have been saved.',

    'confirm_title' => 'Save Legal Page URLs',
    'confirm_message' => 'Are you sure you want to save the legal page URL settings?',

    'validation' => [
        'url' => ':name must be a valid URL.',
        'max' => ':name URL must not exceed 2048 characters.',
    ],
];
