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

    /*
    |--------------------------------------------------------------------------
    | Password Reset Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default lines which match reasons
    | that are given by the password broker for a password update attempt
    | outcome such as failure due to an invalid password / reset token.
    |
    */

    'reset' => 'Your password has been reset.',
    'sent' => 'We have emailed your password reset link.',
    'throttled' => 'Please wait before retrying.',
    'token' => 'This password reset token is invalid.',
    'user' => "We can't find a user with that email address.",
    'error' => 'Password does not meet requirements',
    'requirements' => [
        'password' => 'Password Requirements',
        'length_full' => ':min characters or more (:recommended characters or more recommended)',
        'length_simple' => ':min characters or more',
        'suggestion' => ':length characters or more recommended',
        'lowercase' => '1 or more lowercase letters',
        'number' => '1 or more numbers',
        'symbol_required' => 'Include symbols (!@#$%^&* etc.)',
        'symbol_optional' => 'Include symbols (!@#$%^&* etc.) for stronger password (recommended)',
        'uppercase_required' => '1 or more uppercase letters',
        'uppercase_optional' => 'Include uppercase letters for stronger password (recommended)',
        'weak' => 'Weak',
        'normal' => 'Normal',
        'strong' => 'Strong',
        'very_strong' => 'Very Strong',
    ],
    'tooltip' => [
        'generate' => 'Generate password',
        'copy' => 'Copy password',
        'toggle' => 'Toggle password visibility',
    ],

];
