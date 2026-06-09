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
    'title' => 'Core Integrity',
    'lead' => 'Verifies that Dixlase Core is a genuine, unmodified release against its signed manifest. This is integrity / genuine-release assurance, not tamper-proofing.',

    'status' => [
        'genuine' => 'Genuine',
        'modified' => 'Modified',
        'unsigned' => 'Unsigned',
        'pending' => 'Verification pending',
        'invalid' => 'Invalid signature',
        'error' => 'Verification error',
        'waived' => 'Waived',
    ],

    'desc' => [
        'genuine' => 'Core matches its signed manifest exactly.',
        'modified' => 'Core is an official release, but some files differ from the signed manifest (often a deliberate local customization).',
        'unsigned' => 'No signed manifest is present — this is a development build.',
        'pending' => 'The trusted public key is not available yet (offline, or no cached key). Try again once online.',
        'invalid' => 'The manifest signature did not verify. The manifest may be forged or signed by an untrusted key.',
        'error' => 'Verification could not complete. See the message below.',
        'waived' => 'An operator has waived the integrity warning for this install.',
    ],

    'field' => [
        'status' => 'Status',
        'version' => 'Version',
        'key_id' => 'Key ID',
        'signed_at' => 'Signed at',
        'changed' => 'Changed files',
    ],

    'diff' => [
        'modified' => 'modified',
        'missing' => 'missing',
        'extra' => 'extra',
    ],

    'changed_files_title' => 'Modified files (:count)',
    'recheck' => 'Re-check now',

    'waiver_active' => [
        'title' => 'Active waiver',
        'reason' => 'Reason',
        'by' => 'Waived by',
        'at' => 'Waived at',
    ],

    'danger' => [
        'title' => 'Danger zone',
        'lead' => 'Developer actions for customized installs. You are responsible for trusting this build.',
        'disabled_notice' => 'Signature waiver / removal is disabled on this install. Set DLS_CORE_ALLOW_UNSIGN=true (or APP_DEBUG=true) on a development / customized install to enable it.',
    ],

    'waive' => [
        'title' => 'Waive signature check',
        'lead' => 'Suppress the integrity warning while keeping the signed manifest. Use when you have deliberately modified core.',
        'reason_label' => 'Reason',
        'reason_placeholder' => 'e.g. local customization for project X',
        'button' => 'Waive signature check',
        'confirm_title' => 'Waive core signature check?',
        'confirm_message' => 'The integrity warning will be suppressed until you revoke the waiver. You are taking responsibility for this build.',
        'confirm_button' => 'Waive',
    ],

    'unwaive' => [
        'button' => 'Revoke waiver',
        'confirm_title' => 'Revoke the core waiver?',
        'confirm_message' => 'The integrity warning will be shown again.',
        'confirm_button' => 'Revoke',
    ],

    'remove' => [
        'title' => 'Remove signature',
        'lead' => 'Delete the core manifest and signature entirely. Core becomes "unsigned" until re-signed in a build environment.',
        'button' => 'Remove signature',
        'confirm_title' => 'Remove the core signature?',
        'confirm_message' => 'This permanently deletes core-manifest.json and core-signature.sig. Core will be reported as unsigned.',
        'confirm_button' => 'Remove',
    ],

    'flash' => [
        'rechecked' => 'Core integrity re-checked.',
        'waived' => 'Core signature check waived.',
        'already_waived' => 'A core waiver is already active.',
        'unwaived' => 'Core waiver revoked.',
        'signature_removed' => 'Core signature removed. Core is now unsigned.',
    ],
];
