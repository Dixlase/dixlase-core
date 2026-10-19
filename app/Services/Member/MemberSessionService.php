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

namespace App\Services\Member;

use App\Models\AuditLog;
use App\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * List and revoke the admin sessions of members (#674).
 *
 * Admin sessions live in `members_sessions`, one row per session, written by
 * the guard-aware database session handler. An installed site always uses
 * that handler (ApplySessionConfig sets it on every request, whatever
 * SESSION_DRIVER says), so {@see isAvailable()} is false only where it has
 * not run or the table is missing; the screens then say so instead of
 * showing an empty list.
 *
 * A session is never identified to the browser by its id: the id is the
 * value of the session cookie, and putting it in a page would let anyone who
 * can read the page take the session over. Rows are addressed by a keyed
 * hash of the id instead ({@see handleFor()}).
 */
final class MemberSessionService
{
    public const TABLE = 'members_sessions';

    public function isAvailable(): bool
    {
        return config('session.driver') === 'guard-aware-database'
            && Schema::hasTable(self::TABLE);
    }

    /**
     * Active sessions of one member, most recently used first.
     *
     * @return list<array{handle: string, member_id: int, ip_address: ?string, user_agent: ?string, device: string, started_at: ?Carbon, last_activity: Carbon, current: bool}>
     */
    public function forMember(Member $member, ?string $currentSessionId = null): array
    {
        return $this->activeRows()
            ->where('member_id', $member->getKey())
            ->get()
            ->map(fn (object $row) => $this->present($row, $currentSessionId))
            ->all();
    }

    /**
     * Active sessions of every member the actor may manage (their own rank
     * and below, including themselves), most recently used first.
     */
    public function forManageableMembers(Member $actor, ?string $currentSessionId = null, int $perPage = 50): LengthAwarePaginator
    {
        $members = (new Member())->getTable();

        $paginator = $this->activeRows()
            ->join($members, $members.'.id', '=', self::TABLE.'.member_id')
            ->where($members.'.role', '<=', MemberHierarchyGuard::rank($actor))
            ->addSelect([$members.'.account_name', $members.'.display_name'])
            ->paginate($perPage);

        $paginator->through(fn (object $row): array => array_merge($this->present($row, $currentSessionId), [
            'account_name' => $row->account_name,
            'display_name' => $row->display_name,
        ]));

        return $paginator;
    }

    /**
     * End one session of a member.
     *
     * The member's remember-me token is cleared as well: a device that
     * signed in with "remember me" would otherwise sign straight back in
     * on its next request. Other sessions stay signed in, but their
     * remember-me cookies stop working.
     *
     * @return bool false when the member has no session with that handle
     */
    public function revoke(Member $member, string $handle): bool
    {
        $row = $this->activeRows()
            ->where('member_id', $member->getKey())
            ->get()
            ->first(fn (object $row) => hash_equals($this->handleFor($row->id), $handle));

        if ($row === null) {
            return false;
        }

        DB::table(self::TABLE)->where('id', $row->id)->delete();
        $member->forceFill(['remember_token' => null])->saveQuietly();

        MemberSecurityAudit::record(AuditLog::ACTION_SESSION_DESTROYED, $member, [
            'session' => $handle,
            'device' => self::describeDevice($row->user_agent),
            'remember_token_cleared' => true,
        ]);

        return true;
    }

    /**
     * The handle that stands for a session in pages and URLs.
     */
    public function handleFor(string $sessionId): string
    {
        return substr(hash_hmac('sha256', 'members-session:'.$sessionId, (string) config('app.key')), 0, 32);
    }

    /**
     * "Browser on OS" from a user agent, or "" when neither is recognised.
     * The raw string stays available for anything this does not cover.
     */
    public static function describeDevice(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return '';
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Firefox/') || str_contains($userAgent, 'FxiOS/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') || str_contains($userAgent, 'CriOS/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => null,
        };

        $os = match (true) {
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'CrOS') => 'ChromeOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return match (true) {
            $browser !== null && $os !== null => __('admin/members/sessions.device', ['browser' => $browser, 'os' => $os]),
            default => $browser ?? $os ?? '',
        };
    }

    /**
     * Rows of members_sessions that are still within the session lifetime.
     * Garbage collection is probabilistic, so expired rows can remain.
     */
    private function activeRows(): Builder
    {
        $lifetime = (int) config('session.lifetime', 120);

        $columns = ['id', 'member_id', 'ip_address', 'user_agent', 'last_activity'];
        if (Schema::hasColumn(self::TABLE, 'started_at')) {
            $columns[] = 'started_at';
        }

        return DB::table(self::TABLE)
            ->select(array_map(fn (string $column) => self::TABLE.'.'.$column, $columns))
            ->whereNotNull(self::TABLE.'.member_id')
            ->when($lifetime > 0, fn (Builder $query) => $query->where(self::TABLE.'.last_activity', '>=', time() - $lifetime * 60))
            ->orderByDesc(self::TABLE.'.last_activity');
    }

    /**
     * @return array{handle: string, member_id: int, ip_address: ?string, user_agent: ?string, device: string, started_at: ?Carbon, last_activity: Carbon, current: bool}
     */
    private function present(object $row, ?string $currentSessionId): array
    {
        $startedAt = $row->started_at ?? null;

        return [
            'handle' => $this->handleFor($row->id),
            'member_id' => (int) $row->member_id,
            'ip_address' => $row->ip_address,
            'user_agent' => $row->user_agent,
            'device' => self::describeDevice($row->user_agent),
            'started_at' => $startedAt !== null ? Carbon::createFromTimestamp((int) $startedAt, config('app.timezone')) : null,
            'last_activity' => Carbon::createFromTimestamp((int) $row->last_activity, config('app.timezone')),
            'current' => $currentSessionId !== null && hash_equals($row->id, $currentSessionId),
        ];
    }
}
