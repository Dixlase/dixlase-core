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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfileAppearanceUpdateRequest;
use Illuminate\Support\Facades\Auth;

class AdminProfileAppearanceController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the appearance edit page.
     */
    public function index()
    {
        // Clear appearance mode session and revert to saved value
        session()->forget('appearance');

        // Enable animation only for profile screen (unified speed)
        $this->setupTransitionClasses();

        // Enable animation only for profile screen
        $this->viewParams['transitionEnabled'] = true;

        return view('admin.profile.appearance', $this->viewParams);
    }

    /**
     * Update appearance.
     */
    public function update(ProfileAppearanceUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();

        $member->update([
            'appearance' => (int) $validated['appearance'] ?? null,
        ]);

        return redirect()->route('admin.profile.appearance')->with('success', __('admin/profile/common.updated'));
    }

    /**
     * Setup transition classes for appearance animation.
     */
    private function setupTransitionClasses()
    {
        $transition = 'transition-colors duration-500';
        $appearanceClass = config('appearance.appearance_class');

        if (isset($appearanceClass['layout'])) {
            foreach ($appearanceClass['layout'] as $key => $value) {
                $appearanceClass['layout'][$key] = $value.' '.$transition;
            }
        }

        if (isset($appearanceClass['sidebar'])) {
            foreach ($appearanceClass['sidebar'] as $key => $value) {
                $appearanceClass['sidebar'][$key] = $value.' '.$transition;
            }
        }

        if (isset($appearanceClass['table'])) {
            foreach ($appearanceClass['table'] as $key => $value) {
                $appearanceClass['table'][$key] = $value.' '.$transition;
            }
        }

        if (isset($appearanceClass['link'])) {
            $appearanceClass['link'] .= ' '.$transition;
        }

        if (isset($appearanceClass['form'])) {
            foreach ($appearanceClass['form'] as $key => $value) {
                $appearanceClass['form'][$key] = $value.' '.$transition;
            }
        }

        config(['appearance.appearance_class' => $appearanceClass]);

        $appearance = (int) (old('appearance') ?? Auth::guard('member')->user()->appearance?->value ?? 0);

        $htmlClass = '';
        if ($appearance === 2 || ($appearance === 0 && request()->cookie('prefers_dark') === '1')) {
            $htmlClass .= 'dark ';
        }

        $this->viewParams['htmlClass'] = trim($htmlClass);
    }
}
