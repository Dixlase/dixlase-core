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
    'audit_scan_not_executed' => 'Audit scan has not been executed.',
    'audit_scan_not_run' => 'Audit scan has not been executed.',
    'author_id_not_defined' => 'author_id is not defined in plugin.json.',
    'authority_key_id_not_defined' => 'authority_key_id is not defined in plugin.json.',
    'api_version_missing' => 'plugin.json does not declare requires.dixlase_api.',
    'api_version_incompatible' => 'Plugin declares Extension API :declared but core supports :supported.',
    'api_constraint_malformed' => 'requires.dixlase_api is not a valid semver constraint.',
    'csp_violation_detected' => 'CSP violation detected (:cspMode mode)',
    'dangerous_api_declared' => 'Declared dangerous API in use: :permission',
    'dangerous_api_detected' => 'Dangerous API detected: :permission',
    'direct_upload_to_public_dir' => 'Uploads directly to public directory.',
    'inline_css_required_strict_mode' => 'Inline CSS is required. May not work in strict mode.',
    'inline_js_required_strict_mode' => 'Inline JavaScript is required. Will not work in strict mode.',
    'no_signature_recommend_signing' => 'No signature found. Signing is recommended for distribution.',
    'permissions_not_defined_in_json' => 'permissions section is not defined in plugin.json.',
    'permissions_section_undefined' => 'permissions section is undefined.',
    'public_upload_in_dedicated_dir' => 'Uses public upload within dedicated directory.',
    'scan_outdated_rescan_recommended' => 'Scan is outdated (:daysSinceScan days ago). Rescan recommended',
    'signature_invalid_tampering' => 'Signature is invalid. Possible tampering detected.',
    'signature_verification_error' => 'An error occurred during signature verification.',
    'signature_verification_incomplete_keyserver' => 'Signature verification not completed (public key server not connected).',
    'signing_key_not_trusted' => 'Signing key is not registered as trusted.',
    'signing_key_revoked' => 'Key used for signing has been revoked.',
    'undeclared_permission_usage' => 'Undeclared permission usage: :permission',
    'unused_permission_declaration' => 'Unused permission declaration: ',
    'uses_bulk_email_permission' => 'Uses bulk email sending permission.',
    'uses_member_deletion_permission' => 'Uses member deletion permission.',
];
