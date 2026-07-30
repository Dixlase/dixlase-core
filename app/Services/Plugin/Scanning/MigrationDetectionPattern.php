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

namespace App\Services\Plugin\Scanning;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Migration-registration detection pattern
 *
 * Detects migrations.stock_migrator — an extension handing its migration
 * directory to Laravel's stock migrator via loadMigrationsFrom().
 *
 * Extension migrations are applied by PluginMigrator / ThemeMigrator and
 * recorded in the dedicated plugin_migrations / theme_migrations ledgers.
 * Registering them with the stock migrator as well makes a bare
 * `php artisan migrate` treat already-applied migrations as pending and
 * try to re-create tables the installer created, which surfaces as
 * SQLSTATE[42S01] and aborts the whole run.
 *
 * Unlike most detections, this one is never legitimate: there is no
 * plugin.json permission to declare that makes it correct. The key is
 * deliberately absent from PluginPermissionService::$defaultPermissions
 * and from PluginManifestSyncService::MANIFEST_PERMISSION_KEYS so it can
 * never be written into a manifest, and PluginAudit surfaces a
 * remediation telling the author to remove the call rather than the
 * generic "declare it" advice.
 *
 * CoreMigrator already neutralises the call at runtime, so a detection
 * here is a hygiene finding rather than an outage: it marks an extension
 * that still carries the pattern the DevKit generator stubs used to emit.
 */
class MigrationDetectionPattern extends DetectionPattern
{
    public function __construct(
        protected string $subKey = 'stock_migrator',
    ) {}

    public function permissionKey(): string
    {
        return "migrations.{$this->subKey}";
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            // Matches every call form seen across the ecosystem:
            //   $this->loadMigrationsFrom(__DIR__.'/../../database/migrations')
            //   $this->loadMigrationsFrom(__DIR__ . "/../../database/migrations")
            //   $this->loadMigrationsFrom($this->basePath.'/database/migrations')
            'stock_migrator' => [
                '/->\s*loadMigrationsFrom\s*\(/i',
            ],
            default => [],
        };
    }

    /**
     * Applies to plugins and themes alike — both own dedicated ledgers.
     */
    public function applicableTo(): string
    {
        return 'both';
    }

    /**
     * Exclude use statement imports only.
     *
     * Commented-out calls are already dropped by the parent, which keeps
     * the explanatory comments that replace the removed call — they name
     * loadMigrationsFrom() precisely so authors do not reintroduce it —
     * from being reported as a live call.
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        $trimmedLine = ltrim($line);

        // Exclude use statement imports only
        if (str_starts_with($trimmedLine, 'use ')) {
            return false;
        }

        return true;
    }
}
