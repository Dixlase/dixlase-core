<?php

return [
    // ========================================
    // DixlaseDeveloper related keys have been moved to
    // plugins/DixlaseDeveloper/lang/*/command.php
    // ========================================

    // scope, class_name_prompt, class_name_required moved to DixlaseDeveloper
    'theme' => [
        'not_found' => 'Theme \':name\' not found.',
        'no_themes_found' => 'No themes found.',
        'select_theme' => 'Please select a theme',
    ],
    'production_warning' => 'You are about to run :action in production environment.',
    'production_confirm' => 'Do you want to continue?',

    // Git sync commands
    'git_sync' => [
        'git_not_found' => 'Git repository not found. .git directory does not exist.',
        'gitignore_not_found' => '.gitignore file not found.',
        'scanning' => 'Scanning directories...',
        'plugins_found' => 'Plugin directories found:',
        'themes_found' => 'Theme directories found:',
        'to_add' => 'to add:',
        'to_remove' => 'to remove:',
        'exclude_in_sync' => '✓ .git/info/exclude is already in sync. No changes needed.',
        'gitignore_in_sync' => '✓ .gitignore is already in sync. No changes needed.',
        'dry_run' => 'Dry run mode. No changes were made.',
        'confirm_apply' => 'Do you want to apply these changes?',
        'cancelled' => 'Operation cancelled.',
        'exclude_synced' => '✓ Successfully synced .git/info/exclude',
        'gitignore_synced' => '✓ Successfully synced .gitignore',
        'already_exists' => 'Exclusion already exists: :path',
        'added' => 'Added exclusion: :path',
        'removed' => 'Removed exclusion: :path',
        'failed' => 'Operation failed: :error',
    ],

    'plugin' => [
        'prompt' => 'Please select a plugin',
        'not_found' => 'Not found plugin.',
        'not_exists' => 'The specified plugin does not exist.',
        'not_selected' => 'No plugin was selected.',
    ],
    'plugin_install' => [
        'description' => 'Install the plugin, register it in the database, run migrations, and update autoload.',
    ],
    'plugin_disable' => [
        'description' => 'Disable a plugin by setting its status to 0 and removing symlinks',
    ],
    'plugin_enable' => [
        'description' => 'Enable a plugin by setting its status to 1 and creating necessary symlinks',
    ],
    'theme_uninstall' => [
        'description' => 'Uninstall a theme (files will be preserved)',
        'theme_name_prompt' => 'The name of the theme to uninstall',
        'theme_not_found' => 'Theme \':themeName\' not found in the database.',
        'not_installed' => 'Theme \':themeName\' is not installed.',
        'cannot_uninstall_enabled' => 'Cannot uninstall enabled theme \':themeName\'.',
        'disable_first' => 'Please disable the theme first using `dls:theme:disable` command before uninstalling.',
        'confirmation' => 'Are you sure you want to uninstall theme \':themeName\'?',
        'cancelled' => 'Uninstallation cancelled.',
        'uninstalled' => 'Uninstalled theme: :themeName',
        'files_preserved' => 'Theme files and directory have been preserved.',
        'delete_hint' => 'To delete the files, run `php artisan theme:delete <directory>` command.',
    ],
    'theme_install' => [
        'description' => 'Install a theme into the database',
        'theme_name_prompt' => 'The name of the theme to install',
        'theme_not_found' => 'Theme \':themeName\' does not exist in the themes directory.',
        'already_registered' => 'Theme \':themeName\' is already registered in the database.',
        'registered' => 'Theme \':themeName\' has been registered in the database.',
        'enable_help' => 'You can now enable it using: php artisan dls:theme:enable :themeName',
    ],
    'theme_disable' => [
        'description' => 'Disable a theme',
        'theme_name_prompt' => 'The name of the theme to disable',
        'no_enabled_themes' => 'No enabled themes found.',
        'theme_not_found' => 'Theme \':themeName\' not found.',
        'not_installed' => 'Theme \':themeName\' is not installed.',
        'already_disabled' => 'Theme \':themeName\' is already disabled.',
        'disabled' => 'Disabled theme: :themeName',
        'list_headers' => ['Name', 'Slug'],
        'disable_help' => 'To disable a theme, run: php artisan dls:theme:disable <theme-name>',
    ],
    'theme_enable' => [
        'description' => 'Switch to a different theme (select theme to enable)',
        'theme_name_prompt' => 'The name of the theme to switch to',
        'no_themes' => 'No themes found in the database.',
        'no_installed_themes' => 'No installed themes available.',
        'theme_not_found' => 'Theme \':themeName\' not found.',
        'not_installed' => 'Theme \':themeName\' is not installed.',
        'install_first' => 'Please install the theme first using `dls:theme:install` command before enabling.',
        'disabled' => 'Disabled theme: :themeName',
        'already_enabled' => 'Theme \':themeName\' is already enabled.',
        'enabled' => 'Enabled theme: :themeName',
        'list_headers' => ['Name', 'Slug', 'Installed', 'Status'],
        'installed' => 'Installed',
        'not_installed_status' => 'Not Installed',
        'status_enabled' => 'Enabled',
        'status_disabled' => 'Disabled',
        'select_prompt' => 'Select a theme to enable',
        'current_marker' => '(Currently Active)',
        'selection_error' => 'Failed to select theme.',
        'symlink_warning' => 'Failed to update symlink, but theme switch completed.',
    ],
    'theme_switch' => [
        'description' => 'Switch to a different theme (select theme to enable)',
        'theme_name_prompt' => 'The name of the theme to switch to',
        'no_installed_themes' => 'No installed themes available.',
        'theme_not_found' => 'Theme \':themeName\' not found.',
        'not_installed' => 'Theme \':themeName\' is not installed.',
        'install_first' => 'Please install the theme first using `dls:theme:install` command before switching.',
        'disabled' => 'Disabled previous theme: :themeName',
        'already_enabled' => 'Theme \':themeName\' is already enabled.',
        'switched' => 'Switched to theme: :themeName',
        'select_prompt' => 'Select a theme to switch to',
        'current_marker' => '(Currently Active)',
        'selection_error' => 'Failed to select theme.',
        'symlink_warning' => 'Failed to update symlink, but theme switch completed.',
    ],
    'theme_delete' => [
        'description' => 'Delete theme files and directory (theme must be uninstalled first)',
        'theme_directory_prompt' => 'The directory name of the theme to delete',
        'force_option' => 'Force delete without confirmation',
        'not_found' => 'Theme directory \':directory\' not found.',
        'still_installed' => 'Theme \':themeName\' is still installed.',
        'still_enabled' => 'Theme \':themeName\' is still enabled.',
        'uninstall_first' => 'Please uninstall the theme first using `dls:theme:uninstall` command before deleting.',
        'disable_first' => 'Please switch to a different theme before deleting.',
        'confirm' => 'Are you sure you want to delete theme directory \':directory\' and all its files? This action cannot be undone.',
        'cancelled' => 'Deletion cancelled.',
        'deleted' => 'Deleted theme directory: :path',
        'failed' => 'Failed to delete theme directory: :error',
        'database_removed' => 'Removed theme \':themeName\' from database.',
        'completed' => 'Theme \':directory\' deletion completed.',
    ],
    // make_theme moved to DixlaseDeveloper
    'plugin_symlink' => [
        'description' => 'Manage plugin asset symlinks',
        'invalid_action' => 'Invalid action. Use "create" or "remove".',
        'created' => 'Symlink created for plugin: :plugin',
        'removed' => 'Symlink removed for plugin: :plugin',
    ],
    'theme_symlink' => [
        'description' => 'Manage theme asset symlinks',
        'invalid_action' => 'Invalid action. Use "create" or "remove".',
        'created' => 'Symlink created for theme: :theme',
        'removed' => 'Symlink removed for theme: :theme',
    ],
    'plugin_autoload_sync' => [
        'description' => 'Synchronize plugins with composer.json PSR-4 settings (and optionally clean up).',
        'success' => 'Composer autoload has been updated (plugins synced).',
    ],
    'plugin_uninstall' => [
        'description' => 'Uninstall the plugin and remove from database (files are preserved).',
        'not_found' => 'Plugin \':pluginName\' not found.',
        'still_enabled' => 'Plugin \':pluginName\' is still enabled.',
        'disable_first' => 'Please disable the plugin first using `plugin:disable` command before uninstalling.',
        'force_disabling' => 'Force disabling plugin \':pluginName\' due to --force option.',
        'confirm' => 'Are you sure you want to uninstall plugin \':pluginName\'? This will remove plugin information from the database.',
        'cancelled' => 'Uninstallation cancelled.',
        'rollback_running' => 'Running migrations rollback...',
        'rollback_confirm' => 'Do you want to delete database tables related to plugin \':pluginName\'?',
        'rollback_skipped' => 'Database rollback was skipped.',
        'files_preserved' => 'Plugin files and directories have been preserved.',
        'database_removed' => 'Plugin \':pluginName\' has been removed from the database.',
        'completed' => 'Plugin \':pluginName\' has been uninstalled successfully.',
        'delete_hint' => 'To delete files, run `php artisan plugin:delete <directory>` command.',
    ],
    'plugin_delete' => [
        'description' => 'Delete plugin files and directories (plugin must be uninstalled first).',
        'not_found' => 'Plugin directory \':directory\' not found.',
        'still_installed' => 'Plugin \':pluginName\' is still installed.',
        'uninstall_first' => 'Please uninstall the plugin first using `plugin:uninstall` command before deleting.',
        'confirm' => 'Are you sure you want to delete plugin directory \':directory\' and all its files? This action cannot be undone.',
        'cancelled' => 'Deletion cancelled.',
        'deleted' => 'Plugin directory \':path\' has been deleted.',
        'failed' => 'Failed to delete plugin directory: :error',
        'completed' => 'Plugin \':directory\' has been deleted successfully.',
    ],
    // class, files, make, license moved to DixlaseDeveloper
    'plugin_autoload' => [
        'description' => 'Add new plugin directories to composer.json autoload (no cleanup).',
        'added' => 'Added new plugin directories to composer.json autoload.',
        'no_changes' => 'No new plugin directories found; no changes made.'
    ],

    // Cleanup Login Attempts Command
    'cleanup_login_attempts' => [
        'days_zero_warning' => 'Days set to 0 - this will delete ALL login attempt records.',
        'confirm_delete_all' => 'Are you sure you want to delete ALL login attempt records? This action cannot be undone.',
        'operation_cancelled' => 'Operation cancelled.',
        'deleting_all' => 'Deleting all login attempt records...',
        'deleted_all_success' => 'Successfully deleted all :count login attempt records.',
        'no_records_found' => 'No login attempt records found to delete.',
        'invalid_days' => 'Days must be a positive integer, or use --all to delete all records.',
        'cleaning_up' => 'Cleaning up login attempts older than :days days...',
        'deleted_old_success' => 'Successfully deleted :count old login attempt records.',
        'no_old_records_found' => 'No old login attempt records found to delete.',
    ],

    // Cleanup Password Reset Tokens Command
    'cleanup_password_reset_tokens' => [
        'days_zero_warning' => 'Days set to 0 - this will delete ALL password reset token records.',
        'confirm_delete_all' => 'Are you sure you want to delete ALL password reset token records? This action cannot be undone.',
        'operation_cancelled' => 'Operation cancelled.',
        'deleting_all' => 'Deleting all password reset token records...',
        'deleted_all_success' => 'Successfully deleted all :count password reset token records.',
        'no_records_found' => 'No password reset token records found to delete.',
        'invalid_days' => 'Days must be a positive integer, or use --all to delete all records.',
        'cleaning_up' => 'Cleaning up password reset tokens older than :days days...',
        'deleted_old_success' => 'Successfully deleted :count old password reset token records.',
        'no_old_records_found' => 'No old password reset token records found to delete.',
    ],

    // Cleanup Trusted Devices Command
    'cleanup_trusted_devices' => [
        'days_zero_warning' => 'Days set to 0 - this will delete ALL trusted device records.',
        'confirm_delete_all' => 'Are you sure you want to delete ALL trusted device records? This action cannot be undone.',
        'operation_cancelled' => 'Operation cancelled.',
        'deleting_all' => 'Deleting all trusted device records...',
        'deleted_all_success' => 'Successfully deleted all :count trusted device records.',
        'no_records_found' => 'No trusted device records found to delete.',
        'invalid_days' => 'Days must be a positive integer, or use --all to delete all records.',
        'cleaning_up' => 'Cleaning up trusted devices older than :days days...',
        'deleted_old_success' => 'Successfully deleted :count old trusted device records.',
        'no_old_records_found' => 'No old trusted device records found to delete.',
    ],

    // Cleanup Two-Factor Tokens Command
    'cleanup_two_factor_tokens' => [
        'days_zero_warning' => 'Days set to 0 - this will delete ALL two-factor token records.',
        'confirm_delete_all' => 'Are you sure you want to delete ALL two-factor token records? This action cannot be undone.',
        'operation_cancelled' => 'Operation cancelled.',
        'deleting_all' => 'Deleting all two-factor token records...',
        'deleted_all_success' => 'Successfully deleted all :count two-factor token records.',
        'no_records_found' => 'No two-factor token records found to delete.',
        'invalid_days' => 'Days must be a positive integer, or use --all to delete all records.',
        'cleaning_up' => 'Cleaning up two-factor tokens older than :days days...',
        'deleted_old_success' => 'Successfully deleted :count old two-factor token records.',
        'no_old_records_found' => 'No old two-factor token records found to delete.',
    ],

    // Cleanup Cache Command
    'cleanup_cache' => [
        'confirm_delete_all' => 'Are you sure you want to delete ALL cache entries and locks? This action cannot be undone.',
        'operation_cancelled' => 'Operation cancelled.',
        'deleting_all' => 'Deleting all cache entries and locks...',
        'deleted_all_success' => 'Successfully deleted :cache_count cache entries and :locks_count cache locks.',
        'no_records_found' => 'No cache records found to delete.',
        'cleaning_all' => 'Cleaning up all cache entries and locks...',
        'cleaning_expired' => 'Cleaning up expired cache entries and locks...',
        'deleted_expired_success' => 'Successfully deleted :cache_count expired cache entries and :locks_count expired cache locks.',
        'no_expired_records_found' => 'No expired cache records found to delete.',
    ],

    // Cleanup Sessions Command
    'cleanup_sessions' => [
        'confirm_delete_all' => 'Are you sure you want to delete ALL session records? This action cannot be undone.',
        'operation_cancelled' => 'Operation cancelled.',
        'deleting_all' => 'Deleting all session records...',
        'deleted_all_success' => 'Successfully deleted all :count session records.',
        'no_records_found' => 'No session records found to delete.',
        'invalid_days' => 'Days must be a positive integer, or use --all to delete all records.',
        'cleaning_up' => 'Cleaning up sessions older than :days days...',
        'deleted_old_success' => 'Successfully deleted :count old session records.',
        'no_old_records_found' => 'No old session records found to delete.',
    ],

    // Custom File Creation Commands
    'make_custom' => [
        'observer' => [
            'description' => 'Create a new Observer class in the custom directory',
        ],
        'service' => [
            'description' => 'Create a new Service class in the custom directory',
        ],
        'validator' => [
            'description' => 'Create a new Validator class in the custom directory',
        ],
        'command' => [
            'description' => 'Create a new Artisan command class in the custom directory',
        ],
    ],

    // Deploy Commands
    'deploy' => [
        'init' => [
            'description' => 'Generate a new dixlase-deploy.json configuration file',
            'already_exists' => 'Configuration file already exists: :path',
            'use_force' => 'Use --force to overwrite',
            'generated' => 'Configuration file generated: :path',
            'next_steps' => 'Next steps:',
            'step_edit' => 'Edit :path with your environment settings',
            'step_env' => 'Set up environment variables for sensitive data',
            'step_doctor' => "Run 'php artisan deploy:doctor' to validate configuration",
            'step_list' => "Run 'php artisan deploy:list' to see available environments",
            'gitignore_warning' => "Important: Add 'dixlase-deploy.json' to your .gitignore to protect credentials",
            'failed' => 'Failed to generate configuration: :error',
        ],
        'doctor' => [
            'description' => 'Check deployment configuration and system requirements',
            'title' => 'Dixlase Deploy Doctor',
            'checking_config' => 'Checking configuration...',
            'config_not_found' => 'Configuration file not found',
            'config_exists' => 'Configuration file exists',
            'config_valid' => 'Configuration is valid',
            'checking_requirements' => 'Checking system requirements...',
            'rsync_installed' => 'rsync is installed',
            'rsync_not_installed' => 'rsync is not installed (required for SSH sync)',
            'lftp_installed' => 'lftp is installed',
            'lftp_not_installed' => 'lftp is not installed (required for FTP sync)',
            'ssh_installed' => 'ssh is installed',
            'ssh_not_installed' => 'ssh is not installed',
            'mysql_installed' => 'mysql client is installed',
            'mysql_not_installed' => 'mysql client is not installed (required for database sync)',
            'mysqldump_installed' => 'mysqldump is installed',
            'mysqldump_not_installed' => 'mysqldump is not installed (required for database sync)',
            'gzip_installed' => 'gzip is installed',
            'gzip_not_installed' => 'gzip is not installed',
            'available_environments' => 'Available environments:',
            'failed_to_load' => 'Failed to load environments: :error',
            'checks_failed' => 'Some checks failed. Please fix the issues above.',
            'all_passed' => 'All checks passed! Ready to deploy.',
        ],
        'list' => [
            'description' => 'List all available deployment environments',
            'config_not_found' => 'Configuration file not found.',
            'no_environments' => 'No environments configured.',
            'title' => 'Available Environments',
            'local' => 'Local:',
            'url' => 'URL:',
            'path' => 'Path:',
            'db' => 'DB:',
            'connection' => 'Connection:',
            'host' => 'Host:',
            'failed' => 'Failed to load configuration: :error',
        ],
        'push' => [
            'description' => 'Push local Dixlase data to a remote environment',
            'config_not_found' => 'Configuration file not found.',
            'environment_not_found' => "Environment ':environment' not found in configuration.",
            'available_environments' => 'Available environments: :environments',
            'no_targets' => 'No sync targets specified.',
            'use_options' => 'Use --plugins, --themes, --custom, --uploads, --database, or --all',
            'push_to' => 'Push to :environment',
            'targets' => 'Targets: :targets',
            'tables' => 'Tables: :tables',
            'dry_run' => '[DRY RUN MODE]',
            'warning_overwrite' => 'WARNING: This will overwrite the database on :vhost',
            'confirm' => 'Are you sure you want to push to :environment?',
            'cancelled' => 'Operation cancelled.',
        ],
        'pull' => [
            'description' => 'Pull remote Dixlase data to local environment',
            'config_not_found' => 'Configuration file not found.',
            'environment_not_found' => "Environment ':environment' not found in configuration.",
            'available_environments' => 'Available environments: :environments',
            'no_targets' => 'No sync targets specified.',
            'use_options' => 'Use --plugins, --themes, --custom, --uploads, --database, or --all',
            'pull_from' => 'Pull from :environment',
            'targets' => 'Targets: :targets',
            'tables' => 'Tables: :tables',
            'dry_run' => '[DRY RUN MODE]',
            'warning_overwrite' => 'WARNING: This will overwrite your local database',
            'confirm' => 'Are you sure you want to pull from :environment?',
            'cancelled' => 'Operation cancelled.',
        ],
        'common' => [
            'starting_push' => 'Starting push to :environment...',
            'starting_pull' => 'Starting pull from :environment...',
            'connecting' => 'Connecting via :connection',
            'connected' => 'Connected successfully',
            'connection_failed' => 'Failed to connect to :environment',
            'executing_hooks' => 'Executing :timing hooks...',
            'running_command' => 'Running: :command (:where)',
            'hook_failed' => 'Hook failed: :output',
            'hook_output' => 'Output: :output',
            'syncing' => 'Syncing :target: :path',
            'sync_failed' => 'Failed to sync :target',
            'would_sync' => '[DRY RUN] Would sync :local <-> :remote',
            'would_sync_db' => '[DRY RUN] Would sync database',
            'push_completed' => 'Push to :environment completed successfully',
            'push_completed_with_errors' => 'Push to :environment completed with errors',
            'pull_completed' => 'Pull from :environment completed successfully',
            'pull_completed_with_errors' => 'Pull from :environment completed with errors',
        ],
    ],

    // Backup Commands
    'backup' => [
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
    ],

    // File integrity check
    'integrity' => [
        // Baseline generation
        'generating_baseline' => 'Generating file integrity baseline...',
        'baseline_exists' => 'Existing baseline found (generated: :date, version: :version)',
        'overwrite_confirm' => 'Do you want to overwrite the existing baseline?',
        'cancelled' => 'Operation cancelled.',
        'scanning_files' => 'Scanning files...',
        'saving_baseline' => 'Saving baseline...',
        'baseline_success' => 'Baseline generated successfully.',
        'baseline_failed' => 'Failed to save baseline.',
        'baseline_generated' => 'File integrity baseline generated',
        'baseline_regenerated' => 'File integrity baseline regenerated',

        // Scan
        'starting_scan' => 'Starting file integrity scan...',
        'scope_not_supported' => 'Scope ":scope" is not currently supported.',
        'using_core_scope' => 'Using core scope.',
        'scanning' => 'Scanning...',
        'scan_error' => 'Scan error: :error',

        // Result display
        'status' => 'Status',
        'status_ok' => 'OK',
        'status_warning' => 'Warning',
        'status_critical' => 'Critical',
        'files_scanned' => 'Files scanned',
        'duration' => 'Duration',
        'summary' => 'Summary',

        // Issue details
        'changed_files' => 'Changed files (:count)',
        'added_files' => 'Added files (:count)',
        'removed_files' => 'Removed files (:count)',
        'suspicious_files' => 'Suspicious files (:count)',

        // Suspicious file reasons
        'reason_php_in_uploads' => 'PHP file in uploads directory',
        'reason_unknown_php_in_public' => 'Unknown PHP file in public root',

        // Critical warning
        'critical_warning' => '⚠️ Critical security issues detected!',
        'critical_action_1' => '1. Review suspicious files immediately.',
        'critical_action_2' => '2. Remove any unauthorized files found.',
        'critical_action_3' => '3. Conduct a security audit of your system.',

        // Summary messages
        'summary_changed' => ':count file(s) changed',
        'summary_added' => ':count file(s) added',
        'summary_removed' => ':count file(s) removed',
        'summary_suspicious' => ':count suspicious file(s) found',
        'summary_ok' => 'No issues detected',

        // Table display
        'item' => 'Item',
        'value' => 'Value',
        'files_count' => 'Files count',
        'app_version' => 'App version',
        'hash_algo' => 'Hash algorithm',
        'generated_at' => 'Generated at',

        // Notification
        'notification_disabled' => 'Notification is disabled.',
        'no_notification_email' => 'Notification email address is not configured.',
        'notification_sent' => 'Alert notification sent to: :email',
        'notification_failed' => 'Failed to send notification: :error',
    ],

    // Webhook related
    'webhook' => [
        'dead_letters' => [
            'no_action' => 'No action specified. Use one of the following options:',
            'option_notify' => 'Send notifications for unnotified dead letters',
            'option_cleanup' => 'Clean up old dead letter records',
            'option_stats' => 'Show dead letter statistics',
            'sending_notifications' => 'Sending notifications for unnotified dead letters...',
            'notifications_sent' => ':count notification(s) sent.',
            'no_pending_notifications' => 'No pending notifications.',
            'cleaning_up' => 'Cleaning up dead letters older than :days days...',
            'cleanup_complete' => ':count record(s) deleted.',
            'stats_title' => 'Webhook Dead Letter Statistics (Last 30 Days)',
            'stat_name' => 'Metric',
            'stat_value' => 'Value',
            'total' => 'Total',
            'pending' => 'Pending',
            'notified' => 'Notified',
            'manually_retried' => 'Manually Retried',
            'by_event' => 'By Event:',
            'event' => 'Event',
            'count' => 'Count',
        ],
    ],

];
