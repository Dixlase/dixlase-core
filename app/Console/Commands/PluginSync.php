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

namespace App\Console\Commands;

use App\Services\Plugin\PluginManifestSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * plugin.json の permissions / declares をコード実装に追従して自動更新する。
 *
 *   php artisan dls:plugin:sync DixlasePages --dry-run
 *   php artisan dls:plugin:sync DixlasePages --write
 *   php artisan dls:plugin:sync --all --write
 */
class PluginSync extends Command
{
    protected $signature = 'dls:plugin:sync
                            {plugin? : Plugin directory name (e.g. DixlasePages). Omit with --all.}
                            {--all : Sync all plugins under plugins/}
                            {--dry-run : Show diff only (default)}
                            {--write : Write changes to plugin.json}';

    protected $description = 'Sync plugin.json permissions and declares with detected code usage';

    public function __construct(
        protected PluginManifestSyncService $syncService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $write = (bool) $this->option('write');
        $all = (bool) $this->option('all');
        $target = $this->argument('plugin');

        if (! $all && ! $target) {
            $this->error('Specify a plugin name or use --all.');

            return self::FAILURE;
        }

        $plugins = $all ? $this->collectAllPlugins() : [$target];

        $totalChanged = 0;
        foreach ($plugins as $plugin) {
            $pluginDir = base_path("plugins/{$plugin}");
            if (! File::isDirectory($pluginDir)) {
                $this->warn("Skip: plugins/{$plugin} not found");

                continue;
            }
            if (! File::exists("{$pluginDir}/plugin.json")) {
                $this->warn("Skip: plugins/{$plugin}/plugin.json not found");

                continue;
            }

            $this->line('');
            $this->info("=== {$plugin} ===");

            try {
                $result = $this->syncService->diff($pluginDir, 'plugin');
            } catch (\Throwable $e) {
                $this->error("  Error: {$e->getMessage()}");

                continue;
            }

            if (! $result['changed']) {
                $this->line('  ✓ already in sync');

                continue;
            }

            $totalChanged++;
            $this->renderChanges($result['changes']);

            if ($write) {
                $this->syncService->sync($pluginDir, 'plugin');
                $this->info('  ✓ written to plugin.json');
            } else {
                $this->comment('  (dry-run: pass --write to apply)');
            }
        }

        $this->line('');
        $this->info("Plugins changed: {$totalChanged} / ".count($plugins));

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function collectAllPlugins(): array
    {
        $pluginsDir = base_path('plugins');
        if (! File::isDirectory($pluginsDir)) {
            return [];
        }

        $names = [];
        foreach (File::directories($pluginsDir) as $dir) {
            $name = basename($dir);
            if (File::exists("{$dir}/plugin.json")) {
                $names[] = $name;
            }
        }
        sort($names);

        return $names;
    }

    /**
     * @param  array<int, array{path: string, before: mixed, after: mixed}>  $changes
     */
    protected function renderChanges(array $changes): void
    {
        foreach ($changes as $change) {
            $path = $change['path'];
            $before = $this->stringifyValue($change['before']);
            $after = $this->stringifyValue($change['after']);
            $this->line("  - <fg=red>{$path}: {$before}</> → <fg=green>{$after}</>");
        }
    }

    protected function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '(unset)';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
