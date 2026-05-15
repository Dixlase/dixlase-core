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
    // Recovery code input screen
    'title' => 'Recovery Code',
    'prompt' => 'Please enter your recovery code. If you cannot access your device, you can use a recovery code to log in.',
    'code_label' => 'Recovery Code',
    'format_hint' => 'Enter 20 digits (with or without hyphens)',
    'submit' => 'Authenticate and Login',
    'invalid' => 'The recovery code is invalid.',
    'invalid_with_attempts' => 'The recovery code is invalid. Remaining attempts: :attempts',
    'use_recovery_code' => 'Recovery Code',
    'back_to_two_fa' => 'Back to Two-Factor Authentication',

    // Recovery codes display modal
    'warning' => 'Please store these recovery codes in a safe place.<br>If you lose access to your device, you can use these codes to access your account.',
    'confirm_saved' => 'I confirm that I have saved the recovery codes in a safe place',
    'auto_generated_title' => 'Recovery Codes Generated',
    'auto_generated_message' => 'Recovery codes have been automatically generated because two-factor authentication is enabled.',
];
