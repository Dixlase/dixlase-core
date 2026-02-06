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

namespace App\Livewire\Admin\Settings;

use App\Livewire\Admin\AdminLoggedInComponent;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;

/**
 * セキュリティ設定用Livewireコンポーネントの基底クラス
 * セキュリティ設定関連の共通処理を提供
 */
abstract class SecuritySettingsComponent extends AdminLoggedInComponent
{
    protected SecuritySettingRepositoryInterface $securitySettingRepository;

    /**
     * セキュリティ設定リポジトリを取得
     */
    protected function getSecuritySettingRepository(): SecuritySettingRepositoryInterface
    {
        if (!isset($this->securitySettingRepository)) {
            $this->securitySettingRepository = app(SecuritySettingRepositoryInterface::class);
        }
        
        return $this->securitySettingRepository;
    }

    /**
     * セキュリティ設定値を取得
     */
    protected function getSecuritySetting(string $key, mixed $default = null): mixed
    {
        return $this->getSecuritySettingRepository()->get($key, $default);
    }

    /**
     * セキュリティ設定値を保存
     */
    protected function setSecuritySetting(string $key, mixed $value): void
    {
        $this->getSecuritySettingRepository()->set($key, $value);
    }

    /**
     * 複数のセキュリティ設定値を一括保存
     */
    protected function setSecuritySettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->setSecuritySetting($key, $value);
        }
    }
}
