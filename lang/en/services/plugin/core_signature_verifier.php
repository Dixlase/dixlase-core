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
    'key_id_mismatch' => 'Key ID mismatch between plugin.json and signature.sig.',
    'plugin_file_tampering_detected' => 'Plugin file tampering detected.',
    'plugin_json_no_files_section' => 'No files section in plugin.json.',
    'plugin_json_not_found' => 'plugin.json not found.',
    'plugin_json_parse_failed' => 'Failed to parse plugin.json.',
    'signature_file_not_found' => 'Signature is declared but signature file not found.',
    'signature_file_parse_failed' => 'Failed to parse signature file.',
    'signature_mismatch_tampering' => 'Signature mismatch. The plugin may have been tampered with or a different signing key was used.',
    'signature_missing_required_fields' => 'Required fields are missing in signature file.',
    'signature_verification_error' => 'Error occurred during signature verification: ',
    'verification_suspended_no_key' => 'Verification suspended because public key could not be retrieved.',
];
