<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Enums\MemberRole;
use App\Models\Member;
use Illuminate\Http\Request;

class AdminMemberController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * メンバー一覧
     */
    public function index(Request $request)
    {
        $this->viewParams['members'] = Member::all();

        $search = $request->input('search');
        $roleFilter = $request->input('role', '');
        
        $statusFilter = $request->input('status');
        if ($statusFilter === null && !$request->hasAny(['search', 'role', 'page', 'per_page'])) {
            $statusFilter = '1';
        }
        
        $perPage = $request->input('per_page', 25);
        $allowedPerPage = [10, 25, 50, 100];
        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 25;
        }

        $members = Member::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'like', '%' . $search . '%')
                      ->orWhere('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($roleFilter, function ($query, $roleFilter) {
                $query->where('role', $roleFilter);
            })
            ->when($statusFilter !== null, function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->paginate($perPage);

        $members->appends($request->only(['search', 'role', 'status', 'per_page']));
        
        $pagination = [
            'current_page' => $members->currentPage(),
            'last_page' => $members->lastPage(),
            'prev_page' => $members->currentPage() > 1 ? $members->currentPage() - 1 : null,
            'next_page' => $members->hasMorePages() ? $members->currentPage() + 1 : null,
            'total' => $members->total(),
            'per_page' => $members->perPage(),
            'from' => $members->firstItem(),
            'to' => $members->lastItem(),
        ];
        
        $this->viewParams['members'] = $members;
        $this->viewParams['search'] = $search;
        $this->viewParams['roleFilter'] = $roleFilter;
        $this->viewParams['statusFilter'] = $statusFilter;
        $this->viewParams['pagination'] = $pagination;
        $this->viewParams['roles'] = MemberRole::cases();

        return view('admin.members.index', $this->viewParams);
    }
}
