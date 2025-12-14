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
    'heading' => 'Dashboard',
    'description' => 'You can check the site overview.',
    'method_change_modal' => [
        'title' => 'Change Authentication Method',
        'message' => 'You authenticated with ":used_method" this time, but your current default authentication method is ":current_method".',
        'question' => 'Would you like to change your default authentication method to ":used_method"?',
        'switch_button' => 'Yes, change it',
        'keep_button' => 'No, keep current',
    ],
    'method_switched_success' => 'Default authentication method has been changed.',
    'method_switch_failed' => 'Failed to change authentication method.',
];
