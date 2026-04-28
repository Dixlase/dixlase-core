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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sidebar menu visibility preferences per member
 */
class AdminProfileSidebarController extends AdminLoggedInController
{
    /** @var array<int, string> Menu keys that cannot be hidden */
    private const PROTECTED_MENUS = ['dashboard', 'profile'];

    /**
     * Update sidebar menu visibility preferences
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'hidden' => ['present', 'array'],
            'hidden.*' => ['string', 'regex:/^[a-z0-9_]+(\.[a-z0-9_-]+)*$/'],
            'order' => ['sometimes', 'array'],
            'order.*' => ['array'],
            'order.*.*' => ['string', 'regex:/^[a-z0-9_-]+$/'],
        ]);

        $hidden = $request->input('hidden', []);

        // Remove protected menus and their children from hidden list
        $hidden = array_values(array_filter($hidden, function (string $key): bool {
            foreach (self::PROTECTED_MENUS as $protected) {
                if ($key === $protected || str_starts_with($key, $protected.'.')) {
                    return false;
                }
            }

            return true;
        }));

        $order = $request->input('order', []);

        $member = Auth::guard('member')->user();
        $preferences = ['hidden' => $hidden];
        if (! empty($order)) {
            $preferences['order'] = $order;
        }
        $member->sidebar_preferences = $preferences;
        $member->save();

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Reset sidebar preferences to defaults
     */
    public function reset(): JsonResponse
    {
        $member = Auth::guard('member')->user();
        $member->sidebar_preferences = null;
        $member->save();

        return response()->json([
            'success' => true,
        ]);
    }
}
