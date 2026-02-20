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

namespace App\Services\Plugin;

use App\DTO\Plugin\DeclaresVerificationResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * プラグイン declares セクション検証サービス
 *
 * plugin.json の declares セクションと実際のファイル構成を照合し、
 * 宣言とファイルの不一致を検出します。
 */
class DeclaresVerifier
{
    /**
     * declares セクションを検証する
     */
    public function verify(string $pluginSlug): DeclaresVerificationResult
    {
        $pluginName = Str::studly(str_replace('-', '_', $pluginSlug));
        $pluginDir = base_path("plugins/{$pluginName}");

        if (! File::isDirectory($pluginDir)) {
            return new DeclaresVerificationResult();
        }

        $pluginJsonPath = "{$pluginDir}/plugin.json";
        if (! File::exists($pluginJsonPath)) {
            return new DeclaresVerificationResult();
        }

        $data = json_decode(File::get($pluginJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new DeclaresVerificationResult();
        }

        $declares = $data['declares'] ?? null;
        $issues = [];

        // configs の検証
        $configIssues = $this->verifyConfigs($pluginDir, $declares['configs'] ?? []);
        $issues = array_merge($issues, $configIssues);

        // contracts の検証
        $contractIssues = $this->verifyContracts($pluginDir, $declares['contracts'] ?? []);
        $issues = array_merge($issues, $contractIssues);

        // migrations の検証
        $migrationIssues = $this->verifyMigrations($pluginDir, $declares['migrations'] ?? false);
        $issues = array_merge($issues, $migrationIssues);

        // commands の検証
        $commandIssues = $this->verifyCommands($pluginDir, $declares['commands'] ?? false);
        $issues = array_merge($issues, $commandIssues);

        // middleware の検証
        $middlewareIssues = $this->verifyMiddleware($pluginDir, $declares['middleware'] ?? false);
        $issues = array_merge($issues, $middlewareIssues);

        // 宣言数と実際の数を計算
        $declaredCount = $this->countDeclared($declares);
        $actualCount = $this->countActual($pluginDir);

        return new DeclaresVerificationResult(
            issues: $issues,
            declaredCount: $declaredCount,
            actualCount: $actualCount,
        );
    }

    /**
     * configs の検証（roles, database_cleanup, navigation）
     *
     * @return array<array{key: string, type: string, description: string}>
     */
    protected function verifyConfigs(string $pluginDir, array $declaredConfigs): array
    {
        $issues = [];
        $configMap = [
            'roles' => 'config/admin/roles.php',
            'database_cleanup' => 'config/admin/database-cleanup.php',
            'navigation' => 'config/admin/navigation.php',
        ];

        foreach ($configMap as $key => $relativePath) {
            $isDeclared = $declaredConfigs[$key] ?? false;
            $fileExists = File::exists("{$pluginDir}/{$relativePath}");

            if ($isDeclared && ! $fileExists) {
                $issues[] = [
                    'key' => "configs.{$key}",
                    'type' => 'declared_but_missing',
                    'description' => "declares.configs.{$key} は true ですが、{$relativePath} が存在しません。",
                ];
            } elseif (! $isDeclared && $fileExists) {
                $issues[] = [
                    'key' => "configs.{$key}",
                    'type' => 'exists_but_undeclared',
                    'description' => "{$relativePath} が存在しますが、declares.configs.{$key} が false です。",
                ];
            }
        }

        return $issues;
    }

    /**
     * contracts の検証
     *
     * @return array<array{key: string, type: string, description: string}>
     */
    protected function verifyContracts(string $pluginDir, array $declaredContracts): array
    {
        $issues = [];

        // 宣言されたコントラクトのインターフェースファイルが存在するか
        foreach ($declaredContracts as $contract) {
            // コントラクト名からファイルパスを推測
            // App\Contracts\PluginIntegration\FooInterface → app/Contracts/PluginIntegration/FooInterface.php
            $contractPath = str_replace('\\', '/', $contract);
            $contractPath = str_replace('App/', 'app/', $contractPath);

            $coreContractPath = base_path("{$contractPath}.php");
            if (! File::exists($coreContractPath)) {
                $issues[] = [
                    'key' => "contracts.{$contract}",
                    'type' => 'declared_but_missing',
                    'description' => "宣言されたコントラクト {$contract} のファイルが見つかりません。",
                ];
            }
        }

        return $issues;
    }

    /**
     * migrations の検証
     *
     * @return array<array{key: string, type: string, description: string}>
     */
    protected function verifyMigrations(string $pluginDir, bool $isDeclared): array
    {
        $issues = [];
        $migrationsDir = "{$pluginDir}/database/migrations";
        $hasMigrations = File::isDirectory($migrationsDir) && count(File::files($migrationsDir)) > 0;

        if ($isDeclared && ! $hasMigrations) {
            $issues[] = [
                'key' => 'migrations',
                'type' => 'declared_but_missing',
                'description' => 'declares.migrations は true ですが、マイグレーションファイルが存在しません。',
            ];
        } elseif (! $isDeclared && $hasMigrations) {
            $issues[] = [
                'key' => 'migrations',
                'type' => 'exists_but_undeclared',
                'description' => 'マイグレーションファイルが存在しますが、declares.migrations が false です。',
            ];
        }

        return $issues;
    }

    /**
     * commands の検証
     *
     * @return array<array{key: string, type: string, description: string}>
     */
    protected function verifyCommands(string $pluginDir, bool $isDeclared): array
    {
        $issues = [];
        $commandsDir = "{$pluginDir}/app/Console/Commands";
        $hasCommands = File::isDirectory($commandsDir) && count(File::files($commandsDir)) > 0;

        if ($isDeclared && ! $hasCommands) {
            $issues[] = [
                'key' => 'commands',
                'type' => 'declared_but_missing',
                'description' => 'declares.commands は true ですが、コマンドファイルが存在しません。',
            ];
        } elseif (! $isDeclared && $hasCommands) {
            $issues[] = [
                'key' => 'commands',
                'type' => 'exists_but_undeclared',
                'description' => 'コマンドファイルが存在しますが、declares.commands が false です。',
            ];
        }

        return $issues;
    }

    /**
     * middleware の検証
     *
     * @return array<array{key: string, type: string, description: string}>
     */
    protected function verifyMiddleware(string $pluginDir, bool $isDeclared): array
    {
        $issues = [];
        $middlewareDir = "{$pluginDir}/app/Http/Middleware";
        $hasMiddleware = File::isDirectory($middlewareDir) && count(File::files($middlewareDir)) > 0;

        if ($isDeclared && ! $hasMiddleware) {
            $issues[] = [
                'key' => 'middleware',
                'type' => 'declared_but_missing',
                'description' => 'declares.middleware は true ですが、ミドルウェアファイルが存在しません。',
            ];
        } elseif (! $isDeclared && $hasMiddleware) {
            $issues[] = [
                'key' => 'middleware',
                'type' => 'exists_but_undeclared',
                'description' => 'ミドルウェアファイルが存在しますが、declares.middleware が false です。',
            ];
        }

        return $issues;
    }

    /**
     * 宣言されたアイテム数を計算
     */
    protected function countDeclared(?array $declares): int
    {
        if ($declares === null) {
            return 0;
        }

        $count = 0;

        // configs
        foreach ($declares['configs'] ?? [] as $value) {
            if ($value) {
                $count++;
            }
        }

        // contracts
        $count += count($declares['contracts'] ?? []);

        // boolean フィールド
        foreach (['migrations', 'commands', 'middleware'] as $key) {
            if ($declares[$key] ?? false) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 実際に存在するアイテム数を計算
     */
    protected function countActual(string $pluginDir): int
    {
        $count = 0;

        // configs
        $configFiles = [
            'config/admin/roles.php',
            'config/admin/database-cleanup.php',
            'config/admin/navigation.php',
        ];
        foreach ($configFiles as $file) {
            if (File::exists("{$pluginDir}/{$file}")) {
                $count++;
            }
        }

        // migrations
        $migrationsDir = "{$pluginDir}/database/migrations";
        if (File::isDirectory($migrationsDir) && count(File::files($migrationsDir)) > 0) {
            $count++;
        }

        // commands
        $commandsDir = "{$pluginDir}/app/Console/Commands";
        if (File::isDirectory($commandsDir) && count(File::files($commandsDir)) > 0) {
            $count++;
        }

        // middleware
        $middlewareDir = "{$pluginDir}/app/Http/Middleware";
        if (File::isDirectory($middlewareDir) && count(File::files($middlewareDir)) > 0) {
            $count++;
        }

        return $count;
    }
}
