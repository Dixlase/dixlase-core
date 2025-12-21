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
        Schema::create('theme_migrations', function (Blueprint $table) {
            $table->id();
            $table->string('migration', 255); // マイグレーションファイル名
            $table->string('theme', 255)->nullable(); // どのテーマのマイグレーションか識別
            $table->integer('batch'); // バッチ番号
            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_migrations');
    }
};
