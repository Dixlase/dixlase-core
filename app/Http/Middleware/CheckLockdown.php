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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\LockdownStatus;
use App\Services\LockdownService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ロックダウンチェックミドルウェア
 *
 * ロックダウン中のアクセスを制限する
 *
 * Usage:
 *   ->middleware('lockdown')           // 全タイプをチェック
 *   ->middleware('lockdown:admin')     // 管理画面ロックダウンをチェック
 *   ->middleware('lockdown:api')       // APIロックダウンをチェック
 *   ->middleware('lockdown:login')     // ログインロックダウンをチェック
 */
class CheckLockdown
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $type ロックダウンタイプ（null=全タイプ）
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $type = null): Response
    {
        // 自動解除をチェック
        LockdownService::checkAutoRelease();

        // ロックダウン状態を取得
        $lockdown = LockdownService::getStatus();

        if (!$lockdown) {
            return $next($request);
        }

        // タイプが指定されていて、そのタイプがロックされていない場合はスキップ
        if ($type !== null && $lockdown->type !== LockdownStatus::TYPE_FULL && $lockdown->type !== $type) {
            return $next($request);
        }

        // アクセス許可をチェック
        $member = Auth::guard('member')->user();
        $ip = $request->ip();

        if (LockdownService::isAccessAllowed($ip, $member, $type)) {
            return $next($request);
        }

        // ロックダウン中のレスポンス
        return $this->lockdownResponse($request, $lockdown);
    }

    /**
     * ロックダウン中のレスポンスを生成
     */
    protected function lockdownResponse(Request $request, LockdownStatus $lockdown): Response
    {
        $message = $lockdown->reason ?: __('admin/lockdown.default_message');

        // APIリクエストの場合はJSONレスポンス
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => 'lockdown',
                'message' => $message,
                'type' => $lockdown->type,
            ], 503);
        }

        // 通常のリクエストの場合はロックダウンページを表示
        return response()->view('errors.lockdown', [
            'lockdown' => $lockdown,
            'message' => $message,
        ], 503);
    }
}
