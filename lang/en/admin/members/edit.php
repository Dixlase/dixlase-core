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
    'heading' => 'Edit Member',
    'confirm_title' => 'Update Confirmation',
    'confirm_message' => 'Do you want to update the member information with this content?',
    'modals' => [
        'force_logout' => [
            'title' => 'Force Logout Confirmation',
            'message' => 'Do you want to force logout :name?',
            'confirm' => 'Execute Force Logout',
        ],
        'unlock_lockout' => [
            'title' => 'Unlock Lockout Confirmation',
            'message' => 'Do you want to unlock login and two-factor authentication lockout for :name?',
            'confirm' => 'Unlock Lockout',
        ],
        'delete' => [
            'title' => 'Delete Member Confirmation',
            'message' => 'Do you want to completely delete :name?',
            'warning' => 'This operation cannot be undone.',
        ],
    ],
    'messages' => [
        'updated' => 'Member information has been updated.',
        'updated_with_verification_email' => 'Member information has been updated. Verification email sent.',
        'updated_but_email_failed' => 'Member information has been updated, but failed to send verification email.',
        'verification_email_sent' => 'Verification email has been sent.',
        'verification_email_failed' => 'Failed to send verification email.',
        'unlock_lockout_success' => 'Lockout has been unlocked.',
        'force_logout_success' => 'Member has been forcibly logged out.',
        'deleted' => 'Member account has been deleted.',
    ],
];
