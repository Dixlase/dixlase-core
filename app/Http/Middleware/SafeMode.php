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

namespace App\Http\Middleware;

use App\Enums\SafeMode as SafeModeEnum;
use App\Services\SafeModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * セーフモード検出ミドルウェア
 *
 * ?safe= パラメータからセーフモードを検出し、セッションに保存する。
 * ?safe=1 は後方互換性のため ?safe=csp として処理する。
 * カンマ区切りで複数モード同時指定可能（例: ?safe=csp,plugins）。
 * テーマセーフモード時はフロント側のビュー名前空間をオーバーライドする。
 */
class SafeMode
{
    public function __construct(
        protected SafeModeService $safeModeService
    ) {}

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // ?safe= パラメータを検出
        $safeParam = $request->query('safe');

        if ($safeParam !== null && auth()->check()) {
            $modes = SafeModeEnum::fromUrlParam($safeParam);

            foreach ($modes as $mode) {
                if (! $this->safeModeService->isActive($mode)) {
                    $this->safeModeService->activate($mode);
                }
            }
        }

        // テーマセーフモードが有効かつフロント側の場合、ビュー名前空間をオーバーライド
        if ($this->safeModeService->isActive(SafeModeEnum::Theme) && ! $this->isAdminRoute($request)) {
            $this->overrideThemeViewNamespace();
        }

        return $next($request);
    }

    /**
     * 管理画面ルートかどうかを判定
     */
    protected function isAdminRoute(Request $request): bool
    {
        return str_starts_with($request->path(), 'admin') || str_starts_with($request->path(), config('admin.url', 'admin'));
    }

    /**
     * テーマのビュー名前空間をセーフテーマにオーバーライド
     */
    protected function overrideThemeViewNamespace(): void
    {
        $viewFactory = app('view');
        $safeThemePath = resource_path('views/safe-theme');

        if (is_dir($safeThemePath)) {
            // themes:: 名前空間をセーフテーマに上書き
            $viewFactory->getFinder()->replaceNamespace('themes', [$safeThemePath]);
        }
    }
}
