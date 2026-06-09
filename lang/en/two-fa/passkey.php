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
    // Passkey device not registered warning
    'device_not_registered_title' => 'No Passkey Device Registered',
    'device_not_registered_message' => 'You need to register a device to use Passkey authentication.<br>Please register a device from your profile page.<br>Until then, please use other authentication methods.',

    // Passkey Authentication
    'title' => 'Passkey Authentication (Biometric)',
    'prompt' => 'Please use Passkey (biometric) authentication to log in.',
    'start_auth' => 'Start Authentication',
    'waiting_title' => 'Waiting for Passkey Authentication',
    'waiting_message' => 'Please use Touch ID, Face ID, or your registered Passkey.',
    'success_title' => 'Authentication Successful',
    'success_message' => 'Passkey authentication completed. Redirecting...',
    'error_title' => 'Authentication Failed',
    'error_message' => 'Passkey authentication failed. Please try again.',
    'retry' => 'Retry',
    'unsupported_title' => 'Passkey Not Supported',
    'unsupported_message' => 'Your device or browser does not support Passkey.',
    'challenge_failed' => 'Failed to start challenge',
    'network_error' => 'A network error occurred',
    'no_challenge_data' => 'No challenge data available',
    'verification_failed' => 'Authentication verification failed',
    'auth_cancelled' => 'Authentication was cancelled',
    'invalid_state' => 'Authentication state is invalid',
    'auth_failed' => 'Biometric authentication failed',

    // Biometric Authentication (Passkey)
    'https_required' => 'HTTPS connection required.',
    'challenge_generation_failed' => 'Failed to generate challenge.',
    'registered_successfully' => 'Biometric authentication registered.',
    'registration_failed' => 'Failed to register biometric authentication.',
    'revoked_successfully' => 'Biometric authentication deleted.',
    'not_found' => 'Biometric authentication not found.',
    'revocation_failed' => 'Failed to delete biometric authentication.',
    'all_revoked_successfully' => 'All biometric authentications deleted (:count).',
    'revoke_all_failed' => 'Failed to delete all biometric authentications.',

    // Passkey device name modal
    'device_name_title' => 'Register Passkey Device',
    'device_name_message' => 'Please enter a name for this device to help identify it later.',
    'device_name_label' => 'Device Name',

    // Passkey registration prompt modal
    'prompt_modal' => [
        'title' => 'We Recommend Registering Passkey (Biometric)',
        'message' => 'By registering a Passkey, you can log in more securely and conveniently using fingerprint or face recognition.',
        'register_now' => 'Register Now',
        'later' => 'Register Later',
        'dont_show_again' => 'Don\'t show this again',
        'dismissed' => 'Passkey registration prompt has been hidden.',
        'reset' => 'Passkey registration prompt settings have been reset.',
    ],
];
