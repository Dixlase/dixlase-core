<?php

/**
 * This file is part of MySoftware.
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
    protected $table = 'plugins';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 人間が認識する名前
            $table->string('package_name')->nullable(); // パッケージ名
            $table->string('directory'); // プラグインディレクトリ名
            $table->string('slug')->unique(); // スラッグ名（一意）
            $table->string('namespace'); // プラグインの名前空間
            $table->text('description')->nullable(); // プラグインの説明
            $table->string('license')->nullable(); // ライセンス
            $table->string('author')->nullable(); // 作者
            $table->string('email')->nullable(); // 作者のメール
            $table->string('web')->nullable(); // 作者のウェブサイト
            $table->string('version'); // バージョン
            $table->tinyInteger('status')->default(0)->comment('0: disabled, 1: enabled'); // ステータス
            $table->timestamp('installed_at')->nullable(); // インストール日時
            $table->timestamps(); // Laravelの `created_at` & `updated_at`
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
