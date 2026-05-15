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
use Illuminate\Support\Facades\Cache;

class CspSetAdminMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:set-admin {mode? : CSP mode (development/standard/strict/same)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set CSP mode for admin panel (use same to apply frontend mode)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $mode = $this->argument('mode');

            // If mode is not specified, select interactively
            if (! $mode) {
                $mode = $this->choice(
                    __('console/commands/csp_set_admin_mode.select_admin_csp_mode'),
                    ['same', 'development', 'standard', 'strict'],
                    0
                );
            }

            // Validate mode
            $validModes = ['same', 'development', 'standard', 'strict'];
            if (! in_array($mode, $validModes)) {
                $this->error(__('console/commands/csp_set_admin_mode.invalid_mode', ['mode' => $mode]));
                $this->info(__('console/commands/csp_set_admin_mode.valid_modes').implode(', ', $validModes));

                return Command::FAILURE;
            }

            // Set null for 'same' (use the same mode as front)
            if ($mode === 'same') {
                SecuritySetting::set('csp_admin_mode', null);

                // Clear cache
                Cache::flush();

                $this->info(__('console/commands/csp_set_admin_mode.admin_csp_mode_set_to_same_success'));
                $this->info(__('console/commands/csp_set_admin_mode.log_admin_csp_mode_changed_same').now());

                // Log the operation
                \Log::channel('stack')->warning('Executing admin panel CSP mode change command', [
                    'command' => 'dixlase:csp:set-admin',
                    'mode' => 'same (null)',
                    'user' => 'CLI',
                    'timestamp' => now(),
                ]);

                return Command::SUCCESS;
            }

            // Convert mode to numeric value
            $modeValue = match ($mode) {
                'development' => 0,
                'standard' => 1,
                'strict' => 2,
            };

            // Change admin panel CSP mode
            SecuritySetting::set('csp_admin_mode', $modeValue);

            // Clear cache
            Cache::flush();

            $this->info(__('console/commands/csp_set_admin_mode.admin_csp_mode_changed', ['mode' => $mode]));
            $this->info(__('console/commands/csp_set_admin_mode.log_admin_csp_mode_changed').now());

            // Display current front mode
            $frontMode = SecuritySetting::get('csp_mode', 0);
            $frontModeName = match ((int) $frontMode) {
                0 => 'development',
                1 => 'standard',
                2 => 'strict',
                default => 'unknown',
            };

            $this->line('');
            $this->info(__('console/commands/csp_set_admin_mode.current_settings'));
            $this->info(__('console/commands/csp_set_admin_mode.frontend_mode_display', ['frontModeName' => $frontModeName]));
            $this->info(__('console/commands/csp_set_admin_mode.admin_panel_mode_display', ['mode' => $mode]));

            // Log the operation
            \Log::channel('stack')->warning('Executing admin panel CSP mode change command', [
                'command' => 'dixlase:csp:set-admin',
                'mode' => $mode,
                'front_mode' => $frontModeName,
                'user' => 'CLI',
                'timestamp' => now(),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error(__('console/commands/csp_set_admin_mode.admin_csp_mode_change_failed').$e->getMessage());

            return Command::FAILURE;
        }
    }
}
