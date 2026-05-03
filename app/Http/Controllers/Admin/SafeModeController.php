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

namespace App\Http\Controllers\Admin;

use App\Enums\SafeMode;
use App\Services\SafeModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Safe mode management controller
 *
 * Provide safe mode disable operations
 */
class SafeModeController extends AdminLoggedInController
{
    public function __construct(
        protected SafeModeService $safeModeService
    ) {
        parent::__construct();
    }

    /**
     * Disable the specified safe mode
     */
    public function disable(Request $request): RedirectResponse
    {
        $modeValue = $request->input('mode');
        $mode = SafeMode::tryFrom($modeValue);

        if ($mode === null) {
            return redirect()->back()
                ->with('error', __('admin/safe-mode.invalid_mode'));
        }

        $this->safeModeService->deactivate($mode);

        return redirect()->back()
            ->with('success', __('admin/safe-mode.disabled', ['mode' => __('admin/safe-mode.'.$mode->value.'_label')]));
    }

    /**
     * Disable all safe modes
     */
    public function disableAll(Request $request): RedirectResponse
    {
        $this->safeModeService->deactivateAll();

        return redirect()->back()
            ->with('success', __('admin/safe-mode.all_disabled'));
    }
}
