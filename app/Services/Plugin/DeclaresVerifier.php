<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services\Plugin;

use App\DTO\Plugin\DeclaresVerificationResult;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Plugin declares section verification service
 *
 * Compares the declares section of plugin.json with the actual file structure,
 * and detects mismatches between declarations and files
 */
class DeclaresVerifier
{
    /**
     * Verify the declares section
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

        // Verify configs
        $configIssues = $this->verifyConfigs($pluginDir, $declares['configs'] ?? []);
        $issues = array_merge($issues, $configIssues);

        // Verify contracts
        $contractIssues = $this->verifyContracts($pluginDir, $declares['contracts'] ?? []);
        $issues = array_merge($issues, $contractIssues);

        // Verify migrations
        $migrationIssues = $this->verifyMigrations($pluginDir, $declares['migrations'] ?? false);
        $issues = array_merge($issues, $migrationIssues);

        // Verify commands
        $commandIssues = $this->verifyCommands($pluginDir, $declares['commands'] ?? false);
        $issues = array_merge($issues, $commandIssues);

        // Verify middleware
        $middlewareIssues = $this->verifyMiddleware($pluginDir, $declares['middleware'] ?? false);
        $issues = array_merge($issues, $middlewareIssues);

        // Verify assets
        $assetIssues = $this->verifyAssets($pluginDir, $declares['assets'] ?? null);
        $issues = array_merge($issues, $assetIssues);

        // Calculate declared count and actual count
        $declaredCount = $this->countDeclared($declares);
        $actualCount = $this->countActual($pluginDir);

        return new DeclaresVerificationResult(
            issues: $issues,
            declaredCount: $declaredCount,
            actualCount: $actualCount,
        );
    }

    /**
     * Verify configs (roles, database_cleanup, navigation)
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
                    'description' => __('services/plugin/declares_verifier.declares_config_true_but_path_missing', ['key' => $key, 'relativePath' => $relativePath]),
                ];
            } elseif (! $isDeclared && $fileExists) {
                $issues[] = [
                    'key' => "configs.{$key}",
                    'type' => 'exists_but_undeclared',
                    'description' => __('services/plugin/declares_verifier.path_exists_but_declares_config_false', ['relativePath' => $relativePath, 'key' => $key]),
                ];
            }
        }

        return $issues;
    }

    /**
     * Verify contracts
     *
     * @return array<array{key: string, type: string, description: string}>
     */
    protected function verifyContracts(string $pluginDir, array $declaredContracts): array
    {
        $issues = [];

        // Check if interface files for declared contracts exist
        foreach ($declaredContracts as $contract) {
            // Infer file path from contract name
            // App\Contracts\PluginIntegration\FooInterface → app/Contracts/PluginIntegration/FooInterface.php
            $contractPath = str_replace('\\', '/', $contract);
            $contractPath = str_replace('App/', 'app/', $contractPath);

            $coreContractPath = base_path("{$contractPath}.php");
            if (! File::exists($coreContractPath)) {
                $issues[] = [
                    'key' => "contracts.{$contract}",
                    'type' => 'declared_but_missing',
                    'description' => __('services/plugin/declares_verifier.declared_contract_file_not_found', ['contract' => $contract]),
                ];
            }
        }

        return $issues;
    }

    /**
     * Verify migrations
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
                'description' => __('services/plugin/declares_verifier.migrations_true_but_files_missing'),
            ];
        } elseif (! $isDeclared && $hasMigrations) {
            $issues[] = [
                'key' => 'migrations',
                'type' => 'exists_but_undeclared',
                'description' => __('services/plugin/declares_verifier.migration_files_exist_but_false'),
            ];
        }

        return $issues;
    }

    /**
     * Verify commands
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
                'description' => __('services/plugin/declares_verifier.commands_true_but_files_missing'),
            ];
        } elseif (! $isDeclared && $hasCommands) {
            $issues[] = [
                'key' => 'commands',
                'type' => 'exists_but_undeclared',
                'description' => __('services/plugin/declares_verifier.command_files_exist_but_false'),
            ];
        }

        return $issues;
    }

    /**
     * Verify middleware
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
                'description' => __('services/plugin/declares_verifier.middleware_true_but_files_missing'),
            ];
        } elseif (! $isDeclared && $hasMiddleware) {
            $issues[] = [
                'key' => 'middleware',
                'type' => 'exists_but_undeclared',
                'description' => __('services/plugin/declares_verifier.middleware_files_exist_but_false'),
            ];
        }

        return $issues;
    }

    /**
     * Verify assets
     *
     * @param  array{common?: string[], admin?: string[], front?: string[]}|false|null  $declaredAssets
     * @return array<array{key: string, type: string, description: string}>
     */
    protected function verifyAssets(string $pluginDir, array|false|null $declaredAssets): array
    {
        $issues = [];
        $resourceSrcDir = "{$pluginDir}/resources/src";
        $hasAssetFiles = $this->hasAssetSourceFiles($resourceSrcDir);

        if ($declaredAssets === false) {
            // When declared as no assets but asset files actually exist
            if ($hasAssetFiles) {
                $issues[] = [
                    'key' => 'assets',
                    'type' => 'exists_but_undeclared',
                    'description' => __('services/plugin/declares_verifier.asset_files_exist_but_false'),
                ];
            }

            return $issues;
        }

        if (is_array($declaredAssets)) {
            // Verify that each declared asset file exists
            foreach (['common', 'admin', 'front'] as $scope) {
                $files = $declaredAssets[$scope] ?? [];
                foreach ($files as $file) {
                    $filePath = "{$resourceSrcDir}/{$file}";
                    if (! File::exists($filePath)) {
                        $issues[] = [
                            'key' => "assets.{$scope}.{$file}",
                            'type' => 'declared_but_missing',
                            'description' => __('services/plugin/declares_verifier.asset_declared_but_file_missing', ['scope' => $scope, 'file' => $file, 'file2' => $file]),
                        ];
                    }
                }
            }

            return $issues;
        }

        // When undeclared (null) but asset files actually exist
        if ($hasAssetFiles) {
            $issues[] = [
                'key' => 'assets',
                'type' => 'exists_but_undeclared',
                'description' => __('services/plugin/declares_verifier.asset_files_exist_but_not_declared'),
            ];
        }

        return $issues;
    }

    /**
     * Check if asset source files exist under resources/src/
     */
    protected function hasAssetSourceFiles(string $resourceSrcDir): bool
    {
        if (! File::isDirectory($resourceSrcDir)) {
            return false;
        }

        // Check if files exist in js/ or css/ directories
        foreach (['js', 'css', 'admin'] as $subDir) {
            $dir = "{$resourceSrcDir}/{$subDir}";
            if (File::isDirectory($dir) && count(File::allFiles($dir)) > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate the number of declared items
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

        // boolean fields
        foreach (['migrations', 'commands', 'middleware'] as $key) {
            if ($declares[$key] ?? false) {
                $count++;
            }
        }

        // assets (false counts as a declaration, null/unset does not count)
        if (array_key_exists('assets', $declares)) {
            $count++;
        }

        return $count;
    }

    /**
     * Calculate the number of items that actually exist
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

        // assets (whether asset files exist in resources/src/)
        if ($this->hasAssetSourceFiles("{$pluginDir}/resources/src")) {
            $count++;
        }

        return $count;
    }
}
