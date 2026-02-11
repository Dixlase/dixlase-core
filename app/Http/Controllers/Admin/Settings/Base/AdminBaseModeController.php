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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Enums\AdminMode;
use App\Enums\MenuVisibility;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBaseModeController extends AdminLoggedInController
{
    protected BaseSettingRepositoryInterface $baseSettingRepository;

    public function __construct(BaseSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * モード設定ページ
     */
    public function index()
    {
        $currentMode = AdminMode::fromInt(
            (int) $this->baseSettingRepository->get('admin_mode', AdminMode::Simple->value)
        );

        // 保存済みのカスタム表示設定を取得
        $savedVisibilities = $this->getSavedVisibilities();

        // デフォルトのかんたんモード設定
        $simpleDefaults = config('admin.mode.simple_defaults', []);

        // メニュー項目のメタ情報
        $menuItems = config('admin.mode.menu_items', []);

        // 現在の表示設定をマージ（保存済み > デフォルト）
        $currentVisibilities = $this->mergeVisibilities($simpleDefaults, $savedVisibilities);

        $this->viewParams['currentMode'] = $currentMode;
        $this->viewParams['simpleDefaults'] = $simpleDefaults;
        $this->viewParams['menuItems'] = $menuItems;
        $this->viewParams['currentVisibilities'] = $currentVisibilities;
        $this->viewParams['menuVisibilityCases'] = MenuVisibility::cases();

        return view('admin.settings.base.mode', $this->viewParams);
    }

    /**
     * モード設定の更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'admin_mode' => 'required|integer|in:0,1',
            'menu_visibilities' => 'nullable|array',
            'menu_visibilities.*' => 'integer|in:0,1,2,3,4',
        ]);

        $newMode = AdminMode::fromInt((int) $validated['admin_mode']);

        // モードを保存
        $this->baseSettingRepository->set('admin_mode', (string) $newMode->value);

        // かんたんモードの場合、メニュー表示設定を保存
        if ($newMode->isSimple()) {
            $visibilities = $validated['menu_visibilities'] ?? [];
            $this->saveVisibilities($visibilities);
        } else {
            // 詳細モードの場合、カスタム設定をクリア
            $this->baseSettingRepository->set('admin_mode_visibilities', '');
        }

        // キャッシュをクリアして即座に反映
        AdminModeHelper::clearCache();

        Log::channel('admin_activity')->info('管理画面モード設定を更新', [
            'mode' => $newMode->name,
            'member_id' => auth('admin')->id(),
        ]);

        return redirect()->route('admin.settings.base.mode')
            ->with('success', __('admin/settings/base/mode.settings_updated'));
    }

    /**
     * 保存済みのメニュー表示設定を取得
     */
    private function getSavedVisibilities(): array
    {
        $json = $this->baseSettingRepository->get('admin_mode_visibilities', '');

        if (empty($json)) {
            return [];
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * メニュー表示設定を保存
     */
    private function saveVisibilities(array $visibilities): void
    {
        $this->baseSettingRepository->set(
            'admin_mode_visibilities',
            json_encode($visibilities, JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * デフォルト設定と保存済み設定をマージ
     */
    private function mergeVisibilities(array $defaults, array $saved): array
    {
        $merged = [];

        foreach ($defaults as $key => $visibility) {
            $value = $visibility instanceof MenuVisibility ? $visibility->value : (int) $visibility;

            if (isset($saved[$key])) {
                $merged[$key] = (int) $saved[$key];
            } else {
                $merged[$key] = $value;
            }
        }

        // 保存済みにあってデフォルトにないキーも含める
        foreach ($saved as $key => $value) {
            if (!isset($merged[$key])) {
                $merged[$key] = (int) $value;
            }
        }

        return $merged;
    }
}
