<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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
        Schema::create('theme_audits', function (Blueprint $table) {
            $table->id();
            $table->string('theme_slug')->unique();
            $table->boolean('has_mismatches')->default(false);
            $table->json('mismatches')->nullable();
            $table->integer('matches_count')->default(0);
            $table->integer('total_checked')->default(0);
            $table->string('risk_level')->nullable();
            $table->json('risk_reasons')->nullable();

            // Signature information
            $table->string('signature_status')->nullable()
                ->comment('Signature status: official/verified/partner/signed/invalid/unsigned');
            $table->string('signature_signer')->nullable()
                ->comment('Signer name');

            // CSP compatibility
            $table->string('csp_status')->nullable()
                ->comment('CSP status: csp_ready/compatible/inline_required/not_checked');
            $table->boolean('csp_requires_inline_js')->default(false)
                ->comment('Requires inline JS');
            $table->boolean('csp_requires_inline_css')->default(false)
                ->comment('Requires inline CSS');
            $table->json('csp_violations')->nullable()
                ->comment('CSP violation details');
            $table->json('csp_summary')->nullable()
                ->comment('CSP violation summary');

            // Health score
            $table->integer('health_score')->nullable()->comment('Health score（0-100）');
            $table->string('health_status')->nullable()->comment('Health status: healthy/advisory/needs_attention/not_verified');
            $table->json('health_issues')->nullable()->comment('Health issues (deduction reasons) list');

            // Database owned tables (PluginTableInspector result cache)
            $table->json('owned_tables')->nullable()->comment('Table names created/owned by theme');

            // File hash for rescan determination
            $table->string('files_hash')->nullable()->comment('Code file hash (for rescan determination)');

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
