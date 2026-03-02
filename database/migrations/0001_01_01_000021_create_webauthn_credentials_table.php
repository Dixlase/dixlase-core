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
     * テーブル名
     */
    protected $table = 'webauthn_credentials';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            // WebAuthn標準フィールド
            $table->string('id', 510)->primary(); // credential_id

            // Laragear\WebAuthn用ポリモーフィックリレーション
            $table->string('authenticatable_type')->default('App\\Models\\Member');
            $table->unsignedBigInteger('authenticatable_id');

            // Dixlase用メンバーID（既存互換性のため）
            $table->unsignedBigInteger('member_id');

            // Laragear\WebAuthn用ユーザーID（UUID）
            $table->uuid('user_id');

            // WebAuthn標準フィールド
            $table->string('alias')->nullable();
            $table->unsignedBigInteger('counter')->nullable();
            $table->string('rp_id');
            $table->string('origin');
            $table->json('transports')->nullable();
            $table->uuid('aaguid')->nullable();
            $table->text('public_key');
            $table->string('attestation_format')->default('none');
            $table->json('certificates')->nullable();
            $table->timestamp('disabled_at')->nullable();
            $table->timestamps();

            // Dixlaseカスタムフィールド
            $table->string('name'); // デバイス名（必須）

            // 外部キー制約
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');

            // インデックス（名前を短く指定）
            $table->index(['authenticatable_type', 'authenticatable_id'], 'passkeys_authenticatable_index');
            $table->index('member_id');
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
