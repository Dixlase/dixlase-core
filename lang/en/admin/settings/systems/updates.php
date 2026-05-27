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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
        'label' => 'Dixlase Core',
        'current_version' => 'Current version: v:version',
        'update_available' => 'Update available',
        'up_to_date' => 'The core is up to date.',
        'release_notes_link' => 'View release notes on GitHub',
        'update_button' => 'Update core now',
        'update_started' => 'Core upgrade to v:version started. This takes roughly one to two minutes. Reload this page to see the result.',
        'no_update_to_apply' => 'No core update is currently available.',
        'exec_disabled' => 'PHP exec() is disabled on this server, so the core upgrade cannot be started from the web UI. Use the CLI command below instead.',
        'php_cli_not_found' => 'No CLI php binary could be located on this server, so the core upgrade cannot be started from the web UI. Use the CLI command below instead.',
        'update_failed_heading' => 'The previous core upgrade failed',
        'in_progress_title' => 'Core update in progress',
        'in_progress_message' => 'Upgrading to v:version. The admin UI will resume as soon as the update completes.',
        'in_progress_elapsed' => 'Elapsed: :min m :sec s',
        'in_progress_refresh_note' => 'This page refreshes automatically every 15 seconds.',
        'cli_alternative_heading' => 'Or run from a terminal',
        'cli_alternative_intro' => 'You can also run the upgrade yourself from a terminal — useful if PHP exec() is disabled, or if you want to keep the long-running output in view.',
        'cli_required' => 'Core upgrades must be run from a terminal so the running app is not replaced mid-request. Copy the command below and execute it on the server.',
        'cli_command' => 'docker exec -i dixlase-dev-app php artisan dls:core:update',
        'cli_followups' => 'After the update completes, run `composer install --no-dev` if composer.json changed and `npm install && npm run build` if assets changed, then restart PHP-FPM.',
        'execute_not_implemented' => 'Core update detection is now wired up, but executing core upgrades from the web UI is still being implemented. Use the CLI command shown above for now.',
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
