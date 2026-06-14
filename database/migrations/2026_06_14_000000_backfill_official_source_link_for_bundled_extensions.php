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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the source link on bundled extensions installed before the
 * install pipeline learned to set it.
 *
 * Extensions installed via the seeder / dls:*:install path used to be
 * recorded with source_id = NULL, which (a) made them show no update
 * and (b) blocked dls:*:update. New installs now default to the
 * official source; this one-time backfill brings already-installed
 * official-vendor extensions in line. It is a no-op on a fresh install
 * (no extensions and no official source exist yet at migrate time) and
 * skips third-party extensions (package_name not under the official
 * vendor) and any extension already linked to a source.
 *
 * The linkage rules are inlined rather than calling
 * ExtensionSourceManager so this migration stays fixed to the
 * behaviour it shipped with, independent of later service changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        $official = DB::table('extension_sources')
            ->where('is_official', true)
            ->where('is_enabled', true)
            ->orderBy('priority')
            ->first();

        if ($official === null) {
            return;
        }

        $owner = $official->owner ?: config('extension-sources.github.default_owner', 'Dixlase');
        $vendorPrefix = strtolower((string) $owner).'/';

        $tables = [
            'plugins' => config('extension-sources.github.repo_prefix', 'plugin-'),
            'themes' => config('extension-sources.github.theme_repo_prefix', 'theme-'),
        ];

        foreach ($tables as $table => $repoPrefix) {
            DB::table($table)
                ->whereNull('source_id')
                ->whereNotNull('installed_at')
                ->get(['id', 'slug', 'package_name'])
                ->each(function ($row) use ($table, $official, $owner, $vendorPrefix, $repoPrefix) {
                    if ($row->package_name === null
                        || ! str_starts_with(strtolower($row->package_name), $vendorPrefix)) {
                        return;
                    }

                    $repo = $repoPrefix.$row->slug;

                    DB::table($table)->where('id', $row->id)->update([
                        'source_id' => $official->id,
                        'source_repo' => $repo,
                        'installed_from_url' => "https://github.com/{$owner}/{$repo}",
                        'installation_method' => $official->type,
                    ]);
                });
        }
    }

    public function down(): void
    {
        // Irreversible data backfill: there is no record of which rows
        // were NULL beforehand, so unlinking on rollback could discard a
        // legitimately-recorded source. Intentionally a no-op.
    }
};
