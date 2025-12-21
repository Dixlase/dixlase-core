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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Webhook配信ログテーブル
     * 
     * 目的：
     * - Webhook配信の履歴・状態管理
     * - リトライ制御
     * - 冪等性保証（event_id）
     * - リプレイ防止（nonce）
     */
    public function up(): void
    {
        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            
            // 冪等性のためのイベントID（UUID）
            $table->uuid('event_id')->nullable();
            
            // リプレイ防止のためのnonce
            $table->string('nonce', 64)->nullable();
            
            $table->foreignId('webhook_id')->constrained()->onDelete('cascade');
            $table->string('event', 100);
            $table->json('payload');
            $table->string('status', 16); // pending / success / failed / retrying
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->unsignedTinyInteger('attempt')->default(1);
            $table->unsignedTinyInteger('max_attempts')->default(3);
            $table->text('error_message')->nullable();
            
            // デッドレター関連
            $table->boolean('is_dead_letter')->default(false);
            $table->timestamp('dead_letter_at')->nullable();
            $table->boolean('dead_letter_notified')->default(false);
            
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('event_id');
            $table->index('nonce');
            $table->index('status');
            $table->index('event');
            $table->index(['webhook_id', 'created_at']);
            $table->index('next_retry_at');
            $table->index('is_dead_letter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
