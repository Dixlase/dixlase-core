<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
