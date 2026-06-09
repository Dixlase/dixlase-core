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
    // CAPTCHA related
    'captcha_failover_subject' => '[Warning] CAPTCHA Service Failover Due to Outage',
    'captcha_failover_message' => 'A CAPTCHA service outage was detected and automatic failover has been executed.

Switched from: :from
Switched to: :to
Time: :time

The system will automatically switch back once the original service recovers.
To check status: php artisan captcha status',

    // External service failures
    'external_service_failure' => 'External Service Failure',
    'hibp_failure_subject' => '[Warning] Have I Been Pwned API Failure',
    'hibp_failure_message' => 'Connection to Have I Been Pwned API failed. Password breach checking is temporarily skipped.',

    // CAPTCHA Bypass notification
    'captcha_bypass_subject' => '[CRITICAL] CAPTCHA Bypass Has Been Enabled',
    'captcha_bypass_message' => 'CAPTCHA bypass (break-glass) has been enabled.

Scope: :scope
Reason: :reason
Duration: :minutes minutes
Expires at: :expires_at

This is an emergency recovery feature. It will be automatically disabled after expiration.
To manually disable: php artisan security:captcha-bypass disable',
];
