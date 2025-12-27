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

    /**
     * ログイン後に共通で必要な初期化を行う
     */
    protected function initializeAfterLogin()
    {
        $this->middleware(function ($request, $next) {
            $this->setMember();

            $transition = config('admin.transition_class');
            $this->viewParams['transition'] = $transition;

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
