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
        Schema::create('thm_dixlase_default_theme_settings', function (Blueprint $table) {
            $table->id();
            
            // Hero Section Settings
            $table->unsignedBigInteger('hero_background_image_id')->nullable();
            $table->string('hero_main_title')->default('Welcome to Dixlase');
            $table->text('hero_sub_title')->nullable();
            $table->string('hero_button_text')->default('Get Started');
            $table->string('hero_button_link')->default('#');
            $table->string('hero_button_secondary_text')->nullable();
            $table->string('hero_button_secondary_link')->nullable();
            
            // Footer Settings
            $table->text('footer_description')->nullable();
            $table->json('footer_links')->nullable(); // Array of {title, url}
            $table->string('footer_copyright')->default('© 2025 Dixlase. All rights reserved.');
            
            // SNS Links Settings
            $table->string('footer_sns_instagram')->nullable();
            $table->string('footer_sns_x')->nullable();
            $table->string('footer_sns_facebook')->nullable();
            $table->string('footer_sns_tiktok')->nullable();
            $table->string('footer_sns_bluesky')->nullable();
            $table->string('footer_sns_threads')->nullable();
            $table->string('footer_sns_linkedin')->nullable();
            $table->string('footer_sns_youtube')->nullable();
            $table->string('footer_sns_pinterest')->nullable();
            $table->string('footer_sns_discord')->nullable();
            
            // Color Settings
            $table->string('primary_color', 7)->default('#3b82f6');
            $table->string('secondary_color', 7)->default('#6b7280');
            $table->string('accent_color', 7)->default('#10b981');
            
            $table->timestamps();
            
            // 外部キー制約は不要（アプリケーションレベルで管理）
            // NOTE: hero_background_image_id は media テーブルの id を参照しますが、
            // テーマ設定は柔軟性を保つため、DBレベルの制約は設定しません
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thm_dixlase_default_theme_settings');
    }
};
