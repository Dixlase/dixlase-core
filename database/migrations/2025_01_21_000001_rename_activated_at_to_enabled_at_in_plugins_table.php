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
        // プラグインテーブル: activated_at → enabled_at
        Schema::table('plugins', function (Blueprint $table) {
            $table->renameColumn('activated_at', 'enabled_at');
        });

        // テーマ設定テーブル: active_theme_id → enabled_theme_id
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->renameColumn('active_theme_id', 'enabled_theme_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // プラグインテーブル: enabled_at → activated_at
        Schema::table('plugins', function (Blueprint $table) {
            $table->renameColumn('enabled_at', 'activated_at');
        });

        // テーマ設定テーブル: enabled_theme_id → active_theme_id
        Schema::table('theme_settings', function (Blueprint $table) {
            $table->renameColumn('enabled_theme_id', 'active_theme_id');
        });
    }
};
