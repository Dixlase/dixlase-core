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
    protected $table = 'custom_roles';

    /**
     * Run the migrations.
     *
     * カスタムロールテーブル
     * プリセットロール（MemberRole enum）を継承して、
     * 権限の追加・制限を行うカスタムロールを定義する。
     * AI専用ロールやサービス用ロールもここで管理する。
     */
    public function up(): void
    {
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();

            // スラッグ（一意識別子）例: ai_content_writer, store_manager
            $table->string('slug', 100)->unique();

            // 多言語名 {"en": "AI Content Writer", "ja": "AIコンテンツライター"}
            $table->json('name');

            // 多言語説明文
            $table->json('description')->nullable();

            // 継承元プリセットロールの MemberRole enum 値
            // 例: MemberRole::EDITOR->value (8)
            // カスタムロールはこのプリセットの権限をベースに差分を定義する
            $table->unsignedTinyInteger('base_role');

            // アクター種別: human / ai / service
            $table->string('actor_type', 20)->default('human');

            // UI表示順
            $table->unsignedSmallInteger('sort_order')->default(0);

            // バッジ色（例: #3B82F6）
            $table->string('color', 7)->nullable();

            // このロールに割り当て可能な最大メンバー数（null=無制限）
            $table->unsignedInteger('max_members')->nullable();

            // 有効/無効フラグ
            $table->boolean('is_active')->default(true);

            // 作成者（外部キー制約は add_foreign_key_constraints で追加）
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // インデックス
            $table->index('actor_type');
            $table->index('base_role');
            $table->index('is_active');
            $table->index('sort_order');
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
