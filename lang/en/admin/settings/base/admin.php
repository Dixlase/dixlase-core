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
    'heading' => 'Admin Panel Settings',
    'admin_panel_settings' => 'Admin Panel Settings',
    'admin_url' => 'Admin URL',
    'admin_url_prefix' => 'URL Prefix',
    'admin_url_suffix' => 'URL Suffix',
    'admin_url_help' => 'Choose a prefix and enter a suffix to set the admin panel URL path.<br>The suffix must be at least 4 characters (lowercase letters and numbers only).<br>Warning: Changing the admin URL will log you out of the admin panel.',
    'force_ssl' => 'Force SSL',
    'force_ssl_help' => 'Force HTTPS access. Only enable if SSL certificate is configured.',
    'settings_updated' => 'Admin panel settings have been updated.',
    'admin_url_changed' => 'Admin URL has been changed. Please log in with the new URL.',

    // コンテンツエディター設定
    'content_editor_settings' => 'Content Editor',
    'preferred_gui_editor' => 'GUI Editor',
    'preferred_gui_editor_help' => 'Select the GUI block editor plugin to use for content editing. When multiple GUI editor plugins are installed, the selected one will be used as the default.',
    'no_gui_editor_available' => 'No GUI editor plugin is installed. Install a GUI editor plugin to enable the block editor.',
    'gui_editor_auto' => 'Auto (use the only available editor)',
];
