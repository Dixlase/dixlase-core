<?php

return [

        // Common messages
        'starting' => 'Starting backup...',
        'completed' => 'Backup completed successfully!',
        'no_targets' => 'No backup targets specified.',
        'use_options' => 'Use --all, --core, --plugins, --themes, --custom, --storage-public, --storage-private, --logs, --database',
        'cannot_use_both_only' => 'Cannot use --files-only and --db-only together.',
        'summary' => 'Backup Summary',
        'no_backups_created' => 'No backups were created.',
        'files_saved' => 'File backup: :path',
        'database_saved' => 'Database backup: :path',

        // File backup
        'section_files' => 'File Backup',
        'creating_file_backup' => 'Creating file backup: :file',
        'failed_to_create_zip' => 'Failed to create ZIP file.',
        'path_not_found' => 'Path not found: :path',
        'adding_target' => 'Adding :target: :path',
        'no_files_added' => 'No files were added to the backup.',
        'file_backup_completed' => 'File backup completed: :file (:count files, :size)',
        'no_paths_to_backup' => 'No paths to backup.',

        // Database backup
        'section_database' => 'Database Backup',
        'creating_database_backup' => 'Creating database backup: :file',
        'database_config_not_found' => 'Database configuration not found.',
        'running_mysqldump' => 'Running mysqldump...',
        'mysqldump_failed' => 'mysqldump failed: :error',
        'database_backup_completed' => 'Database backup completed: :file (Tables: :tables, :size)',
        'all_tables' => 'All tables',

        // Backup list
        'list' => [
            'title' => 'Available Backups',
            'file_backups' => 'File Backups',
            'database_backups' => 'Database Backups',
            'no_file_backups' => 'No file backups found.',
            'no_database_backups' => 'No database backups found.',
            'filename' => 'Filename',
            'size' => 'Size',
            'date' => 'Date',
            'backup_directory' => 'Backup directory: :path',
        ],

        // Backup cleanup
        'cleanup' => [
            'invalid_days' => 'Days must be a non-negative integer.',
            'confirm_delete_all' => 'Are you sure you want to delete ALL backups? This action cannot be undone.',
            'confirm_delete_old' => 'Are you sure you want to delete backups older than :days days?',
            'cancelled' => 'Operation cancelled.',
            'starting' => 'Deleting old backups...',
            'no_backups_deleted' => 'No backups were deleted.',
            'deleted_count' => 'Deleted :count backup(s).',
        ],

        // Old backup deletion
        'deleted_old_backup' => 'Deleted old backup: :file',
];
