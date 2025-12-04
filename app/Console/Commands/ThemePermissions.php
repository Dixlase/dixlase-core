<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

use Illuminate\Console\Command;
use App\Services\Theme\ThemePermissionService;
use Illuminate\Support\Str;

/**
 * テーマ権限確認コマンド
 */
class ThemePermissions extends Command
{
    protected $signature = 'dls:theme:permissions 
                            {theme : The theme slug (e.g., dixlase-default-theme)}
                            {--check= : Check specific permission (e.g., assets.custom_js)}
                            {--json : Output as JSON}';

    protected $description = 'Display or check theme permissions';

    public function __construct(
        protected ThemePermissionService $permissionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $themeSlug = $this->argument('theme');
        $checkPermission = $this->option('check');
        $outputJson = $this->option('json');

        // 特定の権限をチェック
        if ($checkPermission) {
            return $this->checkPermission($themeSlug, $checkPermission, $outputJson);
        }

        // 全権限を表示
        return $this->showPermissions($themeSlug, $outputJson);
    }

    protected function checkPermission(string $themeSlug, string $permission, bool $outputJson): int
    {
        $hasPermission = $this->permissionService->check($themeSlug, $permission);

        if ($outputJson) {
            $this->line(json_encode([
                'theme' => $themeSlug,
                'permission' => $permission,
                'allowed' => $hasPermission,
            ], JSON_PRETTY_PRINT));
        } else {
            if ($hasPermission) {
                $this->info("✅ Theme '{$themeSlug}' HAS permission: {$permission}");
            } else {
                $this->error("❌ Theme '{$themeSlug}' does NOT have permission: {$permission}");
            }
        }

        return $hasPermission ? 0 : 1;
    }

    protected function showPermissions(string $themeSlug, bool $outputJson): int
    {
        $permissions = $this->permissionService->getPermissions($themeSlug);

        if ($permissions === null) {
            $this->error("Theme not found or has no permissions defined: {$themeSlug}");
            return 1;
        }

        $summary = $this->permissionService->getSummary($themeSlug);

        if ($outputJson) {
            $this->line(json_encode([
                'theme' => $themeSlug,
                'risk_level' => $summary['risk_level'],
                'permissions' => $permissions,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        // 見やすく表示
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info("🎨 Theme Permissions: {$themeSlug}");
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // リスクレベル
        $riskColor = match($summary['risk_level']) {
            'high' => 'red',
            'medium' => 'yellow',
            default => 'green',
        };
        $this->line("Risk Level: <fg={$riskColor}>" . strtoupper($summary['risk_level']) . "</>");
        $this->newLine();

        // カテゴリごとに表示
        foreach ($permissions as $category => $perms) {
            $this->line("<fg=cyan>📁 {$category}</>");
            foreach ($perms as $key => $value) {
                $status = $this->formatPermissionValue($value);
                $this->line("   {$key}: {$status}");
            }
            $this->newLine();
        }

        return 0;
    }

    protected function formatPermissionValue($value): string
    {
        if (is_bool($value)) {
            return $value ? '<fg=green>✓ Yes</>' : '<fg=gray>✗ No</>';
        }
        if (is_array($value)) {
            if (empty($value)) {
                return '<fg=gray>[] (none)</>';
            }
            return '<fg=yellow>[' . implode(', ', $value) . ']</>';
        }
        return (string) $value;
    }
}
