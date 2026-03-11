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
    protected $table = 'members_login_attempts';

    /**
     * Run the migrations.
     *
     * ログイン試行テーブル
     * β版での行動分析機能の基盤として使用
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->index(); // email or username
            $table->string('ip_address', 45)->index(); // IPv4 or IPv6
            $table->string('user_agent')->nullable();
            $table->boolean('successful')->default(false);
            $table->timestamp('attempted_at');
            $table->timestamps();

            // ========================================
            // 行動分析用カラム（β版 行動分析の基盤）
            // ========================================

            // ログイン時刻の時間帯（0-23）- 通常のログイン時間帯を学習
            $table->unsignedTinyInteger('login_hour')->nullable();

            // ログイン曜日（0=日曜, 6=土曜）- 通常のログイン曜日を学習
            $table->unsignedTinyInteger('login_day_of_week')->nullable();

            // デバイスフィンガープリント（ブラウザ情報のハッシュ）
            $table->string('device_fingerprint', 64)->nullable()->index();

            // 国コード（GeoIP）- 通常のログイン地域を学習
            $table->string('country_code', 2)->nullable()->index();

            // 前回ログインからの経過時間（秒）- 異常な間隔を検知
            $table->unsignedInteger('seconds_since_last_login')->nullable();

            // ログイン失敗理由（詳細分析用）
            // invalid_password, account_locked, two_fa_failed, etc.
            $table->string('failure_reason', 50)->nullable();

            // 2FA使用フラグ
            $table->boolean('used_two_fa')->default(false);

            // 2FA方式（email, passkey, recovery_code）
            $table->string('two_fa_method', 20)->nullable();

            // 信頼済みデバイスからのログインか
            $table->boolean('from_trusted_device')->default(false);

            // リスクスコア（0-100）- β版で算出予定
            $table->unsignedTinyInteger('risk_score')->nullable();

            // 追加コンテキスト（JSON）- 将来の拡張用
            $table->json('context')->nullable();

            // インデックスを追加してクエリ性能を向上（カスタム名で短縮）
            $table->index(['identifier', 'attempted_at'], 'idx_login_identifier_time');
            $table->index(['ip_address', 'attempted_at'], 'idx_login_ip_time');
            $table->index(['identifier', 'ip_address', 'attempted_at'], 'idx_login_composite');

            // 行動分析用インデックス
            $table->index(['identifier', 'successful', 'attempted_at'], 'idx_login_behavior');
            $table->index(['identifier', 'login_hour'], 'idx_login_hour_pattern');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
