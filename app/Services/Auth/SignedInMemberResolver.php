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

namespace App\Services\Auth;

use App\Models\Member;
use App\Session\GuardAwareDatabaseSessionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Resolve the member signed in to the admin panel, from anywhere.
 *
 * The `guard-aware-database` session driver keeps one cookie and one
 * session id but two rows: a request under the admin URL opens
 * `members_sessions`, every other request opens `sessions`
 * (GuardAwareDatabaseSessionHandler::getCurrentGuard(), which decides on
 * the path alone). The login key therefore exists only in the admin row,
 * and `auth('member')->check()` is false on a front page even though the
 * same browser is signed in to the admin.
 *
 * Front-side features that are meant to recognise a signed-in member --
 * the admin bar, the maintenance banner -- saw nobody because of that.
 * This service reads the member behind the shared session id instead.
 *
 * It is read-only on purpose. The two rows stay separate, so the CSRF
 * token and the flash bag keep belonging to the row the page was
 * rendered against, and `AdminLoginController::destroyFromAdminBar()`
 * keeps working as it does: it ends the admin login by deleting the
 * member row by that same shared id. Merging the rows instead would
 * bring back the 419 failures the split was introduced to fix.
 *
 * A member returned here is signed in, not authorised: the guard is
 * still what grants access to an admin screen. Ask
 * `PermissionService` / `Member::hasPermission()` before showing
 * anything that depends on a permission.
 */
class SignedInMemberResolver
{
    private const GUARD = 'member';

    /**
     * Session id the cached answer belongs to, so a regenerated id is
     * never answered from the previous one.
     */
    private ?string $resolvedFor = null;

    private ?Member $resolved = null;

    /**
     * The member signed in to the admin panel, or null.
     */
    public function resolve(): ?Member
    {
        // Wherever the guard can see the login, it is authoritative: on an
        // admin request (which opens the member row), and on a front
        // request that a remember-me cookie signed back in.
        $fromGuard = $this->fromGuard();

        if ($fromGuard instanceof Member) {
            return $fromGuard;
        }

        $sessionId = $this->currentSessionId();

        if ($sessionId === null) {
            return null;
        }

        if ($this->resolvedFor === $sessionId) {
            return $this->resolved;
        }

        $this->resolvedFor = $sessionId;
        $this->resolved = $this->fromMemberSession($sessionId);

        return $this->resolved;
    }

    /**
     * Whether a member is signed in to the admin panel.
     */
    public function check(): bool
    {
        return $this->resolve() instanceof Member;
    }

    /**
     * Drop the cached answer. Call this after ending a session within the
     * same request.
     */
    public function forget(): void
    {
        $this->resolvedFor = null;
        $this->resolved = null;
    }

    /**
     * The member the guard already holds, if any.
     */
    private function fromGuard(): ?Member
    {
        try {
            $member = Auth::guard(self::GUARD)->user();
        } catch (\Throwable) {
            // No database (an uninstalled or torn-down site) or no guard.
            return null;
        }

        return $member instanceof Member ? $member : null;
    }

    /**
     * The member recorded on the member guard's session row.
     */
    private function fromMemberSession(string $sessionId): ?Member
    {
        $table = $this->memberSessionTable();

        if ($table === null) {
            return null;
        }

        try {
            $row = DB::table($table)
                ->where('id', $sessionId)
                ->first(['member_id', 'last_activity']);
        } catch (\Throwable) {
            return null;
        }

        if ($row === null || $row->member_id === null) {
            return null;
        }

        // An idle row is not a signed-in member. Garbage collection is
        // probabilistic, so a row can outlive the lifetime on disk while
        // the admin side would already reject it.
        $lifetime = (int) config('session.lifetime', 120);

        if ($lifetime > 0 && (int) $row->last_activity < time() - ($lifetime * 60)) {
            return null;
        }

        $member = Member::find($row->member_id);

        // The same account state the admin side enforces on every request
        // (EnsureMemberCanAuthenticate), so a deactivated member stops
        // being visible here at the same moment.
        return $member instanceof Member && $member->canAuthenticate() ? $member : null;
    }

    /**
     * The table the member guard's sessions live in, asked of the handler
     * that owns the mapping rather than repeated here.
     */
    private function memberSessionTable(): ?string
    {
        try {
            $handler = app('session.guard-aware');
        } catch (\Throwable) {
            return null;
        }

        return $handler instanceof GuardAwareDatabaseSessionHandler
            ? $handler->tableForGuard(self::GUARD)
            : null;
    }

    /**
     * The current request's session id, if there is a session at all.
     */
    private function currentSessionId(): ?string
    {
        $request = request();

        if (! $request->hasSession()) {
            return null;
        }

        $id = $request->session()->getId();

        return $id !== '' ? $id : null;
    }
}
