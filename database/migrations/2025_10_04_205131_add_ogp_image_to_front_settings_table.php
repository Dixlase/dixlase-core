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
        Schema::table('front_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('front_ogp_image_id')->nullable()->after('value');
            
            // 外部キー制約
            $table->foreign('front_ogp_image_id')->references('id')->on('media')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('front_settings', function (Blueprint $table) {
            $table->dropForeign(['front_ogp_image_id']);
            $table->dropColumn('front_ogp_image_id');
        });
    }
};
