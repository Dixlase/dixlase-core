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
     * 監査ログ（Audit Log）テーブル
     *
     * 目的：
     * - 誰が / いつ / どこから / 何に対して / 何をしたか を記録
     * - セキュリティ・設定変更は必ず記録（改ざん防止寄りの設計）
     * - プラグインも同じ形式でログを追加可能
     * - ハッシュチェーンによる改ざん検知
     * - 日次署名による固定化
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

            // Multisite scope. Site-scoped events carry the site id; system /
            // network-wide events (cron, install, maintenance, etc.) leave it
            // null. Foreign key added in 999999.
            $table->unsignedBigInteger('site_id')->nullable()->index();

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
            // 操作元チャネル / AI操作フラグ
            // ========================================
            // actor_source: web, api, cli, scheduler, ai_plugin, webhook, queue
            $table->string('actor_source', 20)->nullable()->index();
            // AI-generated operation flag (denormalized for fast filtering)
            $table->boolean('is_ai_generated')->default(false)->index();

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
            // フォーマットバージョン
            // ========================================
            $table->unsignedSmallInteger('schema_version')->default(1);

            // ========================================
            // ハッシュチェーン用カラム（改ざん検知）
            // ========================================

            // このレコードのハッシュ（SHA-256、64文字）
            // 計算対象: occurred_at + severity + outcome + category + action + actor_type + actor_id + target_type + target_id + ip_address + context + previous_hash
            $table->string('record_hash', 64)->nullable();

            // 前レコードのハッシュ（チェーン形成用）
            // 最初のレコードは 'genesis' または null
            $table->string('previous_hash', 64)->nullable();

            // チェーンシーケンス番号（連番、検証時に使用）
            $table->unsignedBigInteger('chain_sequence')->nullable();

            // ハッシュアルゴリズム（将来の変更に備えて記録）
            $table->string('hash_algorithm', 20)->default('sha256');

            // 検証ステータス（最後の検証結果）
            // null: 未検証, valid: 検証OK, invalid: 改ざん検知, skipped: スキップ
            $table->string('verification_status', 20)->nullable();

            // 最終検証日時
            $table->timestamp('last_verified_at')->nullable();

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

            // AI操作・操作元クエリ用インデックス
            $table->index(['is_ai_generated', 'category', 'occurred_at']);
            $table->index(['actor_source', 'occurred_at']);

            // ハッシュチェーン用インデックス
            $table->index('record_hash');
            $table->index('previous_hash');
            $table->index('chain_sequence');
            $table->index('verification_status');
        });

        // ========================================
        // 日次署名テーブル（監査ログの日次固定化）
        // ========================================
        Schema::create('audit_log_daily_seals', function (Blueprint $table) {
            $table->id();

            // 対象日（YYYY-MM-DD）
            $table->date('seal_date')->unique();

            // その日の最初と最後のログID
            $table->unsignedBigInteger('first_log_id');
            $table->unsignedBigInteger('last_log_id');

            // その日のログ件数
            $table->unsignedInteger('log_count');

            // その日の最終ハッシュ（チェーンの終端）
            $table->string('final_hash', 64);

            // 日次署名（HMAC-SHA256）
            // 計算対象: seal_date + first_log_id + last_log_id + log_count + final_hash
            $table->string('daily_signature', 64);

            // 署名に使用したキーのバージョン（キーローテーション対応）
            $table->unsignedSmallInteger('key_version')->default(1);

            // 署名アルゴリズム
            $table->string('signature_algorithm', 30)->default('hmac-sha256');

            // 検証ステータス
            $table->string('verification_status', 20)->default('valid');

            // 最終検証日時
            $table->timestamp('last_verified_at')->nullable();

            // メタ情報（検証履歴など）
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('seal_date');
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_log_daily_seals');
        Schema::dropIfExists('audit_logs');
    }
};
