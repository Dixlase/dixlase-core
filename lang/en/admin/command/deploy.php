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
];
