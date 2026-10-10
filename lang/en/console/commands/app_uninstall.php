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
    'all_tables_deleted' => 'All database tables deleted.',
    'app_cache_skipped_no_tables' => '⚠️ Application Clear cache skipped (tables may not exist).',
    'application_cache_cleared' => '✔️ Application cache cleared.',
    'backup_error' => '❌ Backup error: ',
    'basic_cache_cleared' => '✔️ Basic cache cleared.',
    'clear_cache_error' => 'Clear cache error occurred: ',
    'composer_autoload_regenerate_failed' => '⚠️ Regenerating Composer autoload failed; run `:command` manually.',
    'composer_autoload_regenerated' => '✔️ Composer autoload regenerated.',
    'config_cache_cleared' => '✔️ Configuration cache cleared.',
    'config_cache_regen_skipped_uninstall' => 'ℹ️ Configuration cache regeneration skipped due to uninstallation completion.',
    'confirm_database_backup' => 'Do you want to backup the database?',
    'confirm_delete_all_tables' => 'Do you want to delete all database tables?',
    'confirm_env_backup' => 'Do you want to backup .env without deleting it?',
    'confirm_uninstall' => 'Do you really want to uninstall?',
    'database_backed_up' => 'Database has been backed up: :dumpFile',
    'env_backed_up' => '✅ .env has been backed up: :backupPath',
    'env_creation_info' => 'ℹ️ A new .env will be created from .env.example during installation.',
    'env_deletion_failed' => '❌ Failed to delete .env. Please delete manually: :envPath',
    'env_file_deleted' => '✅ .env file completely deleted.',
    'env_file_not_found' => '⚠️ .env file does not exist.',
    'force_option_specified' => '⚠️  --force option specified. Executing uninstallation without interaction.',
    'force_skip_database_backup' => '--force option: Skipping database backup.',
    'force_skip_env_backup' => '--force option: Skipping .env backup.',
    'optimize_clear_partially_skipped' => '⚠️ Part of optimize:clear skipped.',
    'plugin_symlink_removed' => '✔️ Plugin asset symbolic link has been removed: :pluginLink',
    'regenerating_composer_autoload' => '🔄 Regenerating Composer autoload...',
    'reinstall_cache_cleared' => '   Since all caches have been cleared,',
    'reinstall_no_additional_commands' => '   reinstallation is possible without additional commands.',
    'reinstall_notes' => '📝 Notes for reinstallation:',
    'session_driver_change_failed' => '⚠️ Failed to change session driver: ',
    'session_driver_changed_to_file' => '✔️ Session driver changed to file.',
    'sqlite_database_deleted' => 'SQLite database file has been deleted: :dbPath',
    'storage_symlink_removed' => '✔️ storage symbolic link removed.',
    'theme_asset_symlinks_removed' => '✔️ Theme asset symbolic links removed.',
    'uninstall_cancelled' => 'Uninstallation cancelled.',
    'uninstall_command_signature' => 'dls:app:uninstall {--force : Execute uninstallation immediately without interaction}',
    'uninstall_completed' => '✅ Uninstallation completed!',
    'uninstall_description' => 'Uninstall the application and delete environment configuration and database.',
    'uninstall_warning_message' => '⚠️  Warning: This operation will completely remove the application!',
];
