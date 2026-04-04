<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Console\Command;

class SourceCheck extends Command
{
    protected $signature = 'dls:source:check';

    protected $description = 'Check all installed extensions for available updates';

    public function handle(ExtensionSourceManager $manager): int
    {
        $this->info('Checking for updates...');

        $result = $manager->checkUpdates();

        $pluginUpdates = $result['plugins'];
        $themeUpdates = $result['themes'];

        if (empty($pluginUpdates) && empty($themeUpdates)) {
            $this->info('All extensions are up to date.');

            return self::SUCCESS;
        }

        if (! empty($pluginUpdates)) {
            $this->newLine();
            $this->info('Plugin updates available:');
            $this->table(
                ['Slug', 'Current', 'Available'],
                array_map(fn (array $u) => [$u['slug'], $u['current'], $u['available']], $pluginUpdates)
            );
        }

        if (! empty($themeUpdates)) {
            $this->newLine();
            $this->info('Theme updates available:');
            $this->table(
                ['Slug', 'Current', 'Available'],
                array_map(fn (array $u) => [$u['slug'], $u['current'], $u['available']], $themeUpdates)
            );
        }

        return self::SUCCESS;
    }
}
