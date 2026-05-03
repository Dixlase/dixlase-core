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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    'heading' => 'API Management',
    'general_settings' => 'General Settings',
    'api_enabled' => 'Enable API',
    'api_enabled_help' => 'Allow access via API from external systems.',
    'signature_required' => 'Require Signature Verification',
    'signature_required_help' => 'Require signature verification for API requests. Recommended for enhanced security.',
    'default_rate_limit' => 'Default Rate Limit',
    'rate_limit_help' => 'Default rate limit applied when no individual setting is configured for an API key.',
    'requests_per_minute' => 'requests/min',
    'update_success' => 'API settings have been updated.',

    // API Key Management
    'api_keys' => 'API Key Management',
    'create_key' => 'Create New API Key',
    'no_keys' => 'No API keys found. Click "Create New API Key" button to create one.',
    'key_name' => 'Key Name',
    'key_name_placeholder' => 'e.g., External System Integration',
    'key_prefix' => 'Key Prefix',
    'environment' => 'Environment',
    'env_live_desc' => 'For production',
    'env_test_desc' => 'For testing/development',
    'status' => 'Status',
    'last_used' => 'Last Used',
    'never_used' => 'Never used',
    'usage_count' => 'Usage Count',
    'expired' => 'Expired',
    'scopes' => 'Permission Scopes',
    'no_scopes' => 'No scopes',
    'rate_limit' => 'Rate Limit',
    'unlimited' => 'Unlimited',
    'allowed_ips' => 'Allowed IP Addresses',
    'allowed_ips_placeholder' => 'e.g., 192.168.1.1, 10.0.0.0',
    'allowed_ips_help' => 'Comma-separated. Leave empty to allow all IPs.',
    'all_ips_allowed' => 'All IPs allowed',
    'expires_at' => 'Expiration Date',
    'expires_at_help' => 'Leave empty for no expiration.',
    'no_expiry' => 'No expiration',
    'description' => 'Description',
    'description_placeholder' => 'Describe the purpose of this API key',
    'created_at' => 'Created At',
    'generate' => 'Generate Key',
    'regenerate' => 'Regenerate',
    'regenerate_confirm' => 'Are you sure you want to regenerate this API key? The current key will be invalidated.',
    'revoke_confirm' => 'Are you sure you want to delete this API key? This action cannot be undone.',
    'key_details' => 'API Key Details',

    // Success Messages
    'key_generated' => 'API key has been generated.',
    'key_generated_warning' => 'Important: This API key will only be shown once',
    'key_generated_warning_detail' => 'Please save this key in a secure location. You will not be able to see it again after leaving this page.',
    'key_regenerated' => 'API key has been regenerated.',
    'key_revoked' => 'API key has been deleted.',
    'copied_to_clipboard' => 'Copied to clipboard.',
];
