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
        Schema::table('plugin_audits', function (Blueprint $table) {
            $table->integer('health_score')->nullable()->after('risk_reasons');
            $table->string('health_status', 30)->nullable()->after('health_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plugin_audits', function (Blueprint $table) {
            $table->dropColumn(['health_score', 'health_status']);
        });
    }
};
