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
    /**
     * Run the migrations.
     *
     * Restore records table
     * Stores audit trail for backup restore operations (who, what, when)
     */
    public function up(): void
    {
        Schema::create('restore_records', function (Blueprint $table) {
            $table->id();

            // Multisite scope. Site-scoped restores carry the site id;
            // network-wide restores leave it null.
            $table->unsignedBigInteger('site_id')->nullable()->index();

            // どのバックアップから復元したか（バックアップ削除後も履歴を保持）
            $table->unsignedBigInteger('backup_record_id')->nullable()->index();

            // 復元実行者（メンバー削除後も履歴を保持）
            $table->unsignedBigInteger('restored_by')->nullable()->index();
            $table->string('restored_by_name', 255)->nullable();   // 表示用スナップショット

            // 復元実行時刻
            $table->timestamp('restored_at')->index();

            // 実際に復元した対象（バックアップ全体の一部のみ復元する場合がある）
            // 例: ["database"] / ["media", "custom"] / ["database", "media", "private", "custom"]
            $table->json('targets');

            // ステータス: pending, in_progress, completed, failed, rolled_back
            $table->string('status', 20)->default('pending')->index();

            // 復元前の安全スナップショット（復元の取り消しに使用）
            $table->unsignedBigInteger('pre_restore_backup_id')->nullable()->index();

            // 実行情報
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('error')->nullable();

            // メタデータ: 復元したテーブル数、ファイル数、警告等
            $table->json('metadata')->nullable();

            $table->timestamps();

            // 複合インデックス
            $table->index(['status', 'restored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restore_records');
    }
};
