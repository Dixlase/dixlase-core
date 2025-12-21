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
     * 監査ログにハッシュチェーン機能を追加
     * 
     * 目的：
     * - ログの改ざん検知（前レコードのハッシュを含めることでチェーン形成）
     * - 整合性検証（任意のタイミングでチェーン全体を検証可能）
     * - 日次署名/固定化の基盤（将来拡張用）
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // ========================================
            // ハッシュチェーン用カラム
            // ========================================
            
            // このレコードのハッシュ（SHA-256、64文字）
            // 計算対象: occurred_at + severity + outcome + category + action + actor_type + actor_id + target_type + target_id + ip_address + context + previous_hash
            $table->string('record_hash', 64)->nullable()->after('schema_version');
            
            // 前レコードのハッシュ（チェーン形成用）
            // 最初のレコードは 'genesis' または null
            $table->string('previous_hash', 64)->nullable()->after('record_hash');
            
            // チェーンシーケンス番号（連番、検証時に使用）
            $table->unsignedBigInteger('chain_sequence')->nullable()->after('previous_hash');
            
            // ハッシュアルゴリズム（将来の変更に備えて記録）
            $table->string('hash_algorithm', 20)->default('sha256')->after('chain_sequence');
            
            // 検証ステータス（最後の検証結果）
            // null: 未検証, valid: 検証OK, invalid: 改ざん検知, skipped: スキップ
            $table->string('verification_status', 20)->nullable()->after('hash_algorithm');
            
            // 最終検証日時
            $table->timestamp('last_verified_at')->nullable()->after('verification_status');
            
            // ========================================
            // インデックス
            // ========================================
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

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['record_hash']);
            $table->dropIndex(['previous_hash']);
            $table->dropIndex(['chain_sequence']);
            $table->dropIndex(['verification_status']);
            
            $table->dropColumn([
                'record_hash',
                'previous_hash',
                'chain_sequence',
                'hash_algorithm',
                'verification_status',
                'last_verified_at',
            ]);
        });
    }
};
