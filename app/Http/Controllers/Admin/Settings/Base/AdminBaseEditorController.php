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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Actions\Settings\UpdateSettingsAction;
use App\Actors\MemberActor;
use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseEditorUpdateRequest;
use App\Services\Editor\EditorManager;

/**
 * コンテンツエディター設定コントローラー
 */
class AdminBaseEditorController extends AdminLoggedInController
{
    protected const SETTING_KEYS = ['preferred_gui_editor'];

    public function __construct(
        protected SiteSettingRepositoryInterface $baseSettingRepository,
        protected EditorManager $editorManager,
    ) {
        parent::__construct();
    }

    /**
     * コンテンツエディター設定ページ
     */
    public function index()
    {
        $guiEditors = $this->editorManager->getEditorsForType('gui');

        $guiEditorOptions = ['' => __('admin/settings/base/editor.no_gui_editor_selected')];
        foreach ($guiEditors as $editor) {
            $guiEditorOptions[$editor->pluginSlug] = $editor->label;
        }

        $settings = [
            'preferred_gui_editor' => $this->baseSettingRepository->get(EditorManager::PREFERRED_GUI_EDITOR_KEY, ''),
        ];

        $this->viewParams['settings'] = $settings;
        $this->viewParams['guiEditors'] = $guiEditors;
        $this->viewParams['guiEditorOptions'] = $guiEditorOptions;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.editor');

        return view('admin.settings.base.editor', $this->viewParams);
    }

    /**
     * コンテンツエディター設定の更新
     */
    public function update(AdminBaseEditorUpdateRequest $request)
    {
        $actor = new MemberActor(AdminHelper::getMember());

        UpdateSettingsAction::make(
            repository: $this->baseSettingRepository,
            settingsPage: 'base.editor',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                $repo->set(
                    EditorManager::PREFERRED_GUI_EDITOR_KEY,
                    $data['preferred_gui_editor'] ?? '',
                );
            },
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.base.editor')
            ->with('success', __('admin/settings/base/editor.settings_updated'));
    }
}
