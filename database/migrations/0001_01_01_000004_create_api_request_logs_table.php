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
     * APIリクエストログテーブル
     * β版でのAPIレートリミット機能の基盤として使用
     */
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // ========================================
            // APIキー情報
            // ========================================

            // APIキーID（外部キー）
            $table->unsignedBigInteger('api_key_id')->nullable()->index();

            // APIキープレフィックス（キー削除後も識別用に保持）
            $table->string('api_key_prefix', 20)->nullable()->index();

            // ========================================
            // リクエスト情報
            // ========================================

            // HTTPメソッド（GET, POST, PUT, DELETE, etc.）
            $table->string('method', 10)->index();

            // エンドポイント（/api/v1/events など）
            $table->string('endpoint', 255)->index();

            // リクエストパス（クエリパラメータ除く）
            $table->string('path', 500)->nullable();

            // クエリパラメータ（JSON）
            $table->json('query_params')->nullable();

            // リクエストボディサイズ（バイト）
            $table->unsignedInteger('request_size')->nullable();

            // ========================================
            // レスポンス情報
            // ========================================

            // HTTPステータスコード
            $table->unsignedSmallInteger('response_code')->index();

            // レスポンスサイズ（バイト）
            $table->unsignedInteger('response_size')->nullable();

            // 処理時間（ミリ秒）
            $table->unsignedInteger('response_time_ms')->nullable();

            // ========================================
            // クライアント情報
            // ========================================

            // IPアドレス
            $table->string('ip_address', 45)->index();

            // User-Agent
            $table->string('user_agent', 500)->nullable();

            // ========================================
            // レートリミット情報
            // ========================================

            // レートリミット超過フラグ
            $table->boolean('rate_limited')->default(false)->index();

            // 現在のレート（1分あたりのリクエスト数）
            $table->unsignedInteger('current_rate')->nullable();

            // ========================================
            // エラー情報
            // ========================================

            // エラーコード（アプリケーション固有）
            $table->string('error_code', 50)->nullable()->index();

            // エラーメッセージ
            $table->text('error_message')->nullable();

            // ========================================
            // タイムスタンプ
            // ========================================

            // リクエスト日時（インデックス付き）
            $table->timestamp('requested_at')->useCurrent()->index();

            // 標準タイムスタンプ
            $table->timestamps();

            // ========================================
            // インデックス
            // ========================================

            // レートリミット計算用（APIキー + 時間範囲）
            $table->index(['api_key_id', 'requested_at'], 'idx_api_rate_limit');

            // IP別レートリミット用
            $table->index(['ip_address', 'requested_at'], 'idx_api_ip_rate');

            // エンドポイント別分析用
            $table->index(['endpoint', 'method', 'requested_at'], 'idx_api_endpoint_analysis');

            // エラー分析用
            $table->index(['response_code', 'requested_at'], 'idx_api_error_analysis');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
