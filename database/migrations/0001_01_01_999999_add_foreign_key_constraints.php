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
     *
     * 外部キー制約を一括追加
     * すべてのテーブル作成後に実行される
     */
    public function up(): void
    {
        // ========================================
        // members テーブルへの外部キー
        // ========================================

        // api_keys.created_by -> members.id
        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreign('created_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // media.uploaded_by -> members.id
        Schema::table('media', function (Blueprint $table) {
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // members_two_fa_attempts.member_id -> members.id
        Schema::table('members_two_fa_attempts', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });

        // members_two_fa_recovery_codes.member_id -> members.id
        Schema::table('members_two_fa_recovery_codes', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });

        // members_two_fa_tokens.member_id -> members.id
        Schema::table('members_two_fa_tokens', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });

        // members_trusted_devices.member_id -> members.id
        Schema::table('members_trusted_devices', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->cascadeOnDelete();
        });

        // security_events.member_id -> members.id
        Schema::table('security_events', function (Blueprint $table) {
            $table->foreign('member_id')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // file_integrity_audits.initiated_by_id -> members.id
        Schema::table('file_integrity_audits', function (Blueprint $table) {
            $table->foreign('initiated_by_id')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // ========================================
        // api_keys テーブルへの外部キー
        // ========================================

        // api_request_logs.api_key_id -> api_keys.id
        Schema::table('api_request_logs', function (Blueprint $table) {
            $table->foreign('api_key_id')
                ->references('id')
                ->on('api_keys')
                ->nullOnDelete();
        });

        // ========================================
        // webhooks テーブルへの外部キー
        // ========================================

        // webhook_deliveries.webhook_id -> webhooks.id
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->foreign('webhook_id')
                ->references('id')
                ->on('webhooks')
                ->cascadeOnDelete();
        });

        // webhook_dead_letters.webhook_id -> webhooks.id
        Schema::table('webhook_dead_letters', function (Blueprint $table) {
            $table->foreign('webhook_id')
                ->references('id')
                ->on('webhooks')
                ->cascadeOnDelete();
        });

        // webhook_dead_letters.delivery_id -> webhook_deliveries.id
        Schema::table('webhook_dead_letters', function (Blueprint $table) {
            $table->foreign('delivery_id')
                ->references('id')
                ->on('webhook_deliveries')
                ->cascadeOnDelete();
        });

        // ========================================
        // role_permission_overrides テーブルへの外部キー
        // ========================================

        // role_permission_overrides.updated_by -> members.id
        Schema::table('role_permission_overrides', function (Blueprint $table) {
            $table->foreign('updated_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // ========================================
        // extension_sources テーブルへの外部キー
        // ========================================

        // plugins.source_id -> extension_sources.id
        Schema::table('plugins', function (Blueprint $table) {
            $table->foreign('source_id')
                ->references('id')
                ->on('extension_sources')
                ->nullOnDelete();
        });

        // themes.source_id -> extension_sources.id
        Schema::table('themes', function (Blueprint $table) {
            $table->foreign('source_id')
                ->references('id')
                ->on('extension_sources')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // themes.source_id
        Schema::table('themes', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
        });

        // plugins.source_id
        Schema::table('plugins', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
        });

        // role_permission_overrides.updated_by
        Schema::table('role_permission_overrides', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
        });

        // webhook_dead_letters.delivery_id
        Schema::table('webhook_dead_letters', function (Blueprint $table) {
            $table->dropForeign(['delivery_id']);
        });

        // webhook_dead_letters.webhook_id
        Schema::table('webhook_dead_letters', function (Blueprint $table) {
            $table->dropForeign(['webhook_id']);
        });

        // webhook_deliveries.webhook_id
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropForeign(['webhook_id']);
        });

        // api_request_logs
        Schema::table('api_request_logs', function (Blueprint $table) {
            $table->dropForeign(['api_key_id']);
        });

        // file_integrity_audits
        Schema::table('file_integrity_audits', function (Blueprint $table) {
            $table->dropForeign(['initiated_by_id']);
        });

        // security_events
        Schema::table('security_events', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        // members_trusted_devices
        Schema::table('members_trusted_devices', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        // members_two_fa_tokens
        Schema::table('members_two_fa_tokens', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        // members_two_fa_recovery_codes
        Schema::table('members_two_fa_recovery_codes', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        // members_two_fa_attempts
        Schema::table('members_two_fa_attempts', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        // media
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
        });

        // api_keys
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
    }
};
