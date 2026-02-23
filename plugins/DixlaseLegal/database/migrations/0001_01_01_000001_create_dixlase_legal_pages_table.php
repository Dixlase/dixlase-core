<?php

/**
 * This file is part of Dixlase Legal.
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
     * マイグレーション実行
     */
    public function up(): void
    {
        Schema::create('plg_dixlase_legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->comment('ページ種別スラッグ');
            $table->string('lang', 10)->comment('言語コード');
            $table->string('title')->nullable()->comment('ページタイトル');
            $table->text('content_html')->nullable()->comment('HTMLコンテンツ');
            $table->text('content_markdown')->nullable()->comment('Markdownコンテンツ');
            $table->string('editor_type', 20)->default('html')->comment('エディタータイプ');
            $table->string('status', 20)->default('draft')->comment('ステータス');
            $table->timestamp('published_at')->nullable()->comment('公開日時');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['slug', 'lang', 'deleted_at'], 'plg_dxl_legal_pages_slug_lang_del_unique');
        });
    }

    /**
     * マイグレーションロールバック
     */
    public function down(): void
    {
        Schema::dropIfExists('plg_dixlase_legal_pages');
    }
};
