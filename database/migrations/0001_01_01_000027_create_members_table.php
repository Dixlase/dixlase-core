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
    protected $table = 'members';

    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('account_name'); // Account name for login (alphanumeric)
            $table->string('display_name')->nullable(); // Display name (shown in admin bar, etc.)
            $table->string('description')->nullable();
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable(); // Email verification timestamp
            $table->string('pending_email')->nullable(); // New email address pending verification
            $table->string('locale')->nullable(); // Individual language settings (system default if null)
            $table->integer('role')->default(1);   // 1=admin, 2=super_admin, 3=editor, 4=author, 5=contributor
            $table->unsignedBigInteger('custom_role_id')->nullable(); // Custom role (foreign key constraint added in add_foreign_key_constraints)
            $table->integer('appearance')->default(0); // 0= auto, 1 = light, 2 = dark
            $table->string('password')->nullable(); // Hashed; null until the member sets one (e.g. invited members)
            $table->integer('login_notification_mode')->default(2); // 0= Disabled, 1= DifferentDevice, 2= Always
            $table->integer('two_fa_mode')->default(0); // 0= Disabled, 1= DifferentDevice, 2= Always
            $table->boolean('passkey_prompt_dismissed')->default(false)->comment('Whether to hide the passkey registration promotion modal');
            $table->boolean('getting_started_dismissed')->default(false)->comment('Whether to hide the getting started card');
            $table->json('getting_started_visited')->nullable()->comment('Visited steps of the getting started card');
            $table->json('sidebar_preferences')->nullable()->comment('Per-member sidebar menu visibility preferences');
            $table->string('last_login_ip')->nullable();
            $table->text('last_login_ua')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->integer('status')->default(0);  // 0 = inactive, 1 = active
            $table->rememberToken();

            $table->timestamps();
            $table->softDeletes();

            // Index for improved search performance (no unique constraint)
            $table->index('email');
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
