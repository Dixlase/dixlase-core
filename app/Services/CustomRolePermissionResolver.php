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

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Site\SiteContextInterface;
use App\Enums\PermissionGrantType;
use App\Models\CustomRole;
use App\Models\CustomRolePermissionOverride;
use App\Models\Member;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Reads a member's custom-role overrides.
 *
 * The member's preset (`members.role`) already equals the custom role's
 * base role (CustomRole::assignTo() keeps them in step), so the only thing
 * a custom role adds to a permission check is its overrides. This class
 * answers "does the member's custom role say anything about this key?":
 * a grant, a deny, or nothing -- and nothing means the preset decides,
 * exactly as for a member without a custom role.
 *
 * A member's overrides are ignored, and the preset decides, when the member
 * has no custom role, or the role is inactive or soft-deleted.
 *
 * Overrides are cached per (role, site), and forgotten whenever one of that
 * role's overrides on that site is saved or deleted.
 */
final class CustomRolePermissionResolver
{
    private const CACHE_DOMAIN = 'custom-role-permissions';

    private const CACHE_TTL = 300;

    /** Prefix that marks an override key as a plugin menu key. */
    public const PLUGIN_PREFIX = 'plugin:';

    /**
     * The override key for a plugin menu key.
     *
     * @param  string  $pluginDirectory  The plugin's directory name, as
     *                                   PermissionRegistry takes it
     */
    public static function pluginKey(string $pluginDirectory, string $menuKey): string
    {
        return self::PLUGIN_PREFIX.$pluginDirectory.':'.$menuKey;
    }

    /**
     * The override of the member's custom role for one exact key, if any.
     */
    public static function overrideFor(Member $member, string $key): ?PermissionGrantType
    {
        $type = self::overridesFor($member)[$key] ?? null;

        return $type === null ? null : PermissionGrantType::from($type);
    }

    /**
     * The override for a plugin menu key, taking its ancestors into account.
     *
     * An override on `posts` also covers `posts.settings`. Across the key and
     * its ancestors a deny anywhere wins; otherwise a grant anywhere applies.
     */
    public static function pluginOverrideFor(Member $member, string $pluginDirectory, string $menuKey): ?PermissionGrantType
    {
        $overrides = self::overridesFor($member);
        if ($overrides === []) {
            return null;
        }

        $found = null;
        $segments = explode('.', $menuKey);

        while ($segments !== []) {
            $type = $overrides[self::pluginKey($pluginDirectory, implode('.', $segments))] ?? null;

            if ($type === PermissionGrantType::Deny->value) {
                return PermissionGrantType::Deny;
            }

            if ($type === PermissionGrantType::Grant->value) {
                $found = PermissionGrantType::Grant;
            }

            array_pop($segments);
        }

        return $found;
    }

    /**
     * Every override of the member's active custom role on the current site.
     *
     * @return array<string, string> key => grant_type value
     */
    public static function overridesFor(Member $member): array
    {
        $role = self::activeRole($member);
        if ($role === null) {
            return [];
        }

        $siteId = self::currentSiteId();

        return Cache::remember(
            self::cacheKey($role->id, $siteId),
            self::CACHE_TTL,
            static fn (): array => self::load($role->id, $siteId),
        );
    }

    /**
     * The member's custom role when it is assigned, active and not deleted.
     */
    public static function activeRole(Member $member): ?CustomRole
    {
        if ($member->custom_role_id === null) {
            return null;
        }

        // The relation excludes soft-deleted roles, so a deleted role reads
        // as no role.
        $role = $member->customRole;

        return $role !== null && $role->is_active ? $role : null;
    }

    /**
     * Forget the cached overrides of a role on a site.
     */
    public static function forget(int $customRoleId, ?int $siteId): void
    {
        Cache::forget(self::cacheKey($customRoleId, $siteId));
        // Also drop the no-site-context entry, which merges every site.
        Cache::forget(self::cacheKey($customRoleId, null));
    }

    /**
     * @return array<string, string>
     */
    private static function load(int $customRoleId, ?int $siteId): array
    {
        $query = CustomRolePermissionOverride::query()
            ->withoutGlobalScope('belongs_to_site')
            ->where('custom_role_id', $customRoleId);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $overrides = [];

        foreach ($query->get(['permission', 'grant_type']) as $row) {
            $type = $row->grant_type->value;

            // Without a site context every site's rows are merged; keep a
            // deny over a grant so the merge can only narrow.
            if (($overrides[$row->permission] ?? null) === PermissionGrantType::Deny->value) {
                continue;
            }

            $overrides[$row->permission] = $type;
        }

        return $overrides;
    }

    private static function currentSiteId(): ?int
    {
        try {
            return app(SiteContextInterface::class)->currentSiteId();
        } catch (Throwable) {
            return null;
        }
    }

    private static function cacheKey(int $customRoleId, ?int $siteId): string
    {
        return CacheKey::core(self::CACHE_DOMAIN, $customRoleId.'.'.($siteId ?? 'all'));
    }
}
