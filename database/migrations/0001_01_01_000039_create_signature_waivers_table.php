<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
    protected $table = 'signature_waivers';

    /**
     * Run the migrations.
     *
     * Operator-recorded signature waivers, shared across scopes
     * (plugin | theme | core). A waiver is an OVERLAY on the objective
     * signature verification result: it suppresses the "invalid / unsigned"
     * warning for an extension the operator has deliberately accepted
     * (branded rebuild, local fork, dev work), while the underlying
     * verifier status is left untouched. See
     * .claude/plans/handoff-signature-waiver-implementation.md.
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->bigIncrements('id');

            // Cross-scope key: 'plugin' | 'theme' | 'core'.
            $table->string('scope', 16);
            // plugin/theme slug, or the literal 'core' for scope=core.
            $table->string('target_slug', 128);

            $table->timestamp('waived_at');
            // members.id — soft reference, no FK (members may be deleted).
            $table->unsignedBigInteger('waived_by')->nullable();
            $table->string('waived_by_label', 128)->nullable();
            $table->text('reason')->nullable();

            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('revoked_reason')->nullable();

            // NULL-safe single-active enforcement. MySQL/MariaDB unique indexes
            // treat NULLs as DISTINCT, so a key on (scope,target_slug,revoked_at)
            // would NOT bound active rows. Sentinel instead: 1 while active,
            // NULL once revoked -> only one (scope,target,1) can exist; revoked
            // rows (...,NULL) never collide. `unwaive` sets active=null AND
            // revoked_at=now() together.
            $table->boolean('active')->nullable()->default(true);

            $table->timestamps();

            $table->unique(['scope', 'target_slug', 'active'], 'uq_active_waiver');
            $table->index(['scope', 'target_slug'], 'idx_scope_target');
            $table->index(['scope', 'active'], 'idx_scope_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }
};
