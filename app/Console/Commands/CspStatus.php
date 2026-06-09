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

namespace App\Console\Commands;

use App\Models\SecuritySetting;
use Illuminate\Console\Command;

class CspStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the current CSP status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Get CSP settings
            $enabled = SecuritySetting::get('csp_enabled', 0);
            $modeValue = SecuritySetting::get('csp_mode', 0);
            $adminModeValue = SecuritySetting::get('csp_admin_mode');
            $excludeDevTools = SecuritySetting::get('csp_exclude_dev_tools', 1);

            // Convert mode to string
            $mode = match ((int) $modeValue) {
                0 => __('console/commands/csp_status.development_mode'),
                1 => __('console/commands/csp_status.standard_mode'),
                2 => __('console/commands/csp_status.strict_mode'),
                default => 'unknown',
            };

            // Convert admin panel mode to string
            $adminMode = __('console/commands/csp_status.same_as_front');
            if ($adminModeValue !== null && $adminModeValue !== '') {
                $adminMode = match ((int) $adminModeValue) {
                    0 => __('console/commands/csp_status.development_mode'),
                    1 => __('console/commands/csp_status.standard_mode'),
                    2 => __('console/commands/csp_status.strict_mode'),
                    default => 'unknown',
                };
            }

            // Display status
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info(__('console/commands/csp_status.current_csp_status'));
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->line('');

            // CSP enabled/disabled
            if ($enabled) {
                $this->info(__('console/commands/csp_status.csp_enabled'));
            } else {
                $this->warn(__('console/commands/csp_status.csp_disabled'));
            }

            // Mode
            $this->info(__('console/commands/csp_status.frontend_mode', ['mode' => $mode]));
            $this->info(__('console/commands/csp_status.admin_panel_mode', ['adminMode' => $adminMode]));

            // Exclude development tools
            if ($excludeDevTools) {
                $this->info(__('console/commands/csp_status.exclude_dev_tools_enabled'));
            } else {
                $this->line(__('console/commands/csp_status.exclude_dev_tools_disabled'));
            }

            $this->line('');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // Command help
            $this->line('');
            $this->comment(__('console/commands/csp_status.available_commands'));
            $this->line(__('console/commands/csp_status.cmd_disable_csp'));
            $this->line(__('console/commands/csp_status.cmd_enable_csp'));
            $this->line(__('console/commands/csp_status.cmd_set_front_mode'));
            $this->line(__('console/commands/csp_status.cmd_set_admin_mode'));
            $this->line(__('console/commands/csp_status.cmd_display_status'));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error(__('console/commands/csp_status.failed_retrieve_csp_status').$e->getMessage());

            return Command::FAILURE;
        }
    }
}
