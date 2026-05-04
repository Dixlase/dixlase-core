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

use App\Services\Plugin\PluginHealthScorer;
use App\Services\Plugin\PluginManifestSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Display plugin health in human-readable format and auto-fix possible inconsistencies
 *
 *   php artisan dls:plugin:lint DixlasePages
 *   php artisan dls:plugin:lint DixlasePages --fix
 *   php artisan dls:plugin:lint --all
 */
class PluginLint extends Command
{
    protected $signature = 'dls:plugin:lint
                            {plugin? : Plugin directory name (e.g. DixlasePages). Omit with --all.}
                            {--all : Lint all plugins under plugins/}
                            {--fix : Auto-fix solvable issues (calls dls:plugin:sync --write and fills missing author_id / authority_key_id)}';

    protected $description = 'Run health checks on a plugin and report human-friendly diagnostics';

    public function __construct(
        protected PluginHealthScorer $scorer,
        protected PluginManifestSyncService $syncService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $all = (bool) $this->option('all');
        $target = $this->argument('plugin');

        if (! $all && ! $target) {
            $this->error('Specify a plugin name or use --all.');

            return self::FAILURE;
        }

        $plugins = $all ? $this->collectAllPlugins() : [$target];

        $exitCode = self::SUCCESS;
        foreach ($plugins as $plugin) {
            $code = $this->lintOne($plugin, $fix);
            if ($code !== self::SUCCESS) {
                $exitCode = $code;
            }
        }

        return $exitCode;
    }

    protected function lintOne(string $plugin, bool $fix): int
    {
        $pluginDir = base_path("plugins/{$plugin}");
        if (! File::isDirectory($pluginDir) || ! File::exists("{$pluginDir}/plugin.json")) {
            $this->warn("Skip: plugins/{$plugin} not found");

            return self::SUCCESS;
        }

        $this->line('');
        $this->line(__('console/commands/plugin_lint.plugin_health_check', ['plugin' => $plugin]));
        $this->line('');

        // 1. Auto-fix (run first: calculate score in clean state)
        $autoFixApplied = false;
        if ($fix) {
            $autoFixApplied = $this->autoFix($pluginDir, $plugin);
        }

        // 2. Calculate health score
        $slug = $this->resolveSlug($pluginDir);
        $result = $this->scorer->calculate($slug);

        // 3. Display results
        $this->renderIssues($result->issues, $autoFixApplied);
        $this->renderScore($result->score);

        return $result->score >= 70 ? self::SUCCESS : 1;
    }

    /**
     * Fix items that can be auto-corrected
     */
    protected function autoFix(string $pluginDir, string $plugin): bool
    {
        $manifestPath = "{$pluginDir}/plugin.json";
        $manifest = json_decode(File::get($manifestPath), true);
        if (! is_array($manifest)) {
            return false;
        }

        $changed = false;

        // Complement author_id
        if (empty($manifest['author_id'])) {
            $default = config('extension-sources.default_author_id');
            if (! empty($default)) {
                $manifest['author_id'] = $default;
                $changed = true;
                $this->line(__('console/commands/plugin_lint.auto_fix_author_id_set', ['default' => $default]));
            }
        }

        // Complement authority_key_id
        if (empty($manifest['authority_key_id'])) {
            $default = config('extension-sources.default_authority_key_id');
            if (! empty($default)) {
                $manifest['authority_key_id'] = $default;
                $changed = true;
                $this->line(__('console/commands/plugin_lint.auto_fix_authority_key_id_set', ['default' => $default]));
            }
        }

        if ($changed) {
            File::put($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        }

        // Sync permissions / declares
        $syncResult = $this->syncService->diff($pluginDir, 'plugin');
        if ($syncResult['changed']) {
            $this->line(__('console/commands/plugin_lint.auto_fix_sync_permissions_declares', ['plugin' => $plugin]));
            $this->syncService->sync($pluginDir, 'plugin');
            $changed = true;
        }

        if ($changed) {
            $this->line('');
        }

        return $changed;
    }

    /**
     * @param  array<\App\DTO\Plugin\HealthIssue>  $issues
     */
    protected function renderIssues(array $issues, bool $autoFixApplied): void
    {
        if (empty($issues)) {
            $this->line(__('console/commands/plugin_lint.no_issues_detected'));

            return;
        }

        // Group by severity
        $critical = [];
        $warning = [];
        $info = [];
        foreach ($issues as $issue) {
            $severity = $issue->severity;
            if ($severity === 'critical') {
                $critical[] = $issue;
            } elseif ($severity === 'warning') {
                $warning[] = $issue;
            } else {
                $info[] = $issue;
            }
        }

        foreach ($critical as $issue) {
            $this->renderIssue('❌', 'red', $issue);
        }
        foreach ($warning as $issue) {
            $this->renderIssue('⚠️ ', 'yellow', $issue);
        }
        foreach ($info as $issue) {
            $this->renderIssue('ℹ️ ', 'blue', $issue);
        }

        if ($autoFixApplied) {
            $this->line('');
            $this->line(__('console/commands/plugin_lint.auto_fix_results_note'));
        }
    }

    protected function renderIssue(string $icon, string $color, $issue): void
    {
        $deduction = $issue->deduction;
        $sign = $deduction !== 0 ? sprintf('(%dpt)', $deduction) : '';
        $this->line("  {$icon} <fg={$color}>{$issue->type}</> {$sign}");
        $this->line("     {$issue->description}");

        $hint = $this->getHintFor($issue->type);
        if ($hint) {
            $this->line("     <fg=cyan>💡 {$hint}</>");
        }
    }

    protected function getHintFor(string $type): ?string
    {
        return match ($type) {
            'missing_author_id' => __('console/commands/plugin_lint.auto_insert_config_defaults'),
            'missing_authority_key_id' => __('console/commands/plugin_lint.auto_insert_config_defaults'),
            'permission_undeclared_minor', 'permission_undeclared_major' => __('console/commands/plugin_lint.auto_update_permissions'),
            'permission_unused' => __('console/commands/plugin_lint.align_permissions_with_state'),
            'signature_unsigned' => __('console/commands/plugin_lint.set_security_preset_development'),
            'signature_pending_verification' => __('console/commands/plugin_lint.sign_with_dixlase_signer'),
            'csp_inline_js_required' => __('console/commands/plugin_lint.add_csp_nonce_or_separate_js'),
            'csp_inline_css_required' => __('console/commands/plugin_lint.move_inline_styles_to_css'),
            default => null,
        };
    }

    protected function renderScore(int $score): void
    {
        $this->line('');
        $color = $score >= 90 ? 'green' : ($score >= 70 ? 'yellow' : 'red');
        $this->line(__('console/commands/plugin_lint.health_score_display', ['color' => $color, 'score' => $score]));
        $this->line('');
    }

    protected function resolveSlug(string $pluginDir): string
    {
        $manifest = json_decode(File::get("{$pluginDir}/plugin.json"), true);
        if (is_array($manifest) && ! empty($manifest['slug']) && is_string($manifest['slug'])) {
            return $manifest['slug'];
        }

        // Fallback: infer from directory name
        return \Illuminate\Support\Str::kebab(basename($pluginDir));
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
}
