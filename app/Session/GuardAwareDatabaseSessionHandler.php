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

namespace App\Session;

use Illuminate\Session\DatabaseSessionHandler;

class GuardAwareDatabaseSessionHandler extends DatabaseSessionHandler
{
    /**
     * Session table configuration per guard
     *
     * @var array
     */
    protected $guardTables = [];

    /**
     * Current guard name
     *
     * @var string|null
     */
    protected $currentGuard = null;

    /**
     * Guard determination logic (custom resolver)
     *
     * @var array
     */
    protected $guardResolvers = [];

    /**
     * Resolved admin URL segment (in-process cache)
     *
     * @var string|null
     */
    protected static $resolvedAdminUrl = null;

    /**
     * Register table configuration per guard
     */
    public function setGuardTable(string $guard, string $table): void
    {
        $this->guardTables[$guard] = $table;
    }

    /**
     * Register guard determination logic
     */
    public function addGuardResolver(callable $resolver): void
    {
        $this->guardResolvers[] = $resolver;
    }

    /**
     * Get table name based on current guard
     */
    protected function getTable(): string
    {
        // Get current authentication guard
        $guard = $this->getCurrentGuard();

        // Use guard-specific table if configured
        if ($guard && isset($this->guardTables[$guard])) {
            return $this->guardTables[$guard];
        }

        // Return default table name
        return $this->table;
    }

    /**
     * Get current guard name
     */
    protected function getCurrentGuard(): ?string
    {
        // Return it if already set
        if ($this->currentGuard !== null) {
            return $this->currentGuard;
        }

        $path = request()->path();
        $guard = null;

        // Execute custom resolver with priority
        foreach ($this->guardResolvers as $resolver) {
            $guard = $resolver(request());
            if ($guard !== null) {
                $this->currentGuard = $guard;

                return $guard;
            }
        }

        // Path-based guard determination (higher priority)

        // Use member guard for admin panel.
        //
        // The admin URL prefix can be customized per install. Resolve it
        // dynamically from `site_settings` (with a static in-process
        // cache) so the right table is picked even when the operator has
        // changed the prefix away from the config default 'admin'.
        // Without this, when admin_url is not "admin", sessions for the
        // customized admin paths land in the default `sessions` table
        // and admin-panel requests hit 419 from `_token` inconsistency.
        //
        // The match is a SEGMENT-level match (exact prefix or prefix
        // followed by '/'). A naive `str_starts_with($path, $prefix)`
        // would also rope in unrelated paths whose first segment merely
        // *starts with* the prefix string — e.g. with the default
        // prefix 'admin', `/admin-bar/logout` would be misrouted to the
        // member guard. That misroute causes its own 419 because the
        // form on the front page renders against the web guard's
        // session row but validation runs against the member guard's
        // row, with independently rotated `_token` values.
        $adminUrl = $this->resolveAdminUrl();
        if (self::pathMatchesSegment($path, 'admin')
            || ($adminUrl !== '' && self::pathMatchesSegment($path, $adminUrl))
        ) {
            $this->currentGuard = 'member';

            return 'member';
        }

        // For Mypage, use user guard
        if (self::pathMatchesSegment($path, 'mypage')) {
            $this->currentGuard = 'user';

            return 'user';
        }

        // Other paths use the default table (sessions)
        // Removed auth state check as it causes circular reference
        $this->currentGuard = null;

        return null;
    }

    /**
     * Whether `$path` is exactly `$prefix` or sits directly under it as
     * a path segment (i.e. `$prefix` followed by `/`).
     *
     * `request()->path()` returns the URI without a leading slash and
     * without a trailing slash, so the matcher only needs to check the
     * exact match and the `<prefix>/` case.
     *
     * Used by `getCurrentGuard()` to keep the path-based guard pick
     * from collapsing onto unrelated routes whose first segment only
     * *starts with* the prefix string (e.g. `/admin-bar/...` under the
     * default `admin` prefix).
     */
    protected static function pathMatchesSegment(string $path, string $prefix): bool
    {
        if ($prefix === '') {
            return false;
        }

        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }

