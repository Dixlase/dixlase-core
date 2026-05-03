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
     * Webhookデッドレターキューテーブル
     *
     * 目的：
     * - 最大リトライ回数を超えて失敗したWebhookの詳細記録
     * - 手動リトライや調査のための情報保持
     * - 障害分析・通知
     */
    public function up(): void
    {
        Schema::create('webhook_dead_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();
            $table->unsignedBigInteger('webhook_id');
            $table->unsignedBigInteger('delivery_id');
            $table->uuid('event_id');
            $table->string('event', 100);
            $table->json('payload');
            $table->json('request_headers')->nullable();
            $table->text('last_error');
            $table->unsignedTinyInteger('total_attempts');
            $table->json('attempt_log')->nullable(); // 各試行の詳細ログ
            $table->timestamp('first_attempt_at');
            $table->timestamp('last_attempt_at');
            $table->boolean('notified')->default(false);
            $table->timestamp('notified_at')->nullable();
            $table->boolean('manually_retried')->default(false);
            $table->timestamp('manually_retried_at')->nullable();
            $table->unsignedBigInteger('manually_retried_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index('event_id');
            $table->index('event');
            $table->index('notified');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_dead_letters');
    }
};
