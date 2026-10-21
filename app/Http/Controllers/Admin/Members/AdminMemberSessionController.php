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

namespace App\Http\Controllers\Admin\Members;

use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Member;
use App\Services\Member\MemberHierarchyGuard;
use App\Services\Member\MemberSessionService;
use Illuminate\Http\Request;

/**
 * Active admin sessions: the site-wide list and per-session revoke (#674).
 *
 * The list on a member's edit page is filled by AdminMemberFormController;
 * both lists post to {@see revoke()}.
 */
class AdminMemberSessionController extends AdminLoggedInController
{
    public function __construct(private readonly MemberSessionService $sessions)
    {
        parent::__construct();
    }

    /**
     * Sessions of every member the signed-in member may manage
     */
    public function index(Request $request)
    {
        $available = $this->sessions->isAvailable();
        $sessions = $available
            ? $this->sessions->forManageableMembers(AdminHelper::getMember(), $request->session()->getId())
            : null;

        $this->viewParams['sessionsAvailable'] = $available;
        $this->viewParams['sessions'] = $sessions;
        $this->viewParams['pagination'] = $sessions === null ? null : [
            'current_page' => $sessions->currentPage(),
            'last_page' => $sessions->lastPage(),
            'prev_page' => $sessions->currentPage() > 1 ? $sessions->currentPage() - 1 : null,
            'next_page' => $sessions->hasMorePages() ? $sessions->currentPage() + 1 : null,
            'total' => $sessions->total(),
            'per_page' => $sessions->perPage(),
            'from' => $sessions->firstItem(),
            'to' => $sessions->lastItem(),
        ];

        return view('admin.members.sessions', $this->viewParams);
    }

    /**
     * End one session of a member
     */
    public function revoke(Request $request, Member $member, string $session)
    {
        MemberHierarchyGuard::assertCanManage(AdminHelper::getMember(), $member);

        // Ending the session this request runs in is signing out, which has
        // its own button; refusing it here keeps a stray click from doing it.
        if (hash_equals($this->sessions->handleFor($request->session()->getId()), $session)) {
            return redirect()->back()->with('error', __('admin/members/sessions.cannot_revoke_current'));
        }

        if (! $this->sessions->isAvailable() || ! $this->sessions->revoke($member, $session)) {
            return redirect()->back()->with('error', __('admin/members/sessions.not_found'));
        }

        return redirect()->back()->with('success', __('admin/members/sessions.revoked'));
    }
}
