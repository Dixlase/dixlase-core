<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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

            // 健全性スコア
            $table->integer('health_score')->nullable()->comment('健全性スコア（0-100）');
            $table->string('health_status')->nullable()->comment('健全性ステータス: healthy/advisory/needs_attention/not_verified');

            // 再スキャン判定用ファイルハッシュ
            $table->string('files_hash')->nullable()->comment('コードファイルハッシュ（再スキャン判定用）');

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
