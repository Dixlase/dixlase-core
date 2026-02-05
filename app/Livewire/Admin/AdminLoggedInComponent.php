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

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Enums\AppearanceMode;
use Illuminate\Support\Facades\Auth;

/**
 * 管理画面Livewireコンポーネントの基底クラス
 * AdminLoggedInControllerと同様の共通処理を提供
 */
abstract class AdminLoggedInComponent extends Component
{
    protected $member;
    protected $appearance;
    protected $heading = '';
    protected $description = null;
    protected $breadcrumbs = [];

    /**
     * コンポーネント初期化
     */
    public function mount()
    {
        $this->initializeAfterLogin();
        $this->mountComponent();
    }

    /**
     * ログイン後の初期化処理
     * AdminLoggedInTraitのinitializeAfterLogin()と同等
     */
    protected function initializeAfterLogin()
    {
        $this->member = Auth::guard('member')->user();
        $this->appearance = $this->member->appearance?->value ?? AppearanceMode::Auto->value;
    }

    /**
     * 子コンポーネントでオーバーライドする初期化メソッド
     */
    protected function mountComponent()
    {
        // 子クラスでオーバーライド
    }

    /**
     * レイアウトに渡す共通変数を取得
     */
    protected function getLayoutData(): array
    {
        return [
            'appearance' => $this->appearance,
            'member' => $this->member,
            'heading' => $this->heading,
            'description' => $this->description,
            'breadcrumbs' => $this->breadcrumbs,
        ];
    }

    /**
     * ビューをレンダリング
     * 子クラスでオーバーライドして使用
     */
    abstract public function render();
}
