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
        Schema::create('dixlase_default_theme_settings', function (Blueprint $table) {
            $table->id();
            
            // Header Settings
            $table->string('logo_url')->nullable();
            $table->string('logo_text')->default('Dixlase');
            
            // Hero Section Settings
            $table->string('hero_background_image')->nullable();
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
            $table->string('footer_sns_facebook')->nullable();
            $table->string('footer_sns_twitter')->nullable();
            $table->string('footer_sns_instagram')->nullable();
            $table->string('footer_sns_linkedin')->nullable();
            $table->string('footer_sns_youtube')->nullable();
            
            // Color Settings
            $table->string('primary_color', 7)->default('#3b82f6');
            $table->string('secondary_color', 7)->default('#6b7280');
            $table->string('accent_color', 7)->default('#10b981');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dixlase_default_theme_settings');
    }
};
