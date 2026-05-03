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
    'heading' => 'Member privacy data',
    'description' => 'Export or erase the personal data tied to a single member. Every privacy data provider registered in core or in a plugin is dispatched in one operation.',

    'search' => [
        'label' => 'Look up a member',
        'placeholder' => 'Member id, email, or account name',
        'submit' => 'Find',
        'help' => 'Search by numeric id, exact email, or exact account name. Soft-deleted members are included.',
        'not_found' => 'No member matches that search.',
    ],

    'subject' => [
        'heading' => 'Subject',
        'id' => 'ID',
        'email' => 'Email',
        'account_name' => 'Account name',
        'deleted_at' => 'Soft-deleted at',
    ],

    'scope' => [
        'site' => 'Current site only',
        'network' => 'Whole network (every site + global tables)',
    ],

    'deletion_mode' => [
        'anonymize' => 'Anonymize (irreversible HMAC of identifying fields, rows kept)',
        'soft_delete' => 'Soft delete (mark members.deleted_at, keep child rows)',
        'hard_delete' => 'Hard delete (physically remove every related row)',
    ],

    'export' => [
        'heading' => 'Export',
        'description' => 'Build a ZIP archive with one directory per privacy provider, plus a manifest.json describing the run.',
        'download_site' => 'Download (current site)',
        'download_network' => 'Download (network-wide)',
    ],

    'delete' => [
        'heading' => 'Delete or anonymize',
        'description' => 'Choose a scope and a strategy. The action is dispatched to every registered privacy data provider. Hard-delete on audit_logs and security_events still preserves the rows themselves and only anonymizes their PII so the audit chain stays intact.',
        'scope_label' => 'Scope',
        'mode_label' => 'Strategy',
        'confirm_checkbox' => 'I understand this action affects user data across every privacy provider and may be irreversible.',
        'confirm_prompt' => 'Proceed with the selected privacy operation?',
        'submit' => 'Run privacy operation',
    ],

    'status' => [
        'deletion_completed' => 'Privacy operation completed: :deleted records deleted, :anonymized anonymized, :errors error(s).',
    ],

    'errors' => [
        'invalid_mode' => 'The selected deletion mode is not recognized.',
    ],
];
