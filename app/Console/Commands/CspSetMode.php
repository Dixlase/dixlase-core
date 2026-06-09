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
use Illuminate\Support\Facades\Cache;

class CspSetMode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dixlase:csp:set {mode : CSP mode (development/standard/strict)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change CSP mode';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $mode = $this->argument('mode');

            // Validate mode
            $validModes = ['development', 'standard', 'strict'];
            if (! in_array($mode, $validModes)) {
                $this->error(__('console/commands/csp_set_mode.invalid_mode', ['mode' => $mode]));
                $this->info(__('console/commands/csp_set_mode.csp_valid_modes').implode(', ', $validModes));

                return Command::FAILURE;
            }

            // Convert mode to numeric value
            $modeValue = match ($mode) {
                'development' => 0,
                'standard' => 1,
                'strict' => 2,
            };

            // Change CSP mode
            SecuritySetting::set('csp_mode', $modeValue);

            // Clear cache
            Cache::flush();

            $this->info(__('console/commands/csp_set_mode.csp_mode_changed', ['mode' => $mode]));
            $this->info(__('console/commands/csp_set_mode.csp_mode_changed_log').now());

            // Log to record
            \Log::channel('stack')->warning('Executing CSP mode change command', [
                'command' => 'dixlase:csp:set',
                'mode' => $mode,
                'user' => 'CLI',
                'timestamp' => now(),
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error(__('console/commands/csp_set_mode.csp_mode_change_failed').$e->getMessage());

            return Command::FAILURE;
        }
    }
}
