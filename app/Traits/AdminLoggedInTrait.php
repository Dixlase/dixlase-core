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

namespace App\Traits;

use App\Enums\AppearanceMode;
use Illuminate\Support\Facades\Auth;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 管理画面ログイン後の共通初期化トレイト
 */
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

            // メンバーの言語設定を適用（SetMemberLocaleミドルウェアと同じロジック）
            if ($this->member && $this->member->locale) {
                $locale = $this->member->locale instanceof \App\Enums\Locale
                    ? $this->member->locale->value
                    : $this->member->locale;

                if (\App\Enums\Locale::isValid($locale)) {
                    app()->setLocale($locale);
                }
            }

            $transition = config('admin.transition_class');
            $this->viewParams['transition'] = $transition;

            // パンくずリストを自動生成（ロケール設定後に実行）
            if (empty($this->breadcrumbs)) {
                $this->generateBreadcrumbsFromRoute();
            }

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
     * ルート名から自動的にパンくずリストを生成
     * 例: admin.members.settings.index → ダッシュボード > メンバー管理 > メンバー全体設定
     */
    protected function generateBreadcrumbsFromRoute(): void
    {
        $routeName = request()->route()?->getName();

        if (! $routeName) {
            return;
        }

        // プラグインのルート名の場合
        if (str_contains($routeName, '::')) {
            $this->generatePluginBreadcrumbs($routeName);
        } else {
            $this->generateCoreBreadcrumbs($routeName);
        }

        $this->setBreadcrumbs();
    }

    /**
     * コアのパンくずリストを生成
     */
    protected function generateCoreBreadcrumbs(string $routeName): void
    {
        // admin. を除去
        $parts = explode('.', $routeName);
        array_shift($parts); // 'admin' を除去

        if (empty($parts)) {
            return;
        }

        // 最後が 'index' の場合は除去（重複を避けるため）
        if (end($parts) === 'index') {
            array_pop($parts);
        }

        if (empty($parts)) {
            return;
        }

        // 先頭に「管理画面」を追加（ダッシュボードへのリンク）
        $this->addBreadcrumb('admin.dashboard', __('common.admin_panel'));

        // 各階層のパンくずを生成
        $currentPath = 'admin';
        $translationPath = 'admin';

        foreach ($parts as $index => $part) {
            $currentPath .= '.'.$part;
            $translationPath .= '/'.$part;

            // 最後の要素（現在のページ）はリンクなし
            $route = null;
            if ($index < count($parts) - 1) {
                // 中間ルートが存在するかチェック（.index を付けて試行）
                $indexRouteName = $currentPath.'.index';
                if (\Route::has($indexRouteName)) {
                    $route = $indexRouteName;
                } elseif (\Route::has($currentPath)) {
                    $route = $currentPath;
                }
            }

            // 翻訳キーを試行: index.heading または nav.{part}
            $label = $this->resolveBreadcrumbLabel($translationPath, $part);

            if ($label) {
                $this->addBreadcrumb($route, $label);
            }
        }
    }

    /**
     * プラグインのパンくずリストを生成
     */
    protected function generatePluginBreadcrumbs(string $routeName): void
    {
        // プラグイン名とルート部分を分離
        [$pluginPrefix, $route] = explode('::', $routeName, 2);

        // admin. を除去
        $parts = explode('.', $route);
        if ($parts[0] === 'admin') {
            array_shift($parts);
        }

        if (empty($parts)) {
            return;
        }

        // 最後が 'index' の場合は除去（重複を避けるため）
        if (end($parts) === 'index') {
            array_pop($parts);
        }

        if (empty($parts)) {
            return;
        }

        // 先頭に「管理画面」を追加（ダッシュボードへのリンク）
        $this->addBreadcrumb('admin.dashboard', __('common.admin_panel'));

        // 各階層のパンくずを生成
        $currentPath = 'admin';
        $translationPath = 'admin';

        foreach ($parts as $index => $part) {
            $currentPath .= '.'.$part;
            $translationPath .= '/'.$part;

            // 最後の要素（現在のページ）はリンクなし
            $route = null;
            if ($index < count($parts) - 1) {
                // 中間ルートが存在するかチェック（.index を付けて試行）
                $indexRouteName = $pluginPrefix.'::'.$currentPath.'.index';
                if (\Route::has($indexRouteName)) {
                    $route = $indexRouteName;
                } else {
                    $fullRouteName = $pluginPrefix.'::'.$currentPath;
                    if (\Route::has($fullRouteName)) {
                        $route = $fullRouteName;
                    }
                }
            }

            // 翻訳キーを試行
            $label = $this->resolvePluginBreadcrumbLabel($pluginPrefix, $translationPath, $part);

            if ($label) {
                $this->addBreadcrumb($route, $label);
            }
        }
    }

    /**
     * コアの翻訳ラベルを解決
     */
    protected function resolveBreadcrumbLabel(string $translationPath, string $part): ?string
    {
        // パターン1: {path}/index.heading
        $indexKey = $translationPath.'/index.heading';
        if (\Lang::has($indexKey)) {
            $label = __($indexKey);

            return $label;
        }

        // パターン2: {path}.heading
        $headingKey = $translationPath.'.heading';
        if (\Lang::has($headingKey)) {
            $label = __($headingKey);

            return $label;
        }

        // パターン3: 親のnav.{part}
        $pathParts = explode('/', $translationPath);
        if (count($pathParts) >= 2) {
            $lastPart = array_pop($pathParts);
            $parentPath = implode('/', $pathParts);
            $navKey = $parentPath.'/index.nav.'.$lastPart;
            if (\Lang::has($navKey)) {
                $label = __($navKey);

                return $label;
            }
        }

        // パターン4: admin/navigation.{part}.text（配列の場合）
        $navTextKey = 'admin/navigation.'.$part.'.text';
        if (\Lang::has($navTextKey)) {
            $label = __($navTextKey);

            return $label;
        }

        // パターン5: admin/navigation.{part}（文字列の場合）
        $navKey = 'admin/navigation.'.$part;
        if (\Lang::has($navKey)) {
            $value = __($navKey);
            // 配列が返ってきた場合は .text を試す
            if (is_array($value) && isset($value['text'])) {
                return $value['text'];
            }
            // 文字列の場合はそのまま返す
            if (is_string($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * プラグインの翻訳ラベルを解決
     */
    protected function resolvePluginBreadcrumbLabel(string $pluginPrefix, string $translationPath, string $part): ?string
    {
        // パターン1: plugin::{path}/index.heading
        $indexKey = $pluginPrefix.'::'.$translationPath.'/index.heading';
        if (\Lang::has($indexKey)) {
            return __($indexKey);
        }

        // パターン2: plugin::{path}.heading
        $headingKey = $pluginPrefix.'::'.$translationPath.'.heading';
        if (\Lang::has($headingKey)) {
            return __($headingKey);
        }

        // パターン3: 親のnav.{part}
        $pathParts = explode('/', $translationPath);
        if (count($pathParts) >= 2) {
            $lastPart = array_pop($pathParts);
            $parentPath = implode('/', $pathParts);
            $navKey = $pluginPrefix.'::'.$parentPath.'/index.nav.'.$lastPart;
            if (\Lang::has($navKey)) {
                return __($navKey);
            }
        }

        // パターン4: plugin::admin/navigation.{part}.text（配列の場合）
        $navTextKey = $pluginPrefix.'::admin/navigation.'.$part.'.text';
        if (\Lang::has($navTextKey)) {
            return __($navTextKey);
        }

        // パターン5: plugin::admin/navigation.{part}（文字列の場合）
        $navKey = $pluginPrefix.'::admin/navigation.'.$part;
        if (\Lang::has($navKey)) {
            $value = __($navKey);
            // 配列が返ってきた場合は .text を試す
            if (is_array($value) && isset($value['text'])) {
                return $value['text'];
            }
            // 文字列の場合はそのまま返す
            if (is_string($value)) {
                return $value;
            }
        }

        return null;
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

        if (! $routeName) {
            return null;
        }

        // プラグインのルート名の場合、プレフィックスを処理
        // 例: dixlase-users::admin.users.index -> dixlase-users::admin/users/index.description
        if (str_contains($routeName, '::')) {
            [$pluginPrefix, $route] = explode('::', $routeName, 2);

            // ルート部分をパスに変換
            $routePath = str_replace('.', '/', $route);

            // プラグインの翻訳キー形式: プラグイン名::パス.description
            $translationKey = $pluginPrefix.'::'.$routePath.'.description';
        } else {
            // 通常のルート名を翻訳キーに変換
            // 例: admin.members.settings -> admin/members/settings.description
            //     admin.settings.base.site -> admin/settings/base/site.description
            $translationKey = str_replace('.', '/', $routeName).'.description';
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
