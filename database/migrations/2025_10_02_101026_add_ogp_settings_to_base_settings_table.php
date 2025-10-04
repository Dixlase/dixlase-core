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
        Schema::table('base_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('default_ogp_image_id')->nullable()->after('timezone');
            $table->string('site_description', 500)->nullable()->after('default_ogp_image_id');
            $table->string('site_keywords', 500)->nullable()->after('site_description');
            $table->string('twitter_card_type', 50)->default('summary_large_image')->after('site_keywords');
            
            // 外部キー制約
            $table->foreign('default_ogp_image_id')->references('id')->on('media')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('base_settings', function (Blueprint $table) {
            $table->dropForeign(['default_ogp_image_id']);
            $table->dropColumn(['default_ogp_image_id', 'site_description', 'site_keywords', 'twitter_card_type']);
        });
    }
};
