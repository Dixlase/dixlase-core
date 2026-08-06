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

namespace App\Models;

use App\Contracts\Site\SiteContextInterface;
use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Throwable;

/**
 * @api Stable API for plugins/themes (Bearer-token authentication backbone).
 *
 * Site keys are bound to a single site via site_id. Network keys (site_id = null)
 * are CLI-issued only and authenticate against any site. See
 * docs/development/api-reference/versioning.md for the full contract.
 */
class ApiKey extends Model
{
    use BelongsToSite;

    protected $table = 'api_keys';

    protected $fillable = [
        'site_id',
        'name',
        'key_hash',
        'key_prefix',
        'created_by',
        'is_active',
        'environment',
        'scopes',
        'rate_limit',
        'allowed_ips',
        'expires_at',
        'last_used_at',
        'usage_count',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'scopes' => 'array',
        'allowed_ips' => 'array',
        'rate_limit' => 'integer',
        'usage_count' => 'integer',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // ========================================
    // Environment constants
    // ========================================

    public const ENV_LIVE = 'live';

    public const ENV_TEST = 'test';

    // ========================================
    // Scope constants
    // ========================================

    public const SCOPE_READ_EVENTS = 'read:events';

    public const SCOPE_WRITE_EVENTS = 'write:events';

    public const SCOPE_READ_TRANSLATIONS = 'read:translations';

    public const SCOPE_WRITE_TRANSLATIONS = 'write:translations';

    public const SCOPE_READ_CONTENT = 'read:content';

    public const SCOPE_WRITE_CONTENT = 'write:content';

    /**
     * Available scope list
     */
    public static function availableScopes(): array
    {
        return [
            self::SCOPE_READ_EVENTS => __('models/api_key.event_read'),
            self::SCOPE_WRITE_EVENTS => __('models/api_key.event_write'),
            self::SCOPE_READ_TRANSLATIONS => __('models/api_key.translation_read'),
            self::SCOPE_WRITE_TRANSLATIONS => __('models/api_key.translation_write'),
            self::SCOPE_READ_CONTENT => __('models/api_key.content_read'),
            self::SCOPE_WRITE_CONTENT => __('models/api_key.content_write'),
        ];
    }

    // ========================================
    // Relations
    // ========================================

    /**
     * Relation with creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    // ========================================
    // Factory method
    // ========================================

    /**
     * Generate a site-bound API key for the current site. The
     * BelongsToSite trait auto-injects site_id from SiteContext on
     * creation, so the row is always tied to a real site. To create a
     * network key (cross-site), use generateNetworkKey() instead.
     *
     * @param  string  $name  Key name
     * @param  string  $environment  Environment (live/test)
     * @param  array  $scopes  Permission scope
     * @param  int|null  $createdBy  Creator ID
     * @param  array  $options  Other options
     * @return array{model: ApiKey, plain_key: string}
     */
    public static function generate(
        string $name,
        string $environment = self::ENV_LIVE,
        array $scopes = [],
        ?int $createdBy = null,
        array $options = []
    ): array {
        $prefix = $environment === self::ENV_TEST ? 'dxl_test_' : 'dxl_live_';

        $randomKey = Str::random(32);
        $plainKey = $prefix.$randomKey;

        $keyHash = hash('sha256', $plainKey);

        $apiKey = self::create([
            'name' => $name,
            'key_hash' => $keyHash,
            'key_prefix' => $prefix,
            'created_by' => $createdBy,
            'is_active' => true,
            'environment' => $environment,
            'scopes' => $scopes,
            'rate_limit' => $options['rate_limit'] ?? null,
            'allowed_ips' => $options['allowed_ips'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'description' => $options['description'] ?? null,
        ]);

        return [
            'model' => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * Generate a network-scope API key (site_id = null).
     *
     * Network keys authenticate against any site and are intended for
     * cross-site operations. Production rule: only the
     * dls:api:create-network-key Artisan command is allowed to call this
     * method — never expose creation through a web UI.
     *
     * @param  array  $options  Other options (environment, rate_limit, allowed_ips, expires_at, description)
     * @return array{model: ApiKey, plain_key: string}
     */
    public static function generateNetworkKey(
        string $name,
        array $scopes = [],
        ?int $createdBy = null,
        array $options = []
    ): array {
        $environment = $options['environment'] ?? self::ENV_LIVE;
        $prefix = $environment === self::ENV_TEST ? 'dxl_test_' : 'dxl_live_';

        $randomKey = Str::random(32);
        $plainKey = $prefix.$randomKey;

        $keyHash = hash('sha256', $plainKey);

        $apiKey = self::withoutSiteContext(fn () => self::create([
            'site_id' => null,
            'name' => $name,
            'key_hash' => $keyHash,
            'key_prefix' => $prefix,
            'created_by' => $createdBy,
            'is_active' => true,
            'environment' => $environment,
            'scopes' => $scopes,
            'rate_limit' => $options['rate_limit'] ?? null,
            'allowed_ips' => $options['allowed_ips'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'description' => $options['description'] ?? null,
        ]));

        return [
            'model' => $apiKey,
            'plain_key' => $plainKey,
        ];
    }

    /**
     * Validate a plaintext API key for the current request.
     *
     * Resolution rules:
     *   - The lookup bypasses the BelongsToSite global scope so network
     *     keys (site_id = null) and site keys for the current site are
     *     both reachable.
     *   - A site key is rejected unless its site_id matches the current
     *     site resolved by SiteContext.
     *   - A network key is accepted regardless of current site.
     *   - Expired keys (expires_at in the past) are rejected.
     *
     * @param  string  $plainKey  Plaintext bearer token
     * @return self|null Valid ApiKey model, or null when invalid
     */
    public static function validate(string $plainKey): ?self
    {
        $keyHash = hash('sha256', $plainKey);

        $apiKey = self::query()
            ->withoutGlobalScope('belongs_to_site')
            ->where('key_hash', $keyHash)
            ->where('is_active', true)
            ->first();

        if (! $apiKey) {
            return null;
        }

        if ($apiKey->site_id !== null) {
            // Site key: must match the current request's site.
            try {
                $currentSiteId = app(SiteContextInterface::class)->currentSiteId();
            } catch (Throwable) {
                // SiteContext unresolvable — reject site keys conservatively.
                return null;
            }

            if ((int) $apiKey->site_id !== (int) $currentSiteId) {
                return null;
            }
        }

        // Expiration check
        if ($apiKey->expires_at && now()->greaterThan($apiKey->expires_at)) {
            return null;
        }

        return $apiKey;
    }

    // ========================================
    // Instance methods
    // ========================================

    /**
     * Update usage record
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Disable key
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Enable key
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Check if has specified scope
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }

    /**
     * Whether this key is a network (cross-site) key. Network keys have
     * site_id = null and are issued via the dls:api:create-network-key
     * CLI command.
     */
    public function isNetworkKey(): bool
    {
        return $this->site_id === null;
    }

    /**
     * Confirm this key is a network key AND carries the given scope.
     * Use in middleware / authorization gates that protect cross-site
     * operations: a request must present a network key with the right
     * scope to act on data outside the resolved site.
     */
    public function hasNetworkScope(string $scope): bool
    {
        return $this->isNetworkKey() && $this->hasScope($scope);
    }

    /**
     * Check if access from specified IP is allowed
     */
    public function allowsIp(?string $ip): bool
    {
        // Allow all IPs if allowed IP list is empty
        if (empty($this->allowed_ips)) {
            return true;
        }

        // Null-safe (an unresolved client IP is denied against a non-empty
        // list rather than throwing a TypeError) and CIDR-capable, matching
        // the core IP allow-list semantics used elsewhere.
        return \App\Helpers\IpAccessControlHelper::ipMatchesAny($ip, $this->allowed_ips);
    }

    /**
     * Check if expired
     */
    public function isExpired(): bool
    {
        if (! $this->expires_at) {
            return false;
        }

        return now()->greaterThan($this->expires_at);
    }

    /**
     * Get masked key (for display)
     */
    public function getMaskedKey(): string
    {
        return $this->key_prefix.str_repeat('*', 8).'...';
    }

    // ========================================
    // Scope
    // ========================================

    /**
     * Get only active keys
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Filter by environment
     */
    public function scopeForEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    /**
     * Get only non-expired keys
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
