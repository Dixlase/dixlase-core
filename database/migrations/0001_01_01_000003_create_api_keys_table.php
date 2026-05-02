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
     * APIキー管理テーブル
     * 外部システム連携用のAPIキーを管理
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // キー名（識別用）
            $table->string('name', 100);

            // APIキー（ハッシュ化して保存）
            // 生成時のみ平文を表示、以降はハッシュのみ保存
            $table->string('key_hash', 255)->unique();

            // キープレフィックス（識別用、例: dxl_live_, dxl_test_）
            $table->string('key_prefix', 20);

            // 作成者
            $table->unsignedBigInteger('created_by')->nullable()->index();

            // 有効/無効
            $table->boolean('is_active')->default(true);

            // 環境（live/test）
            $table->string('environment', 10)->default('live');

            // 権限スコープ（JSON配列）
            // 例: ["read:events", "write:events", "read:translations"]
            $table->json('scopes')->nullable();

            // レート制限（1分あたりのリクエスト数、nullは無制限）
            $table->unsignedInteger('rate_limit')->nullable();

            // 許可IPリスト（JSON配列、nullは全IP許可）
            $table->json('allowed_ips')->nullable();

            // 有効期限（nullは無期限）
            $table->timestamp('expires_at')->nullable();

            // 最終使用日時
            $table->timestamp('last_used_at')->nullable();

            // 使用回数
            $table->unsignedBigInteger('usage_count')->default(0);

            // メモ
            $table->text('description')->nullable();

            // 標準タイムスタンプ
            $table->timestamps();

            // インデックス
            $table->index(['is_active', 'environment']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
