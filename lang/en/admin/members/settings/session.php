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
    'heading' => 'Session Settings',
    'description' => 'Customize session lifetime specifically for admin members.',
    'admin_settings' => 'Admin Member Session Settings',
    'admin_settings_description' => 'Configure session lifetime specifically for admin members. When enabled, this takes priority over the default value in security settings.',
    'lifetime_enabled' => 'Custom Session Lifetime',
    'lifetime_enabled_help' => 'When enabled, you can set a custom session lifetime for admin members. When disabled, the default value from security settings is used.',
    'lifetime' => 'Session Lifetime',
    'lifetime_help' => 'Set the session lifetime for admin members in minutes (1-43200 minutes).',
];
