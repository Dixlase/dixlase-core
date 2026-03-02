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
    public function up(): void
    {
        Schema::table('front_pages', function (Blueprint $table) {
            // lang カラム追加（存在しない場合のみ）
            if (! Schema::hasColumn('front_pages', 'lang')) {
                $table->string('lang', 10)->default('en')->after('page_type');
            }

            // 不要カラム削除
            $dropColumns = [];
            foreach (['content_html', 'content_markdown', 'content_blade'] as $col) {
                if (Schema::hasColumn('front_pages', $col)) {
                    $dropColumns[] = $col;
                }
            }
            if (! empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }

            // custom_js / custom_css カラム追加（HTML エディタ用）
            if (! Schema::hasColumn('front_pages', 'custom_js')) {
                $table->text('custom_js')->nullable()->after('content');
            }
            if (! Schema::hasColumn('front_pages', 'custom_css')) {
                $table->text('custom_css')->nullable()->after('custom_js');
            }

            // status を tinyInteger に変更（enum から移行）
            $table->tinyInteger('status')->default(1)->change();
        });

        // インデックスの操作は別トランザクションで実行
        Schema::table('front_pages', function (Blueprint $table) {
            // page_type の unique インデックスを削除
            $table->dropUnique(['page_type']);

            // (page_type, lang) の複合 unique インデックスを追加
            $table->unique(['page_type', 'lang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('front_pages', function (Blueprint $table) {
            // 複合 unique インデックスを削除
            $table->dropUnique(['page_type', 'lang']);

            // page_type の unique インデックスを復元
            $table->unique('page_type');
        });

        Schema::table('front_pages', function (Blueprint $table) {
            // lang カラム削除
            $table->dropColumn('lang');

            // custom_js / custom_css カラム削除
            $dropCols = [];
            foreach (['custom_js', 'custom_css'] as $col) {
                if (Schema::hasColumn('front_pages', $col)) {
                    $dropCols[] = $col;
                }
            }
            if (! empty($dropCols)) {
                $table->dropColumn($dropCols);
            }

            // 不要カラムを復元
            $table->text('content_html')->nullable()->after('content');
            $table->text('content_markdown')->nullable()->after('content_html');
            $table->text('content_blade')->nullable()->after('content_markdown');
        });
    }
};
