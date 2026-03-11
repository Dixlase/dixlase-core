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

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * APIキー認証ミドルウェア
 *
 * Authorization: Bearer dxl_live_xxx ヘッダーからキーを取得し、
 * APIキーの有効性・スコープ・IP制限を検証します。
 *
 * 使用例:
 *   - Route::middleware('auth.api') ... 全スコープ許可
 *   - Route::middleware('auth.api:read:content') ... read:content スコープ必須
 */
class AuthenticateApiKey
{
    /**
     * リクエストを処理
     *
     * @param  string  ...$scopes  必要なスコープ（ミドルウェアパラメータ）
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return $this->unauthorizedResponse('APIキーが提供されていません。');
        }

        $apiKey = ApiKey::validate($bearerToken);

        if (! $apiKey) {
            return $this->unauthorizedResponse('無効または期限切れのAPIキーです。');
        }

        // IP制限チェック
        if (! $apiKey->allowsIp($request->ip())) {
            return $this->forbiddenResponse('このIPアドレスからのアクセスは許可されていません。');
        }

        // スコープチェック
        foreach ($scopes as $scope) {
            if (! $apiKey->hasScope($scope)) {
                return $this->forbiddenResponse("必要なスコープ '{$scope}' がありません。");
            }
        }

        // 使用記録を更新
        $apiKey->recordUsage();

        // リクエスト属性にAPIキーをセット
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }

    /**
     * 401 Unauthorized レスポンスを生成
     */
    protected function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'unauthorized',
            'message' => $message,
        ], 401);
    }

    /**
     * 403 Forbidden レスポンスを生成
     */
    protected function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'forbidden',
            'message' => $message,
        ], 403);
    }
}
