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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    protected $table = 'custom_role_permission_overrides';

    /**
     * Run the migrations.
     *
     * カスタムロールの権限オーバーライドテーブル
     * 継承元プリセットロール（base_role）の権限に対して、
     * 個別の権限付与（grant）または拒否（deny）を定義する。
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id')->index();

            // 対象カスタムロール（外部キー制約は add_foreign_key_constraints で追加）
            $table->unsignedBigInteger('custom_role_id');

            // Permission enum 値（例: members.view, media.upload）
            $table->string('permission', 100);

            // 付与種別: grant=明示的に許可 / deny=明示的に拒否
            $table->string('grant_type', 10)->default('grant');

            // 更新者（外部キー制約は add_foreign_key_constraints で追加）
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // ロールごとに同一権限は1つだけ（site スコープ込み）
            $table->unique(['site_id', 'custom_role_id', 'permission'], 'custom_role_perm_unique');

            // インデックス
            $table->index('custom_role_id', 'custom_role_perm_role_idx');
            $table->index('permission', 'custom_role_perm_permission_idx');
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
