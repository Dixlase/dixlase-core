<?php

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

            // status を string(20) に変更（enum から移行）
            $table->string('status', 20)->default('published')->change();
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

            // 不要カラムを復元
            $table->text('content_html')->nullable()->after('content');
            $table->text('content_markdown')->nullable()->after('content_html');
            $table->text('content_blade')->nullable()->after('content_markdown');
        });
    }
};
