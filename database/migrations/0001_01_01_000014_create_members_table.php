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
     */
    protected $table = 'members';

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('account_name'); // ログイン用アカウント名（半角英数字）
            $table->string('display_name')->nullable(); // 表示名（管理バー等に表示）
            $table->string('description')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable(); // メール認証日時
            $table->string('pending_email')->nullable(); // 認証待ちの新メールアドレス
            $table->string('locale')->nullable(); // 個別言語設定（nullの場合はシステムデフォルト）
            $table->integer('role')->default(1);   // 1=admin, 2=super_admin, 3=editor, 4=author, 5=contributor
            $table->integer('appearance')->default(0); // 0= auto, 1 = light, 2 = dark
            $table->string('password'); // Hashed
            $table->integer('login_notification_mode')->default(2); // 0= Disabled, 1= DifferentDevice, 2= Always
            $table->integer('two_fa_mode')->default(0); // 0= Disabled, 1= DifferentDevice, 2= Always
            $table->boolean('passkey_prompt_dismissed')->default(false)->comment('パスキー登録促進モーダルを非表示にするかどうか');
            $table->json('sidebar_preferences')->nullable()->comment('Per-member sidebar menu visibility preferences');
            $table->string('last_login_ip')->nullable();
            $table->text('last_login_ua')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('status')->default(0);  // 0 = inactive, 1 = active
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            // 検索パフォーマンス向上のためのインデックス（ユニーク制約なし）
            $table->index('email');
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
