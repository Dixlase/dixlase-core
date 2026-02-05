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
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Lang;

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
    protected $hasSaveButton = false;
    protected $saveButtonForm = '';

    /**
     * コンポーネント初期化
     */
    public function mount()
    {
        $this->initializeAfterLogin();
        
        // パンくずリストを自動生成
        if (empty($this->breadcrumbs)) {
            $this->generateBreadcrumbsFromRoute();
        }
        
        // ページ説明を自動設定
        if ($this->description === null) {
            $this->setDescription();
        }
        
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
        // ここでページ固有の$headingや$descriptionを設定可能
    }

    /**
     * ページ見出しを設定
     * AdminInterfaceTraitのsetHeading()と同等
     */
    protected function setHeading()
    {
        $routeName = Route::currentRouteName();
        
        // プラグインのルートかどうかを判別
        if (strpos($routeName, '::') !== false) {
            $this->heading = $this->resolvePluginHeadingKey($routeName);
        } else {
            $this->heading = $this->resolveCoreHeadingKey($routeName);
        }
    }

    /**
     * コアの翻訳キーを解決する
     */
    protected function resolveCoreHeadingKey(string $routeName): string
    {
        $keys = explode('.', $routeName);
        array_shift($keys); // 'admin'を除去
        
        if (empty($keys)) {
            return 'admin/dashboard.heading';
        }
        
        // パターン1: 完全パス
        $fullPath = 'admin/' . implode('/', $keys) . '.heading';
        if (Lang::has($fullPath)) {
            return $fullPath;
        }
        
        // パターン2: index.phpを参照
        $indexPath = 'admin/' . implode('/', $keys) . '/index.heading';
        if (Lang::has($indexPath)) {
            return $indexPath;
        }
        
        // パターン3: 親ディレクトリのindex.phpを参照
        if (count($keys) >= 2) {
            $parentKeys = array_slice($keys, 0, -1);
            $parentIndexPath = 'admin/' . implode('/', $parentKeys) . '/index.heading';
            if (Lang::has($parentIndexPath)) {
                return $parentIndexPath;
            }
        }
        
        return $fullPath;
    }

    /**
     * プラグインの翻訳キーを解決する
     */
    protected function resolvePluginHeadingKey(string $routeName): string
    {
        // プラグイン名とルート部分を分離
        [$pluginPart, $routePart] = explode('::', $routeName, 2);
        
        $keys = explode('.', $routePart);
        array_shift($keys); // 'admin'を除去
        
        $pluginName = str_replace('admin.', '', $pluginPart);
        
        return $pluginName . '::admin/' . implode('/', $keys) . '.heading';
    }

    /**
     * ページ説明を設定
     */
    protected function setDescription()
    {
        $routeName = Route::currentRouteName();
        
        if (strpos($routeName, '::') !== false) {
            [$pluginPart, $routePart] = explode('::', $routeName, 2);
            $keys = explode('.', $routePart);
            array_shift($keys);
            $pluginName = str_replace('admin.', '', $pluginPart);
            $descriptionKey = $pluginName . '::admin/' . implode('/', $keys) . '.description';
        } else {
            $keys = explode('.', $routeName);
            array_shift($keys);
            $descriptionKey = 'admin/' . implode('/', $keys) . '.description';
        }
        
        if (Lang::has($descriptionKey)) {
            $this->description = __($descriptionKey);
        }
    }

    /**
     * パンくずリストをルートから自動生成
     */
    protected function generateBreadcrumbsFromRoute()
    {
        $routeName = Route::currentRouteName();
        
        if (!$routeName) {
            return;
        }
        
        $parts = explode('.', $routeName);
        
        // ダッシュボードを追加
        $this->addBreadcrumb('admin.dashboard', __('admin/dashboard.heading'));
        
        // ルートの各部分からパンくずを生成
        $accumulated = ['admin'];
        for ($i = 1; $i < count($parts); $i++) {
            $accumulated[] = $parts[$i];
            $currentRoute = implode('.', $accumulated);
            
            // 最後の要素は現在のページなのでリンクなし
            if ($i === count($parts) - 1) {
                break;
            }
            
            // 翻訳キーを解決
            $labelKey = $this->resolveCoreHeadingKey($currentRoute);
            if (Lang::has($labelKey)) {
                $this->addBreadcrumb($currentRoute, __($labelKey));
            }
        }
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
