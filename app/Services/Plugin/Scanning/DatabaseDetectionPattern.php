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
 * Database-related detection patterns
 *
 * Detects database.own_tables, database.core_tables_read, database.core_tables_write
 * core_tables_read: Detects references (reads) to Core tables
 * core_tables_write: Detects write operations to Core tables
 * Excludes cases where there are only use statement imports
 */
class DatabaseDetectionPattern extends DetectionPattern
{
    /**
     * Regex pattern to detect write operations
     */
    protected const WRITE_PATTERNS = [
        '/->save\s*\(/i',
        '/->create\s*\(/i',
        '/->update\s*\(/i',
        '/->delete\s*\(/i',
        '/->forceDelete\s*\(/i',
        '/->insert\s*\(/i',
        '/->upsert\s*\(/i',
        '/DB::table\s*\([\'"][^"\']+[\'"]\)\s*->\s*(insert|update|delete|upsert)\s*\(/i',
    ];

    public function __construct(
        protected string $subKey = 'own_tables',
    ) {}

    public function permissionKey(): string
    {
        return "database.{$this->subKey}";
    }

    public function filePatterns(): array
    {
        if ($this->subKey === 'own_tables') {
            return ['database/migrations/*.php'];
        }

        return [];
    }

    public function regexPatterns(): array
    {
        return match ($this->subKey) {
            'own_tables' => [
                '/Schema::(create|table)\s*\(\s*[\'"](\w+)[\'"]/i',
            ],
            'core_tables_read', 'core_tables_write' => [
                // Any class under the *top-level* App\Models\. Accepts
                // both the FQN form `\App\Models\Foo` and the bare form
                // `App\Models\Foo` (the latter appears in
                // `use App\Models\Foo;` statements, which are themselves
                // evidence that the file consumes that core model — see
                // validateMatch() below). The negative lookbehind
                // (?<![\w\\]) is critical: it excludes the substring
                // when it appears *inside* a deeper namespace such as
                // `\Plugins\DixlaseInquiry\App\Models\Foo` (preceded by
                // `y\`) or `Plugins\DixlaseInquiry\App\Models\Foo`
                // (the `App` preceded by `\`). Without it, every plugin
                // model under Plugins\<Plugin>\App\Models\ would be
                // mis-detected as core_tables_* evidence.
                // The earlier hardcoded shortlist
                // (User|Member|Plugin|Media|Setting|SiteSetting|
                // SecuritySetting) also missed core models like
                // CaptchaEnabledForm, RolePermissionOverride, etc.
                '/(?<![\\w\\\\])\\\\?App\\\\Models\\\\\w+/i',
                // Core facades that wrap core models (read/write the
                // underlying tables). The recommended plugin idiom is
                // `use App\Facades\SiteSettings;` followed by
                // `SiteSettings::get(...)` — the use-statement is the
                // only line containing the FQN, so the regex must
                // accept the no-leading-backslash form too. Same
                // negative-lookbehind rationale as above to exclude
                // deeper-namespaced look-alikes.
                '/(?<![\\w\\\\])\\\\?App\\\\Facades\\\\(SiteSettings|SiteContext|Audit|PluginPermission|Webhook)/i',
                // Common core tables touched via raw DB::table(). The
                // earlier shortlist missed members_role_permissions
                // (role-permission seeders), captcha_enabled_forms
                // (captcha widget readers), and other ancillary tables.
                // Plugin authors who need a table not on this list
                // should fall back to declaring the permission with
                // _optional in plugin.json — the health scorer already
                // honors that escape hatch.
                '/DB::table\s*\(\s*[\'"](users|members|members_role_permissions|plugins|media|settings|site_settings|security_settings|sites|captcha_enabled_forms|role_permission_overrides|audit_logs|webhooks|webhook_deliveries)[\'"]\)/i',
                // Repository contracts under App\Contracts\Repositories\.
                // CLAUDE.md "Cross-Plugin/Theme Data Access" requires
                // plugins to reach core tables via these contracts
                // (e.g. MediaRepositoryInterface) instead of importing
                // App\Models\* directly. Each repository proxies a
                // specific core table — Media -> media,
                // SiteSetting -> site_settings, Plugin -> plugins,
                // etc. — so a contract reference is the strongest
                // static signal we get that the file touches the
                // underlying table, even though the call goes through
                // the abstraction. Without this, plugins that fully
                // migrated to the contract idiom (DixlaseSEO from
                // f3c5e8f onward) get a spurious `unused_declaration`
                // mismatch on their declared core_tables_read entries.
                // Same negative-lookbehind rationale as the App\Models
                // pattern above: excludes deeper-namespaced look-alikes
                // such as Plugins\Foo\App\Contracts\Repositories\Bar.
                '/(?<![\\w\\\\])\\\\?App\\\\Contracts\\\\Repositories\\\\\w+RepositoryInterface/i',
            ],
            default => [],
        };
    }

    /**
     * Decide whether a regex match should count as evidence of the
     * declared permission.
     *
     * For core_tables_read we deliberately do NOT filter `use ` lines:
     * a `use App\Models\Foo;` (or `use App\Facades\SiteSettings;`)
     * statement is itself a declared dependency on a core class, which
     * is the strongest static signal we get when the plugin follows
     * the recommended short-name idiom (`SiteSettings::get(...)`).
     * Pure namespace imports of unrelated classes never reach this
     * method because the regex above only accepts top-level
     * `App\Models\X` and the enumerated `App\Facades\X` paths.
     *
     * For core_tables_write we DO filter `use ` lines, for a different
     * reason: hasWriteOperations() below is a file-level coarse check
     * (any `->save()` / `->create()` / etc. anywhere in the file). A
     * use-statement is a class-level dependency declaration but tells
     * us nothing about whether the write calls in the file actually
     * target *that imported class* — they might just as easily be
     * writes on the plugin's own models. To avoid that false positive,
     * core_tables_write evidence is restricted to inline FQN call sites
     * (`\App\Models\Foo::create(...)` or `DB::table('members')->update`)
     * where the regex match co-occurs with the actual write callsite.
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        if ($this->subKey === 'core_tables_write') {
            // Write evidence requires at least one write call somewhere
            // in the file, AND the match itself must not be a pure
            // import line — see the docblock above for the rationale.
            if (! $this->hasWriteOperations($fileContent)) {
                return false;
            }

            $trimmedLine = ltrim($line);
            if (str_starts_with($trimmedLine, 'use ')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if write operations to Core tables exist in the file
     */
    protected function hasWriteOperations(string $fileContent): bool
    {
        foreach (self::WRITE_PATTERNS as $pattern) {
            if (preg_match($pattern, $fileContent)) {
                return true;
            }
        }

        return false;
    }
}
