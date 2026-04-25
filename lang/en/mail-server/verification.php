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
    // Mail Verification Success Page
    'verification_success' => [
        'title' => 'Mail Verification Complete',
        'heading' => 'Mail Verification Completed',
        'description' => 'The mail function test has been completed successfully.',
        'actions' => 'Actions',
        'already_verified_heading' => 'Mail Receive Already Verified',
        'already_verified_description' => 'This email verification has already been completed.',
        'next_steps_title' => 'Next Steps',
        'next_steps' => [
            'close_window' => 'Please close this window',
            'save_settings' => 'Save settings to confirm test results',
            'data_saved' => 'Data has been saved',
        ],
        'next_steps_install' => [
            'close_window' => 'Please close this window',
            'continue_install' => 'Continue with installation',
        ],
        'important_notice_title' => 'Important Notice',
        'important_notice' => 'Test results are temporary. Settings must be saved to confirm.',
        'close_button' => 'Close Window',
        'completed_message' => 'Mail receive verification completed.',
    ],

    // Mail Verification Error Page
    'verification_error' => [
        'title' => 'Mail Verification Error',
        'heading' => 'An error occurred during mail verification',
        'invalid_token_description' => 'This mail verification link is invalid or expired.',
        'verification_error_description' => 'An error occurred while processing mail verification.',
        'general_error_description' => 'An unexpected error has occurred.',
        'solution_title' => 'Solution',
        'actions' => 'Actions',
        'solution_steps' => [
            'Please close this window',
            'Send a new test email from the base settings screen',
            'Complete verification using the link in the new email',
        ],
        'close_button' => 'Close Window',
        'error_occurred' => 'Mail verification error occurred.',
    ],

    // Mail Verification Functions (Common)
    'verification_token_invalid' => 'Mail verification token is invalid.',
    'verification_error_message' => 'An error occurred during mail verification: :error',
    'verification_success_common' => [
        'title' => 'Mail Verification Complete',
        'heading' => 'Mail verification has been completed',
        'description' => 'It has been confirmed that the mail server settings are working correctly.',
        'next_steps_title' => 'Next Steps',
        'next_steps' => [
            'close_window' => 'Please close this window',
            'continue_install' => 'Return to the installation screen and continue with the setup',
            'save_settings' => 'Please save the settings',
            'data_saved' => 'Data is temporarily saved',
        ],
        'close_button' => 'Close Window',
        'completed_message' => 'Mail verification has been completed',
    ],
];
