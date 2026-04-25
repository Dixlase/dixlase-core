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

    /*
    |--------------------------------------------------------------------------
    | API Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for API responses and error messages.
    |
    */

    'signature' => [
        'errors' => [
            'missing_headers' => 'Required signature headers are missing',
            'timestamp_expired' => 'Signature timestamp has expired',
            'invalid_api_key' => 'Invalid API key',
            'unsupported_version' => 'Unsupported signature version',
            'invalid_signature' => 'Signature verification failed',
            'revoked_key' => 'API key has been revoked',
        ],
    ],

    'responses' => [
        'success' => 'Success',
        'error' => 'An error occurred',
        'not_found' => 'Resource not found',
        'unauthorized' => 'Authentication required',
        'forbidden' => 'Access denied',
        'validation_error' => 'Validation error',
        'server_error' => 'Server error occurred',
    ],

];
