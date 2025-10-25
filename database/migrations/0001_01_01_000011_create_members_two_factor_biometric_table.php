<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    protected $table = 'members_two_factor_biometric';

    /**
     * Run the migrations.
     *
     * @return void
     */

    protected $table = 'base_settings';

    public function up()
    {
        Schema::create($this->table, function (Blueprint $table) {
<<<<<<<< HEAD:database/migrations/0001_01_01_000013_create_base_settings_table.php
            $table->bigIncrements('id');
            $table->string("name", 255)->nullable();
            $table->text("value")->nullable();
            $table->unsignedBigInteger('default_ogp_image_id')->nullable();
            $table->string('site_description', 500)->nullable();
            $table->string('site_keywords', 500)->nullable();
            $table->string('twitter_card_type', 50)->default('summary_large_image');
            $table->timestamps();
            $table->softDeletes();
            
            // 外部キー制約は不要（アプリケーションレベルで管理）
            // NOTE: default_ogp_image_id は media テーブルを参照しますが、
            // 設定の柔軟性を保つため、DBレベルの制約は設定しません
========
            $table->id();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('credential_id')->unique();
            $table->text('public_key');
            $table->string('name')->nullable(); // デバイス名
            $table->string('authenticator_type')->default('platform'); // platform, cross-platform
            $table->json('transports')->nullable(); // ['usb', 'nfc', 'ble', 'internal']
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->index(['member_id', 'credential_id']);
>>>>>>>> v0.0093_2fa:database/migrations/0001_01_01_000011_create_members_two_factor_biometric_table.php
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->table);
    }
};
