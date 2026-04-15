<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 */

return [
    'heading' => 'Revisions',
    'description' => 'Review the edit history of the front page and restore a previous version.',

    'index' => [
        'heading' => 'Revision History',
        'description' => 'Review the edit history of the front page and restore a previous version.',
    ],

    'show' => [
        'heading' => 'Revision Details',
        'description' => 'View the differences between the selected revision and the current content.',
    ],

    'back_to_edit' => 'Back to Editor',
    'no_revisions' => 'No revisions have been recorded yet.',
    'created_at' => 'Created At',
    'type' => 'Type',
    'creator' => 'Created By',
    'note' => 'Note',
    'note_placeholder' => 'e.g. Version before redesign',
    'note_help' => 'Add an optional description for this revision (up to 500 characters).',
    'note_save' => 'Save Note',
    'note_updated' => 'Note updated.',
    'note_confirm_title' => 'Save Note',
    'note_confirm_message' => 'Update the note for this revision?',

    'protect' => 'Protect',
    'protect_label_on' => 'Protected',
    'protect_label_off' => 'Protect',
    'protect_enable' => 'Protect',
    'protect_disable' => 'Unprotect',
    'protect_enabled' => 'Revision protected. It will not be auto-deleted.',
    'protect_disabled' => 'Protection removed from this revision.',
    'protect_count_summary' => 'Protected :protected / retention :retention',
    'protect_count_summary_over' => 'Protected :protected (exceeding retention :retention)',
    'protect_help' => 'Protected revisions are exempt from automatic deletion even if the retention count is exceeded.',
    'actions' => 'Actions',
    'view_diff' => 'View Diff',
    'restore' => 'Restore This Version',
    'restore_confirm_title' => 'Restore From Revision',
    'restore_confirm_message' => 'This will overwrite the current front page with the selected revision. The current content will be backed up automatically. Continue?',
    'restore_success' => 'The front page has been restored from the revision.',

    'type_auto' => 'Auto',
    'type_manual' => 'Manual',
    'type_restore_backup' => 'Restore Backup',

    'diff_heading' => 'Differences Between This Revision and the Current Content',
    'diff_field_title' => 'Title',
    'diff_field_content' => 'Content',
    'diff_field_custom_js' => 'Custom JavaScript',
    'diff_field_custom_css' => 'Custom CSS',
    'diff_no_changes' => 'No differences between this revision and the current content.',
    'diff_meta_heading' => 'Metadata Changes',
    'diff_field_storage_type' => 'Storage Type',
    'diff_field_editor_type' => 'Editor Type',
    'diff_field_status' => 'Status',
    'diff_left_label' => 'This Revision',
    'diff_right_label' => 'Current',
    'unknown_user' => 'Unknown',
];
