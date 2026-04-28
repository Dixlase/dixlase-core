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
     */
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
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
            $table->string('url')->nullable(); // 作者のウェブサイト
            $table->string('version'); // バージョン
            $table->unsignedBigInteger('source_id')->nullable()->index(); // Extension source reference
            $table->string('source_repo')->nullable(); // Repository name at source
            $table->string('available_version')->nullable(); // Latest available version from source
            $table->timestamp('last_version_check')->nullable(); // Last update check timestamp
            // サプライチェーン攻撃防御用カラム
            $table->string('signing_key_id')->nullable()->index(); // 初回インストール時の署名鍵ID
            $table->string('author_id')->nullable()->index(); // plugin.json の author_id
            $table->string('authority_key_id')->nullable(); // Authority 公開鍵 ID（配信元の Ed25519 鍵を識別）
            $table->string('installed_from_url')->nullable(); // インストール元URL
            $table->string('installation_method')->nullable(); // upload/marketplace/cli/github
            $table->timestamp('installed_at')->nullable(); // インストール日時
            $table->timestamp('enabled_at')->nullable(); // 有効化日時
            $table->timestamps(); // Laravelの `created_at` & `updated_at`
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
