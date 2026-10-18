<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Console\Traits;

use App\Support\ExtensionReincludeRules;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Shared body of `dls:sync-gitignore` and `dls:sync-git-exclude`.
 *
 * Both commands used to add a `!plugins/<Name>/` or `!themes/<Name>/` rule
 * for every installed extension, which made core's git track extension
 * directories it ignores on purpose. They now only remove such rules. The
 * default run is a dry-run preview; `--confirm` (or the older `--force`)
 * writes the cleaned file.
 *
 * The using command supplies the file through rulesFilePath(), which tests
 * override to point at a throwaway file.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
trait CleansExtensionReincludeRules
{
    /**
     * Absolute path of the file to clean up.
     */
    abstract protected function rulesFilePath(): string;

    /**
     * Name of the file as shown to the operator.
     */
    abstract protected function rulesFileLabel(): string;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('add-plugin') || $this->option('add-theme')) {
            $this->warn(__('admin/command/git-sync.add_deprecated'));

            return Command::SUCCESS;
        }

        $path = $this->rulesFilePath();
        $label = $this->rulesFileLabel();

        if (! File::exists($path)) {
            $this->info(__('admin/command/git-sync.file_not_found', ['file' => $label]));

            return Command::SUCCESS;
        }

        [$types, $name] = $this->ruleSelection();

        $this->info(__('admin/command/git-sync.scanning', ['file' => $label]));

        $result = ExtensionReincludeRules::strip(File::get($path), $types, $name);

        if ($result['removed'] === []) {
            $this->info(__('admin/command/git-sync.nothing_to_remove', ['file' => $label]));

            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info(__('admin/command/git-sync.rules_found', ['file' => $label]));
        foreach ($result['removed'] as $line) {
            $this->line('  <fg=red>- '.OutputFormatter::escape($line).'</>');
        }
        $this->newLine();

        $apply = ($this->option('confirm') || $this->option('force')) && ! $this->option('dry-run');

        if (! $apply) {
            $this->warn(__('admin/command/git-sync.dry_run'));

            return Command::SUCCESS;
        }

        if (! is_writable($path)) {
            $this->error(__('admin/command/git-sync.not_writable', ['file' => $label]));

            return Command::FAILURE;
        }

        try {
            File::put($path, $result['content']);
        } catch (\Throwable $e) {
            $this->error(__('admin/command/git-sync.failed', ['error' => $e->getMessage()]));
            Log::error("Failed to remove extension re-include rules from {$label}: ".$e->getMessage());

            return Command::FAILURE;
        }

        $this->info(__('admin/command/git-sync.cleaned', [
            'count' => count($result['removed']),
            'file' => $label,
        ]));

        return Command::SUCCESS;
    }

    /**
     * Which rules this run removes: the rule types, and one directory name
     * when --remove-plugin or --remove-theme names it.
     *
     * @return array{0: list<string>, 1: string|null}
     */
    protected function ruleSelection(): array
    {
        $plugin = $this->option('remove-plugin');
        if (is_string($plugin) && $plugin !== '') {
            return [['plugins'], $plugin];
        }

        $theme = $this->option('remove-theme');
        if (is_string($theme) && $theme !== '') {
            return [['themes'], $theme];
        }

        $pluginsOnly = (bool) $this->option('plugins-only');
        $themesOnly = (bool) $this->option('themes-only');

        if ($pluginsOnly && ! $themesOnly) {
            return [['plugins'], null];
        }

        if ($themesOnly && ! $pluginsOnly) {
            return [['themes'], null];
        }

        return [['plugins', 'themes'], null];
    }
}
