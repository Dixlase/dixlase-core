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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Webhook登録テーブル
     *
     * 目的：
     * - 外部サービスへのイベント通知設定
     * - 署名付きHTTPリクエストの送信
     * - リトライ・タイムアウト設定
     */
    public function up(): void
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('url', 2048);
            $table->string('secret', 128);
            $table->json('events')->nullable(); // 購読するイベント一覧
            $table->boolean('is_active')->default(true);
            $table->string('environment', 16)->default('live'); // live / test
            $table->unsignedInteger('timeout')->default(30); // タイムアウト秒数
            $table->unsignedInteger('retry_count')->default(3); // リトライ回数
            $table->json('headers')->nullable(); // カスタムヘッダー
            $table->text('description')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedBigInteger('success_count')->default(0);
            $table->unsignedBigInteger('failure_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
            $table->index('environment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhooks');
    }
};
