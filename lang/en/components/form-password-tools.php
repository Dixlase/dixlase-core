<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
    'toolbar_label' => 'Password Tools',
    'strength' => [
        'error' => 'Password does not meet requirements',
        'normal' => 'Normal strength',
        'strong' => 'Strong password',
    ],
    'tooltip' => [
        'generate' => 'Generate',
        'copy' => 'Copy',
        'toggle' => 'Toggle visibility',
    ],
    'copied' => 'Password copied!',
    'requirements' => [
        'length' => '8 or more characters',
        'lowercase' => 'Include at least 1 lowercase letter',
        'number' => 'Include at least 1 number',
        'uppercase' => 'Include at least 1 uppercase letter',
        'symbol' => 'Include at least 1 symbol (!@#$%^&* etc.)',
        'length_full' => ':min or more characters (recommended :recommended or more)',
        'length_simple' => ':min or more characters',
        'lowercase_optional_note' => 'Include lowercase letters',
        'number_optional_note' => 'Include numbers',
        'uppercase_optional_note' => 'Include uppercase letters',
        'symbol_optional_note' => 'Including symbols（!@#$%^&*-_=+ etc.)',
        'weak' => 'Weak',
        'normal' => 'Normal',
        'strong' => 'Strong',
        'very_strong' => 'Very Strong',
    ],
    'error' => 'Password does not meet requirements.',
];
