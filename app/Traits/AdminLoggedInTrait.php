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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Enums\AppearanceMode;



trait AdminLoggedInTrait
{
    protected $member;
    protected $appearance;
    protected $breadcrumbs = [];
    protected $description = null;

    /**
     * ログイン後に共通で必要な初期化を行う
     */
    protected function initializeAfterLogin()
    {
        $this->middleware(function ($request, $next) {
            $this->setMember();

            $transition = config('admin.transition_class');
            $this->viewParams['transition'] = $transition;

            // ページ説明を自動設定
            if ($this->description === null) {
                $this->setDescription();
            }

            return $next($request);
        });
    }

    /**
     * パンくずリストに項目を追加
     */
    protected function addBreadcrumb(?string $route, string $label): void
    {
        $this->breadcrumbs[] = [
            'route' => $route,
            'label' => $label,
        ];
    }

    /**
     * パンくずリストをビューパラメータに設定
     */
    protected function setBreadcrumbs(): void
    {
        $this->viewParams['breadcrumbs'] = $this->breadcrumbs;
    }

    /**
     * ページ説明を設定
     * 引数なしで呼び出すと、現在のルート名から自動的に翻訳キーを生成して説明を取得
     */
    protected function setDescription(?string $description = null): void
    {
        if ($description === null) {
            $description = $this->getDescriptionFromRoute();
        }
        
        $this->description = $description;
        $this->viewParams['description'] = $this->description;
    }

    /**
     * 現在のルート名から翻訳キーを生成して説明を取得
     */
    protected function getDescriptionFromRoute(): ?string
    {
        $routeName = request()->route()?->getName();
        
        if (!$routeName) {
            return null;
        }
        
        // プラグインのルート名の場合、プレフィックスを処理
        // 例: users-plugin::admin.users.index -> users-plugin::admin/users/index.description
        if (str_contains($routeName, '::')) {
            [$pluginPrefix, $route] = explode('::', $routeName, 2);
            
            // ルート部分をパスに変換
            $routePath = str_replace('.', '/', $route);
            
            // プラグインの翻訳キー形式: プラグイン名::パス.description
            $translationKey = $pluginPrefix . '::' . $routePath . '.description';
        } else {
            // 通常のルート名を翻訳キーに変換
            // 例: admin.members.settings -> admin/members/settings.description
            //     admin.settings.base.site -> admin/settings/base/site.description
            $translationKey = str_replace('.', '/', $routeName) . '.description';
        }
        
        // 翻訳が存在するかチェック
        $translation = __($translationKey);
        
        // 翻訳キーがそのまま返ってきた場合は翻訳が存在しない
        if ($translation === $translationKey) {
            return null;
        }
        
        return $translation;
    }

    /**
     * 管理者情報を取得して設定
     */
    protected function setMember()
    {
        $this->member = Auth::guard('member')->user();
        $this->viewParams['member'] = $this->member;

        $this->appearance = $this->member->appearance?->value ?? AppearanceMode::Auto->value;
        $this->viewParams['appearance'] = $this->appearance;
    }
}
