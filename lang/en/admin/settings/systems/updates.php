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
 */

return [
    'heading' => 'Updates',
    'description' => 'Review available updates for the core, plugins, and themes, and apply selected items in one batch.',

    // Common
    'check_now' => 'Check Now',
    'apply_selected' => 'Update Selected',
    'apply_one' => 'Update',
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
        'already_in_progress' => 'Another core update or rollback is already running. Wait for it to finish before starting a new one.',
        'in_progress_elapsed' => 'Elapsed: :min m :sec s',
        'in_progress_refresh_note' => 'This page refreshes automatically every 10 seconds.',
        'cli_alternative_heading' => 'Or run from a terminal',
        'cli_alternative_intro' => 'You can also run the upgrade yourself from a terminal — useful if PHP exec() is disabled, or if you want to keep the long-running output in view.',
        'cli_required' => 'Core upgrades must be run from a terminal so the running app is not replaced mid-request. Copy the command below and execute it on the server.',
        'cli_command' => 'docker exec -i dixlase-dev-app php artisan dls:core:update',
        'cli_followups' => 'After the update completes, run `composer install --no-dev` if composer.json changed and `npm install && npm run build` if assets changed, then restart PHP-FPM.',
        'execute_not_implemented' => 'Core update detection is now wired up, but executing core upgrades from the web UI is still being implemented. Use the CLI command shown above for now.',
        'not_implemented' => 'Core update functionality is being prepared in a separate task and will appear here once available.',
        'rollback' => [
            'heading' => 'Roll back the last update',
            'description' => 'Restore the core to v:version — the state captured before the last update.',
            'button' => 'Roll back core',
            'confirm_title' => 'Roll back the core?',
            'confirm_message' => 'This restores the core from v:from back to v:to — the source tree and prebuilt assets captured before the last update. It runs in the background and takes about a minute; reload this page to see the result.',
            'confirm_note' => 'Only this update\'s schema changes are reversed; data created since the update is preserved. If a data migration must also be undone, restore the full database backup from the Backups page.',
            'started' => 'Core rollback started. This runs in the background; reload this page to see the result.',
            'none_to_apply' => 'There is no core rollback point available.',
            'in_progress_title' => 'Core rollback in progress',
            'in_progress_message' => 'Rolling back to v:version. The admin UI will resume as soon as the rollback completes.',
        ],
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
        'action' => 'Action',
    ],

    // Flash messages
    'messages' => [
        'check_done' => 'Update check completed.',
        'check_failed' => 'Update check failed: :error',
        'no_selection' => 'No items selected for update.',
        'apply_summary' => ':succeeded of :total succeeded, :failed failed',
        'backup_failed' => 'Pre-update backup failed, so the update was not started. Error: :error',
        'update_started' => 'The update has started. This page will refresh automatically until it completes.',
        'already_in_progress' => 'A plugin/theme update batch is already running. Wait for it to finish before starting a new one.',
        'core_update_complete' => 'Core update complete: v:from → v:to.',
        'core_rollback_complete' => 'Core rollback complete: v:from → v:to.',
        'update_complete_frame' => 'Updated :subject.',
        'update_complete_plugins' => 'plugin(s) :names',
        'update_complete_themes' => 'theme(s) :names',
        'update_complete_join' => ' and ',
        'update_complete_rollback_hint' => 'If something isn\'t working, you can roll back from :links.',
        'rollback_hint_plugin_detail' => 'the plugin detail page',
        'rollback_hint_plugin_master' => 'the plugin master',
        'rollback_hint_theme_detail' => 'the theme detail page',
        'rollback_hint_theme_master' => 'the theme master',
    ],

    // Polling placeholder shown while a detached plugin/theme update runs
    'extension_in_progress' => [
        'title' => 'Updating extensions...',
        'message' => 'Applying :count update(s). A theme update rebuilds its front-end assets, which can take a few minutes.',
    ],

    // Pre-update backup recommendation
    'backup' => [
        'recommendation_title' => 'Recommended: take a backup before updating',
        'recommendation_body' => 'A botched update can leave files or DB schema in an inconsistent state. Take a backup first so you can restore from the backup page if anything goes wrong.',
        'recommendation_link' => 'Open the backup management page',
        'checkbox_label' => 'Take a backup before updating',
    ],

    // Version-drift banner (VersionDriftService). Shown when the on-disk
    // VERSION file at the repo root disagrees with what the ledger's
    // currentVersion() returns — typically caused by advancing the
    // checkout via `git` instead of `dls:core:update`.
    'drift' => [
        'title' => 'Version drift detected between the ledger and the on-disk code.',
        'body' => 'The core_version_history ledger says the running version is v:ledger, but the VERSION file on disk says v:on_disk. The "available updates" listed below are computed against the ledger, so they may be misleading until the two agree.',
        'kind_ahead' => 'The on-disk code is NEWER than the ledger. This usually means the checkout was advanced via git rather than through dls:core:update, so no history row was recorded. An "available update" older than the on-disk code would be a downgrade — CoreUpdater refuses it, but the safest fix is to reconcile the ledger first.',
        'kind_behind' => 'The on-disk code is OLDER than the ledger. This is unusual; it can happen after a rollback that did not clean up, or a hand-edited VERSION file. Applying any update from this state may not do what the UI implies.',
        'fix_instruction' => 'To reconcile the ledger to the on-disk VERSION, run:',
    ],

    // Subtle note shown under the "Take a backup before updating"
    // checkbox on the core update confirmation modal, so the operator
    // knows what the auto-backup captures without having to open the
    // backup management page separately.
    'core_confirm_backup_note' => 'Backup includes: database + core source + themes.',

    // Confirm modal (bulk apply)
    'confirm' => [
        'title' => 'Run Updates',
        'message' => 'Apply :count selected update(s) sequentially. Continue?',
    ],

    // In-progress modal shown while the "Check Now" request runs
    'checking' => [
        'title' => 'Checking for updates...',
        'message' => 'Contacting the update sources. Please do not close this page.',
    ],

    // In-progress modal (shown while the bulk apply request is being
    // processed by the controller and the page has not yet redirected;
    // the per-row single-update path opens the same modal so its
    // in-flight UX matches the bulk-apply path).
    'in_progress' => [
        'title' => 'Preparing update...',
        'backup_phase' => 'Taking a backup before the update...',
        'starting_phase' => 'Starting the update...',
        'description_line1' => 'Please do not close this page.',
        'description_line2' => 'This may take a moment.',
    ],

    // Confirm modal (single-item per-row "Update" button)
    'single_confirm' => [
        'title' => 'Run Update',
        'message' => 'Update :name?',
    ],

    // Confirm modal (core "更新" button — separate from the per-row
    // single_confirm so the message can name the v:current → v:available
    // bump explicitly)
    'core_confirm' => [
        'title' => 'Update Core',
        'message' => 'Update the core from v:current to v:available. Continue?',
    ],

    // Release notes (changelog from GitHub Releases body, rendered as
    // Markdown under each available update)
    'release_notes' => [
        'heading' => 'Release notes',
        'show' => 'Show release notes',
        'hide' => 'Hide release notes',
        'empty' => 'No release notes were published with this release.',
    ],

    // Inline badge shown on a plugin/theme row whose last update attempt
    // failed (the full failure reason is in the badge's title tooltip).
    'failure' => [
        'previous_failure' => 'Previous update failed (:date)',
    ],
];