    /**
     * Resolve admin panel URL from DB (with in-process cache)
     *
     * Using SiteSetting::getValue would be ideal, but the handler can be
     * called during early boot, so guard with Schema::hasTable. Fall back to config default when DB is not connected
     * Empty string means 'use config value', not 'disable admin URL detection'
     */
    protected function resolveAdminUrl(): string
    {
        if (self::$resolvedAdminUrl !== null) {
            return self::$resolvedAdminUrl;
        }

        $configDefault = config('admin.url.admin_url', 'admin') ?: 'admin';

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('site_settings')) {
                $dbValue = \App\Models\SiteSetting::getValue('admin_url', $configDefault);
                if (is_string($dbValue) && $dbValue !== '') {
                    return self::$resolvedAdminUrl = $dbValue;
                }
            }
        } catch (\Throwable $e) {
            // Fall back to config default when DB is not connected / before installation
        }

        return self::$resolvedAdminUrl = $configDefault;
    }

    /**
     * Read session data
     *
     * @param  string  $sessionId
     * @return string|null
     */
    public function read($sessionId): string|false
    {
        $session = (object) $this->getQuery()
            ->where('id', $sessionId)
            ->first();

        if ($this->expired($session)) {
            $this->exists = true;

            return '';
        }

        if (isset($session->payload)) {
            $this->exists = true;

            return base64_decode($session->payload);
        }

        return '';
    }

    /**
     * Get query builder (dynamically set table name)
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getQuery()
    {
        return $this->connection->table($this->getTable());
    }

    /**
     * Write session data
     *
     * @param  string  $sessionId
     * @param  string  $data
     */
    public function write($sessionId, $data): bool
    {
        $payload = $this->getDefaultPayload($data);

        if (! $this->exists) {
            $this->read($sessionId);
        }

        if ($this->exists) {
            $this->performUpdate($sessionId, $payload);
        } else {
            $this->performInsert($sessionId, $payload);
        }

        return $this->exists = true;
    }

    /**
     * Get default payload (adjust column names based on table)
     *
     * @param  string  $data
     * @return array
     */
    protected function getDefaultPayload($data)
    {
        $payload = [
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
        ];

        if (! $this->container) {
            return $payload;
        }

        $table = $this->getTable();
        $guard = $this->getCurrentGuard();

        // For guest table (guard is null), do not include user_id/member_id
        if ($guard === null) {
            return array_merge($payload, [
                'ip_address' => $this->ipAddress(),
                'user_agent' => $this->userAgent(),
            ]);
        }

        // For member table, use member_id
        if ($guard === 'member') {
            return array_merge($payload, [
                'member_id' => $this->userId(),
                'ip_address' => $this->ipAddress(),
                'user_agent' => $this->userAgent(),
            ]);
        }

        // For others (user plugins, etc.), use user_id
        return array_merge($payload, [
            'user_id' => $this->userId(),
            'ip_address' => $this->ipAddress(),
            'user_agent' => $this->userAgent(),
        ]);
    }

    /**
     * Update session data
     *
     * @param  string  $sessionId
     * @param  array  $payload
     * @return int
     */
    protected function performUpdate($sessionId, $payload)
    {
        return $this->getQuery()
            ->where('id', $sessionId)
            ->update($payload);
    }

    /**
     * Insert session data
     *
     * @param  string  $sessionId
     * @param  array  $payload
     * @return bool
     */
    protected function performInsert($sessionId, $payload)
    {
        try {
            return $this->getQuery()->insert(array_merge(
                ['id' => $sessionId],
                $payload
            ));
        } catch (\Exception $e) {
            $this->performUpdate($sessionId, $payload);
        }
    }

    /**
     * Filter payload based on table
     */
    protected function filterPayloadForTable(array $payload): array
    {
        $table = $this->getTable();

        // For guest table (sessions), exclude user_id
        if ($table === 'sessions') {
            unset($payload['user_id']);
        }
        // For member table (members_sessions), rename user_id to member_id
        elseif ($table === 'members_sessions' && isset($payload['user_id'])) {
            $payload['member_id'] = $payload['user_id'];
            unset($payload['user_id']);
        }

        return $payload;
    }

    /**
     * Delete the session
     *
     * @param  string  $sessionId
     */
    public function destroy($sessionId): bool
    {
        $this->getQuery()->where('id', $sessionId)->delete();

        return true;
    }

    /**
     * Garbage collect expired sessions
     *
     * @param  int  $lifetime
     */
    public function gc($lifetime): int
    {
        // Clean up each guard's table
        $deleted = 0;

        foreach ($this->guardTables as $guard => $table) {
            $deleted += $this->connection->table($table)
                ->where('last_activity', '<=', $this->currentTime() - $lifetime)
                ->delete();
        }

        // Also clean up the default table
        $deleted += $this->connection->table($this->table)
            ->where('last_activity', '<=', $this->currentTime() - $lifetime)
            ->delete();

        return $deleted;
    }
}
