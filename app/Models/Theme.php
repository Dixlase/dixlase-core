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

namespace App\Models;

use App\Models\Traits\ResolvesExtensionDirectory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Theme metadata model
 */
class Theme extends Model
{
    use HasFactory;
    use ResolvesExtensionDirectory;

    /**
     * Table name definition
     */
    protected $table = 'themes';

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'name',         // Theme name
        'package_name', // Package name
        'directory',    // Theme directory name
        'slug',         // Theme slug (unique)
        'namespace',    // Theme namespace
        'description',  // Theme description
        'license',      // License
        'author',       // Author
        'email',        // Author email
        'url',          // Author website
        'version',      // Theme version
        'has_settings', // Whether theme has settings page
        'config',       // Theme settings
        'installed_at', // Installation datetime
        'source_id',
        'source_repo',
        'available_version',
        'last_notified_version',
        'available_version_published_at',
        'release_url',
        'release_notes',
        'last_version_check',
        'update_failed_at',
        'update_failure_reason',
        // Supply-chain defense columns (mirror Plugin model)
        'signing_key_id',
        'author_id',
        'authority_key_id',
        'installed_from_url',
        'installation_method',
    ];

    /**
     * Cast settings
     */
    protected $casts = [
        'config' => 'array',
        'has_settings' => 'boolean',
        'installed_at' => 'datetime',
        'last_version_check' => 'datetime',
        'available_version_published_at' => 'datetime',
        'update_failed_at' => 'datetime',
    ];

    /**
     * Extension source that this theme was installed from
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ExtensionSource::class, 'source_id');
    }

    /**
     * Check if an update is available from the source
     */
    public function hasUpdateAvailable(): bool
    {
        return $this->available_version !== null
            && version_compare($this->available_version, $this->version, '>');
    }

    /**
     * Scope for installed themes
     */
    public function scopeInstalled($query)
    {
        return $query->whereNotNull('installed_at');
    }

    /**
     * Check if theme is installed
     */
    public function isInstalled(): bool
    {
        return ! is_null($this->installed_at);
    }

    /**
     * Check if the theme is activated
     */
    public function isEnabled(): bool
    {
        $themeSetting = \DB::table('theme_settings')
            ->where('key', 'enabled_theme_id')
            ->first();
        $enabledThemeId = $themeSetting ? (int) $themeSetting->value : null;

        return $enabledThemeId && $this->id == $enabledThemeId;
    }

    /**
     * Get the default theme
     */
    public static function getDefaultTheme()
    {
        return self::where('is_default', true)->first();
    }

    /**
     * {@inheritDoc}
     */
    protected static function extensionRootDirectory(): string
    {
        return 'themes';
    }

    /**
     * Resolve a theme's canonical slug from its directory name.
     *
     * The slug is the theme's declared identity (theme.json `slug`), and it
     * is the key the theme_migrations ledger is written under by the update
     * path. Commands that only receive a directory name (dls:theme:migrate,
     * dls:theme:migrate:rollback) must NOT re-derive the slug from the
     * directory via Str::kebab / Str::headline — for a directory like
     * `DixlaseOnePage` that yields `dixlase-one-page`, which mismatches the
     * declared `dixlase-onepage` and makes migration rollback look under the
     * wrong ledger key (leaving orphan schema + ledger rows).
     *
     * Resolution order: the installed theme's recorded slug (DB) → the
     * theme.json manifest declaration → a last-resort kebab of the directory.
     */
    public static function resolveSlug(string $directory): string
    {
        $slug = static::query()->where('directory', $directory)->value('slug');
        if (is_string($slug) && $slug !== '') {
            return $slug;
        }

        $manifest = base_path("themes/{$directory}/theme.json");
        if (is_file($manifest)) {
            $data = json_decode((string) file_get_contents($manifest), true);
            if (is_array($data) && isset($data['slug']) && is_string($data['slug']) && $data['slug'] !== '') {
                return $data['slug'];
            }
        }

        return \Illuminate\Support\Str::kebab($directory);
    }

    /**
     * Prevent theme deletion (default theme cannot be deleted)
     */
    public function deleteTheme()
    {
        if ($this->is_default) {
            throw new \Exception('Cannot delete default theme');
        }

        $this->delete();
    }
}
