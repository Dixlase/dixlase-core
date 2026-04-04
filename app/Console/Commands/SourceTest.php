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

use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Console\Command;

class SourceTest extends Command
{
    protected $signature = 'dls:source:test {id : Source ID to test}';

    protected $description = 'Test connection to an extension source';

    public function handle(ExtensionSourceManager $manager): int
    {
        $source = ExtensionSource::query()->find($this->argument('id'));

        if (! $source) {
            $this->error('Source not found.');

            return self::FAILURE;
        }

        $this->info("Testing connection to '{$source->name}' ({$source->type})...");

        try {
            $provider = $manager->makeProvider($source);
            $result = $provider->checkConnection();

            if ($result['success']) {
                $this->info("Connection successful: {$result['message']}");
                if (! empty($result['details'])) {
                    foreach ($result['details'] as $key => $value) {
                        $this->line("  {$key}: {$value}");
                    }
                }
                $source->markChecked();

                return self::SUCCESS;
            }

            $this->error("Connection failed: {$result['message']}");

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
