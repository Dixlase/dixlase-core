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
     * 監査ログ（Audit Log）テーブル
     * 
     * 目的：
     * - 誰が / いつ / どこから / 何に対して / 何をしたか を記録
     * - セキュリティ・設定変更は必ず記録（改ざん防止寄りの設計）
     * - プラグインも同じ形式でログを追加可能
     * - α版でスキーマ固定、将来はJSONフィールド側を拡張
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            
            // ========================================
            // 発生時刻
            // ========================================
            // created_atとは別に持つ（バッチ書き込みでもズレにくい）
            $table->timestamp('occurred_at')->useCurrent()->index();
            
            // ========================================
            // レベル / 結果
            // ========================================
            // severity: info, notice, warning, error, critical, alert, emergency
            $table->string('severity', 20)->default('info')->index();
            // outcome: success, failure, denied, pending, unknown
            $table->string('outcome', 20)->default('success')->index();
            
            // ========================================
            // 種別・アクション
            // ========================================
            // category: auth, account, device, security, session, extension, content, system, plugin
            $table->string('category', 50)->index();
            // action: login, logout, update_profile, install_plugin, etc.
            $table->string('action', 100)->index();
            
            // ========================================
            // 行為者（actor）- Polymorphic
            // ========================================
            // actor_type: App\Models\Member, App\Models\User, etc.
            // actor_id: 行為者のID
            $table->nullableMorphs('actor');
            // 表示名のスナップショット（後から参照できるように）
            $table->string('actor_name', 191)->nullable();
            // なりすまし・代理操作時の実際の操作者ID
            $table->unsignedBigInteger('impersonated_by_id')->nullable();
            
            // ========================================
            // 対象（target）- Polymorphic
            // ========================================
            // target_type: App\Models\Member, App\Models\Plugin, etc.
            // target_id: 対象のID
            $table->nullableMorphs('target');
            // 対象の名前や識別子（メールアドレス、タイトルなど）
            $table->string('target_label', 191)->nullable();
            
            // ========================================
            // リクエストコンテキスト
            // ========================================
            $table->string('ip_address', 45)->nullable()->index(); // IPv6対応
            $table->string('user_agent', 500)->nullable();
            // 1リクエスト内の関連ログを紐付け
            $table->string('request_id', 100)->nullable()->index();
            $table->string('session_id', 255)->nullable()->index();
            
            // ========================================
            // プラグイン情報（コア操作ならnull）
            // ========================================
            $table->string('plugin_name', 100)->nullable()->index();
            $table->string('plugin_version', 50)->nullable();
            
            // ========================================
            // 任意の追加情報（JSON）
            // ========================================
            // 構造例:
            // {
            //   "message": "説明テキスト",
            //   "before": { "email": "old@example.com" },
            //   "after": { "email": "new@example.com" },
            //   "diff": { "email": { "from": "old", "to": "new" } },
            //   "meta": { "http_method": "POST", "url": "/admin/...", "extra": {} }
            // }
            $table->json('context')->nullable();
            
            // ========================================
            // 将来のフォーマット変更用
            // ========================================
            $table->unsignedSmallInteger('schema_version')->default(1);
            
            // 標準タイムスタンプ
            $table->timestamps();
            
            // ========================================
            // 複合インデックス（よく使うクエリパターン用）
            // ========================================
            $table->index(['actor_type', 'actor_id', 'occurred_at']);
            $table->index(['target_type', 'target_id', 'occurred_at']);
            $table->index(['category', 'action', 'occurred_at']);
            $table->index(['category', 'severity', 'occurred_at']);
            $table->index(['ip_address', 'action', 'occurred_at']);
            $table->index(['plugin_name', 'action', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
