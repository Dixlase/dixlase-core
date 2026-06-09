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
    'name_prompt' => 'Display name for this network key',
    'name_required' => 'A name is required.',

    'invalid_environment' => 'Invalid --environment :environment (must be live or test).',
    'invalid_scope' => "Unknown scope(s): :scopes\nAvailable scopes: :available",

    'warning' => 'CAUTION: A network key authenticates against EVERY site (site_id = null). Treat it like a root credential.',

    'summary_title' => 'About to create a network API key with these settings:',
    'field' => 'Field',
    'value' => 'Value',
    'summary_name' => 'Name',
    'summary_environment' => 'Environment',
    'summary_scopes' => 'Scopes',
    'summary_rate_limit' => 'Rate limit',
    'summary_expires_at' => 'Expires at',
    'summary_description' => 'Description',

    'confirm_create' => 'Create this network key now?',
    'cancelled' => 'Cancelled. No key was created.',

    'created' => 'Network API key created and recorded in audit_logs (severity = critical).',
    'plain_key_warning' => 'Plain key (shown ONCE, copy it now — you cannot recover it later):',
    'id_label' => 'ID',
    'prefix_label' => 'Prefix',
    'audit_logged' => 'Use of this key will appear as "network_api_key_used" in audit_logs.',
];
