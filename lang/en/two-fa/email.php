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
    'prompt' => <<<'TEXT'
We have sent you an email with an authentication code.
Please enter the 6-digit authentication code from the email.
TEXT,
    'code_title' => 'Enter Authentication Code',
    'code_prompt' => 'Please enter the 6-digit authentication code from the email.',
    'code_label' => 'Authentication Code',
    'expire_notice' => 'The authentication code is valid for :minutes minutes.',
    'expire_label' => 'Code expires in',
    'expired' => 'Expired',
    'submit' => 'Authenticate and Login',
    'verify' => 'Verify',
    'resend' => 'Resend Authentication Code',
    'invalid' => 'The authentication code is incorrect or has expired.',
    'invalid_code' => 'The authentication code is incorrect or has expired.',
    'invalid_with_attempts' => 'The authentication code is incorrect. Remaining attempts: :attempts',
    'resend_success' => 'Email has been resent.',
    'resend_failed' => 'Failed to resend code',
    'network_error' => 'A network error occurred',
    'minutes_suffix' => 'min',
    'seconds_suffix' => 's',
    'use_email_code' => 'Email Authentication',
];
