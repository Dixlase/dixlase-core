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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * セキュリティイベントの構造化保存テーブル
     * ゼロトラスト基盤の一部として、重要なセキュリティイベントをDBに保存
     */
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();

            // Multisite scope. Site-scoped events carry the site id;
            // network-wide / system events leave it null.
            $table->unsignedBigInteger('site_id')->nullable()->index();

            // イベント発生者（nullable: システムイベントの場合はnull）
            $table->unsignedBigInteger('member_id')->nullable()->index();

            // イベント種別
            $table->string('event_type', 50)->index();

            // イベントカテゴリ（フィルタリング用）
            $table->string('category', 20)->index();

            // リスクレベル
            $table->string('risk_level', 10)->default('low')->index();

            // アクセス元情報
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();

            // デバイス情報
            $table->unsignedBigInteger('device_id')->nullable()->index();

            // セッション情報
            $table->string('session_id', 255)->nullable()->index();

            // イベント詳細（JSON）
            $table->json('meta')->nullable();

            // イベント発生日時
            $table->timestamp('occurred_at')->useCurrent()->index();

            // 標準タイムスタンプ
            $table->timestamps();

            // 複合インデックス
            $table->index(['member_id', 'event_type', 'occurred_at']);
            $table->index(['ip_address', 'event_type', 'occurred_at']);
            $table->index(['category', 'risk_level', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
