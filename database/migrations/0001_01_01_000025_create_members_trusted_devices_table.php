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
     * 信頼済みデバイス管理テーブル
     * ゼロトラスト基盤の一部として、デバイスを識別し既知/未知を判定
     */
    public function up(): void
    {
        Schema::create('members_trusted_devices', function (Blueprint $table) {
            $table->id();

            // 所有者
            $table->unsignedBigInteger('member_id')->index();

            // デバイス識別トークン（ハッシュ化して保存）
            $table->string('token', 255)->nullable()->index();

            // デバイス名（ユーザーが識別しやすい名前）
            $table->string('device_name', 100)->nullable();

            // アクセス元情報
            $table->string('ip_address', 45)->nullable(); // IPv6対応
            $table->text('user_agent')->nullable();
            $table->string('user_agent_hash', 64)->nullable(); // インデックス用ハッシュ

            // 信頼レベル
            // trusted: 信頼済み（2FA完了後に登録）
            // unknown: 未知（初回アクセス）
            // blocked: ブロック済み（ユーザーが明示的にブロック）
            $table->string('trust_level', 20)->default('trusted');

            // 初回アクセス時のIP（変更検知用）
            $table->string('first_ip', 45)->nullable();

            // 最終アクセス時のIP
            $table->string('last_ip', 45)->nullable();

            // 最終使用日時
            $table->timestamp('last_used_at')->nullable();

            // 標準タイムスタンプ
            $table->timestamps();

            // インデックス
            $table->index(['member_id', 'trust_level'], 'mtd_member_trust_idx');
            $table->index(['member_id', 'ip_address', 'user_agent_hash'], 'mtd_member_ip_ua_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members_trusted_devices');
    }
};
