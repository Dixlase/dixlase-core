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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Actions\Settings\UpdateSettingsAction;
use App\Actors\MemberActor;
use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseContentUpdateRequest;
use App\Services\FrontPageRevisionService;

/**
 * コンテンツ設定コントローラー
 *
 * 全コンテンツタイプ（フロントページ、将来の固定ページ等）に共通するコンテンツ設定を扱う。
 * 現在はリビジョン保持件数のみ。詳細モード限定。
 */
class AdminBaseContentController extends AdminLoggedInController
{
    protected const SETTING_KEYS = [
        FrontPageRevisionService::SETTING_KEY_RETENTION,
    ];

    protected SiteSettingRepositoryInterface $baseSettingRepository;

    public function __construct(SiteSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    public function index()
    {
        $this->viewParams['settings'] = [
            'revision_retention_count' => (int) $this->baseSettingRepository->get(
                FrontPageRevisionService::SETTING_KEY_RETENTION,
                FrontPageRevisionService::DEFAULT_RETENTION
            ),
        ];
        $this->viewParams['maxRetention'] = FrontPageRevisionService::MAX_RETENTION;
        $this->viewParams['defaultRetention'] = FrontPageRevisionService::DEFAULT_RETENTION;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.content');

        return view('admin.settings.base.content', $this->viewParams);
    }

    public function update(AdminBaseContentUpdateRequest $request)
    {
        $actor = new MemberActor(AdminHelper::getMember());

        UpdateSettingsAction::make(
            repository: $this->baseSettingRepository,
            settingsPage: 'base.content',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                $repo->setMultiple([
                    FrontPageRevisionService::SETTING_KEY_RETENTION => (int) $data['revision_retention_count'],
                ]);
            },
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.base.content')
            ->with('success', __('admin/settings/base/content.settings_updated'));
    }
}
