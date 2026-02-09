<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 管理者は常にアクセス可能
        if (auth()->guard('member')->check()) {
            return $next($request);
        }

        // プレビューリクエストは通す
        if ($request->is('maintenance-preview')) {
            return $next($request);
        }

        // メンテナンスモード設定を取得
        $settings = $this->getMaintenanceSettings();

        // メンテナンスモードが無効な場合は通常処理
        if (!$settings['maintenance_mode']) {
            return $next($request);
        }

        // 開始日時が設定されている場合、まだ開始前かチェック
        if ($settings['maintenance_start_at']) {
            $startAt = \Carbon\Carbon::parse($settings['maintenance_start_at']);
            if (now()->lt($startAt)) {
                // まだメンテナンス開始前
                return $next($request);
            }
        }

        // 終了日時が設定されている場合、すでに終了しているかチェック
        if ($settings['maintenance_release_at']) {
            $releaseAt = \Carbon\Carbon::parse($settings['maintenance_release_at']);
            if (now()->gte($releaseAt)) {
                // メンテナンス終了済み（自動解除処理はコマンドで行う）
                return $next($request);
            }
        }

        // メンテナンス画面を表示
        return $this->showMaintenancePage($settings);
    }

    /**
     * メンテナンス設定を取得
     */
    private function getMaintenanceSettings(): array
    {
        $settings = DB::table('base_settings')
            ->whereIn('name', [
                'maintenance_mode',
                'maintenance_message',
                'maintenance_auto_release',
                'maintenance_start_at',
                'maintenance_release_at',
            ])
            ->pluck('value', 'name')
            ->toArray();

        return [
            'maintenance_mode' => ($settings['maintenance_mode'] ?? '0') === '1',
            'maintenance_message' => $settings['maintenance_message'] ?? '現在メンテナンス中です。しばらくお待ちください。',
            'maintenance_auto_release' => ($settings['maintenance_auto_release'] ?? '0') === '1',
            'maintenance_start_at' => $settings['maintenance_start_at'] ?? null,
            'maintenance_release_at' => $settings['maintenance_release_at'] ?? null,
        ];
    }

    /**
     * メンテナンス画面を表示
     */
    private function showMaintenancePage(array $settings): Response
    {
        $retryAfter = null;

        // 自動解除が有効で終了日時が設定されている場合、Retry-Afterヘッダーを計算
        if ($settings['maintenance_auto_release'] && $settings['maintenance_release_at']) {
            $releaseAt = \Carbon\Carbon::parse($settings['maintenance_release_at']);
            $retryAfter = max(0, now()->diffInSeconds($releaseAt, false));
        }

        $response = response()->view('maintenance', [
            'message' => $settings['maintenance_message'],
            'releaseAt' => $settings['maintenance_release_at'],
        ], 503);

        // Retry-Afterヘッダーを設定
        if ($retryAfter !== null) {
            $response->header('Retry-After', (string) $retryAfter);
        }

        return $response;
    }
}
