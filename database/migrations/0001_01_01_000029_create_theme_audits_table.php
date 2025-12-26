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
        Schema::create('theme_audits', function (Blueprint $table) {
            $table->id();
            $table->string('theme_slug')->unique();
            $table->boolean('has_mismatches')->default(false);
            $table->json('mismatches')->nullable();
            $table->integer('matches_count')->default(0);
            $table->integer('total_checked')->default(0);
            $table->string('risk_level')->nullable();
            $table->json('risk_reasons')->nullable();
            
            // 署名情報
            $table->string('signature_status')->nullable()
                ->comment('署名ステータス: official/verified/partner/signed/invalid/unsigned');
            $table->string('signature_signer')->nullable()
                ->comment('署名者名');
            
            // CSP互換性
            $table->string('csp_status')->nullable()
                ->comment('CSPステータス: csp_ready/compatible/inline_required/not_checked');
            $table->boolean('csp_requires_inline_js')->default(false)
                ->comment('インラインJSが必要か');
            $table->boolean('csp_requires_inline_css')->default(false)
                ->comment('インラインCSSが必要か');
            
            $table->timestamp('audited_at')->nullable();
            $table->timestamps();
            
            $table->index('theme_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_audits');
    }
};
