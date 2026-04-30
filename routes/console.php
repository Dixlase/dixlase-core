<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// ファイル整合性スキャン（毎日午前3時に実行）
Schedule::command('dls:integrity:scan --scheduled')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/integrity-scan.log'));

// メンテナンスモード自動解除チェック（1分ごとに実行）
Schedule::command('maintenance:check-auto-release')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// 拡張機能アップデートチェック（毎時 cron が tick、設定された間隔が経過していたら実際に走らせる）
//
// `extension_update_check_interval` 設定（86400 / 43200 / 21600 / 0=manual）で間隔を制御。
// `when()` 内で「前回チェックから設定間隔以上経過したか」を判定して実行可否を決める。
// 設定変更時に cron を組み直す必要がない設計（hourly ティックで再評価される）。
Schedule::command('dls:source:check')
    ->hourly()
    ->when(function () {
        // インストール完了前は走らせない
        $installed = $_SERVER['INSTALLED'] ?? $_ENV['INSTALLED'] ?? env('INSTALLED') ?? config('app.installed');
        if ($installed !== 'true' && $installed !== true) {
            return false;
        }

        try {
            $interval = (int) (\App\Services\SecuritySettingsRegistry::get('extension_update_check_interval')
                ?? config('extension-sources.check_interval', 86400));
        } catch (\Throwable) {
            return false;
        }

        // 0 は手動チェックのみ（自動実行しない）
        if ($interval <= 0) {
            return false;
        }

        // 前回チェック時刻の最大値（プラグイン・テーマ横断）。一度も走っていなければ即実行。
        try {
            $lastPlugin = \App\Models\Plugin::query()->max('last_version_check');
            $lastTheme = \App\Models\Theme::query()->max('last_version_check');
        } catch (\Throwable) {
            return false;
        }

        $candidates = array_filter([$lastPlugin, $lastTheme]);
        if (empty($candidates)) {
            return true;
        }
        $lastCheck = \Carbon\Carbon::parse(max($candidates));

        return $lastCheck->lt(now()->subSeconds($interval));
    })
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/source-check.log'));
