<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace Plugins\DixlaseLegal\App\Http\Controllers\Admin;

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Traits\AdminInterfaceTrait;
use App\Traits\AdminLoggedInTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Plugins\DixlaseLegal\App\Http\Requests\Admin\DixlaseLegalUpdateSettingsRequest;

/**
 * 法務プラグイン設定コントローラー
 */
class DixlaseLegalAdminSettingsController extends Controller
{
    use AdminInterfaceTrait;
    use AdminLoggedInTrait;
    use AuthorizesRequests;

    /** @var string クッキー同意バナー設定キー */
    private const COOKIE_CONSENT_KEY = 'dixlase_legal_cookie_consent_enabled';

    public function __construct(
        private BaseSettingRepositoryInterface $settingRepository,
    ) {
        $this->initialize();
        $this->initializeAfterLogin();
    }

    /**
     * 設定画面を表示
     */
    public function index(): View
    {
        $cookieConsentEnabled = (bool) $this->settingRepository->get(self::COOKIE_CONSENT_KEY);

        return view('dixlase-legal::admin.legal-pages.settings.index', array_merge($this->viewParams, [
            'cookieConsentEnabled' => $cookieConsentEnabled,
        ]));
    }

    /**
     * 設定を保存
     */
    public function update(DixlaseLegalUpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settingRepository->set(
            self::COOKIE_CONSENT_KEY,
            ! empty($validated['cookie_consent_enabled']) ? '1' : '0',
        );

        return redirect()
            ->route('dixlase-legal::admin.legal-pages.settings.index')
            ->with('success', __('dixlase-legal::admin/legal-pages/settings.save_success'));
    }
}
