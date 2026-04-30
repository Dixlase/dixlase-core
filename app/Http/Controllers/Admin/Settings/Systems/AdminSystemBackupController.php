<?php

/**
 * This file is part of Dixlase.
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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;

/**
 * バックアップ管理コントローラー
 *
 * Phase E プレースホルダー: ナビゲーションを動作させるための最小実装。
 * Phase D で UI（一覧/作成/復元実行/設定）を実装する。
 */
class AdminSystemBackupController extends AdminLoggedInController
{
    /**
     * バックアップ一覧/作成画面
     */
    public function index()
    {
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup');

        return view('admin::settings.systems.backup.index', $this->viewParams);
    }

    /**
     * 復元履歴画面
     */
    public function restores()
    {
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup.restores');

        return view('admin::settings.systems.backup.restores', $this->viewParams);
    }

    /**
     * バックアップ設定画面
     */
    public function settings()
    {
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.backup.settings');

        return view('admin::settings.systems.backup.settings', $this->viewParams);
    }
}
