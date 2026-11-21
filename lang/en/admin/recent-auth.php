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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    'title' => 'Confirm Access',
    'description' => 'This operation changes how the site or its members are protected. Confirm it is you to continue. You will not be asked again for :minutes minutes.',
    'required' => 'Confirm your password or a passkey to continue.',
    'password_label' => 'Password',
    'submit' => 'Confirm',
    'or' => 'or',
    'use_passkey' => 'Use a Passkey',
    'cancel' => 'Cancel',
    'no_method' => 'Your account has neither a password nor a passkey it can use here. Set a password from the password reset screen, then try again.',
    'password_incorrect' => 'The password is incorrect.',
    'passkey_unavailable' => 'No passkey can be used for this account here.',
    'passkey_failed' => 'The passkey could not be verified.',
    'passkey_cancelled' => 'The passkey confirmation was cancelled.',
    'too_many_attempts' => 'Too many failed confirmations. You have been signed out. Sign in again to continue.',
    'confirmed' => 'Confirmed. You will not be asked again for :minutes minutes.',
    'confirmed_resubmit' => 'Confirmed. Run the operation again; you will not be asked again for :minutes minutes.',
    'error' => 'An error occurred. Please try again.',
];
