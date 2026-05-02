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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 */

return [
    'heading' => 'Updates',
    'description' => 'Review available updates for the core, plugins, and themes, and apply selected items in one batch.',

    // Common
    'check_now' => 'Check Now',
    'apply_selected' => 'Update Selected',
    'select_all' => 'Select All',
    'last_checked_at' => 'Last checked: :date',
    'never_checked' => 'Never checked',
    'no_updates' => 'Everything is up to date.',
    'all_up_to_date' => 'All installed extensions are at their latest versions.',

    // Section headings
    'core' => [
        'heading' => 'Core',
        'current_version' => 'Current version: v:version',
        'not_implemented' => 'Core update functionality is being prepared in a separate task and will appear here once available.',
    ],
    'plugins' => [
        'heading' => 'Plugins',
        'count' => ':count update(s) available',
        'none' => 'No plugin updates.',
    ],
    'themes' => [
        'heading' => 'Themes',
        'count' => ':count update(s) available',
        'none' => 'No theme updates.',
    ],

    // Table headers
    'table' => [
        'name' => 'Name',
        'current' => 'Current',
        'available' => 'Available',
    ],

    // Flash messages
    'messages' => [
        'check_done' => 'Update check completed.',
        'check_failed' => 'Update check failed: :error',
        'no_selection' => 'No items selected for update.',
        'apply_summary' => ':succeeded of :total succeeded, :failed failed',
    ],

    // Confirm modal
    'confirm' => [
        'title' => 'Run Updates',
        'message' => 'Apply :count selected update(s) sequentially. Continue?',
    ],
];
