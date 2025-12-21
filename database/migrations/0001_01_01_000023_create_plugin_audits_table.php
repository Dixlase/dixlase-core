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
        Schema::create('plugin_audits', function (Blueprint $table) {
            $table->id();
            $table->string('plugin_slug')->unique();
            $table->boolean('has_mismatches')->default(false);
            $table->json('mismatches')->nullable();
            $table->integer('matches_count')->default(0);
            $table->integer('total_checked')->default(0);
            $table->string('risk_level')->nullable();
            $table->json('risk_reasons')->nullable();
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();
            
            $table->index('plugin_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_audits');
    }
};
