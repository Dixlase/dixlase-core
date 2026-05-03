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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 緊急ロックダウン機能用テーブル
     *
     * 目的：
     * - セキュリティインシデント時の即座のシステム保護
     * - 不正アクセス検知時の自動ロックダウン
     * - ロックダウン履歴の記録
     */
    public function up(): void
    {
        // ロックダウン状態テーブル
        Schema::create('lockdown_status', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // ロックダウンの種類
            // full: 全アクセス遮断（SUPER_ADMIN以外）
            // admin: 管理画面のみロック
            // api: APIのみロック
            // login: ログインのみロック
            $table->string('type', 20)->default('full');

            // 有効/無効
            $table->boolean('is_active')->default(false);

            // ロックダウン理由
            $table->string('reason', 500)->nullable();

            // 発動者（null=自動発動）
            $table->unsignedBigInteger('triggered_by')->nullable();

            // 発動日時
            $table->timestamp('triggered_at')->nullable();

            // 解除者
            $table->unsignedBigInteger('released_by')->nullable();

            // 解除日時
            $table->timestamp('released_at')->nullable();

            // 自動解除日時（設定時）
            $table->timestamp('auto_release_at')->nullable();

            // 許可されたIPアドレス（JSON配列）
            $table->json('allowed_ips')->nullable();

            // 許可されたメンバーID（JSON配列）
            $table->json('allowed_members')->nullable();

            // メタ情報（発動トリガーの詳細など）
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('is_active');
            $table->index('type');
            $table->index('triggered_at');
        });

        // ロックダウン履歴テーブル
        Schema::create('lockdown_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // アクション: activated, deactivated, extended, modified
            $table->string('action', 20);

            // ロックダウンの種類
            $table->string('type', 20);

            // 理由
            $table->string('reason', 500)->nullable();

            // 実行者（null=自動/システム）
            $table->unsignedBigInteger('performed_by')->nullable();

            // IPアドレス
            $table->string('ip_address', 45)->nullable();

            // 詳細情報
            $table->json('details')->nullable();

            $table->timestamp('performed_at')->useCurrent();

            $table->index('action');
            $table->index('type');
            $table->index('performed_at');
        });

        // ロックダウントリガー設定テーブル
        Schema::create('lockdown_triggers', function (Blueprint $table) {
            $table->id();

            // トリガー名
            $table->string('name', 100);

            // トリガータイプ
            // failed_logins: ログイン失敗回数
            // suspicious_activity: 不審なアクティビティ
            // file_integrity: ファイル整合性違反
            // manual: 手動のみ
            $table->string('trigger_type', 50);

            // 有効/無効
            $table->boolean('is_enabled')->default(false);

            // 閾値（トリガータイプによって意味が異なる）
            $table->unsignedInteger('threshold')->default(0);

            // 時間枠（分）
            $table->unsignedInteger('time_window_minutes')->default(60);

            // 発動するロックダウンタイプ
            $table->string('lockdown_type', 20)->default('login');

            // 自動解除までの時間（分、0=手動解除のみ）
            $table->unsignedInteger('auto_release_minutes')->default(0);

            // 通知を送信するか
            $table->boolean('notify')->default(true);

            $table->timestamps();

            $table->unique('trigger_type');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lockdown_triggers');
        Schema::dropIfExists('lockdown_history');
        Schema::dropIfExists('lockdown_status');
    }
};
