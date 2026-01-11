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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Member;
use App\Models\MemberSetting;
use App\Services\TwoFa\TwoFaService;
use App\Traits\TwoFa\TwoFaAuthenticationTrait;
use App\Repositories\BaseSettingRepository;

class AdminTwoFaController extends AdminController
{
    use TwoFaAuthenticationTrait;

    protected $baseSettingRepository;

    public function __construct(BaseSettingRepository $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }
    
    /**
     * 設定モデルクラス名を取得
     */
    protected function getSettingModelClass(): string
    {
        return MemberSetting::class;
    }

    /**
     * ログインルート名を取得
     */
    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }

    /**
     * ダッシュボードのルート名を取得
     */
    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }

    /**
     * セッションキーのプレフィックスを取得
     */
    protected function getSessionPrefix(): string
    {
        return 'login';
    }

    /**
     * 二段階認証サービスのインスタンスを取得
     */
    protected function getTwoFaService()
    {
        return app(TwoFaService::class, [
            'settingModelClass' => MemberSetting::class,
            'context' => 'admin'
        ]);
    }

    /**
     * ユーザーモデルクラス名を取得
     */
    protected function getUserModelClass(): string
    {
        return Member::class;
    }

    /**
     * 認証ガード名を取得
     */
    protected function getGuardName(): string
    {
        return 'web';
    }

    /**
     * コンテキストを取得
     */
    protected function getContext(): string
    {
        return 'admin';
    }

    /**
     * 二段階認証ルートのプレフィックスを取得
     */
    protected function getTwoFaRoutePrefix(): string
    {
        return $this->baseSettingRepository->get('admin_url', 'admin');
    }

    /**
     * 認証方法に応じたルートを取得
     */
    protected function getTwoFaMethodRoute(int $method): string
    {
        $prefix = $this->getTwoFaRoutePrefix();
        
        return match($method) {
            \App\Enums\TwoFaMethod::EMAIL->value => "{$prefix}.two-fa.email.show",
            \App\Enums\TwoFaMethod::PASSKEY->value => "{$prefix}.two-fa.passkey.show",
            default => "{$prefix}.two-fa.email.show",
        };
    }
}
