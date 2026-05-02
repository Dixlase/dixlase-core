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

        // front_page_revisions.created_by -> members.id
        Schema::table('front_page_revisions', function (Blueprint $table) {
            $table->foreign('created_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // ========================================
        // front_pages テーブルへの外部キー
        // ========================================

        // front_page_revisions.front_page_id -> front_pages.id
        Schema::table('front_page_revisions', function (Blueprint $table) {
            $table->foreign('front_page_id')
                ->references('id')
                ->on('front_pages')
                ->cascadeOnDelete();
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
        // custom_roles テーブルへの外部キー
        // ========================================

        // custom_roles.created_by -> members.id
        Schema::table('custom_roles', function (Blueprint $table) {
            $table->foreign('created_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // ========================================
        // custom_role_permission_overrides テーブルへの外部キー
        // ========================================

        // custom_role_permission_overrides.custom_role_id -> custom_roles.id
        Schema::table('custom_role_permission_overrides', function (Blueprint $table) {
            $table->foreign('custom_role_id')
                ->references('id')
                ->on('custom_roles')
                ->cascadeOnDelete();
        });

        // custom_role_permission_overrides.updated_by -> members.id
        Schema::table('custom_role_permission_overrides', function (Blueprint $table) {
            $table->foreign('updated_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // ========================================
        // members.custom_role_id への外部キー
        // ========================================

        // members.custom_role_id -> custom_roles.id
        Schema::table('members', function (Blueprint $table) {
            $table->foreign('custom_role_id')
                ->references('id')
                ->on('custom_roles')
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

        // ========================================
        // restore_records テーブルへの外部キー
        // ========================================

        // restore_records.backup_record_id -> backup_records.id
        // バックアップ削除後も履歴を保持するため nullOnDelete
        Schema::table('restore_records', function (Blueprint $table) {
            $table->foreign('backup_record_id')
                ->references('id')
                ->on('backup_records')
                ->nullOnDelete();
        });

        // restore_records.pre_restore_backup_id -> backup_records.id
        // セーフティスナップショット削除後も履歴を保持するため nullOnDelete
        Schema::table('restore_records', function (Blueprint $table) {
            $table->foreign('pre_restore_backup_id')
                ->references('id')
                ->on('backup_records')
                ->nullOnDelete();
        });

        // restore_records.restored_by -> members.id
        // メンバー削除後も履歴を保持するため nullOnDelete
        Schema::table('restore_records', function (Blueprint $table) {
            $table->foreign('restored_by')
                ->references('id')
                ->on('members')
                ->nullOnDelete();
        });

        // ========================================
        // sites テーブルへの外部キー (multisite foundation)
        // ========================================

        // site_settings.site_id -> sites.id
        // サイトが削除されたら設定も削除（cascade）
        Schema::table('site_settings', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // audit_logs.site_id -> sites.id
        // 監査ログは履歴として保持。サイト削除時は site_id を NULL にする
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->nullOnDelete();
        });

        // front_pages.site_id -> sites.id (cascade)
        Schema::table('front_pages', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // front_page_revisions.site_id -> sites.id (cascade)
        Schema::table('front_page_revisions', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // front_settings.site_id -> sites.id (cascade)
        Schema::table('front_settings', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // media.site_id -> sites.id (cascade)
        Schema::table('media', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // media_settings.site_id -> sites.id (cascade)
        Schema::table('media_settings', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // webhooks.site_id -> sites.id (cascade)
        Schema::table('webhooks', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // webhook_deliveries.site_id -> sites.id (cascade)
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // webhook_dead_letters.site_id -> sites.id (cascade)
        Schema::table('webhook_dead_letters', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // api_keys.site_id -> sites.id (cascade)
        Schema::table('api_keys', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });

        // api_request_logs.site_id -> sites.id (cascade)
        Schema::table('api_request_logs', function (Blueprint $table) {
            $table->foreign('site_id')
                ->references('id')
                ->on('sites')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // api_request_logs.site_id
        Schema::table('api_request_logs', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // api_keys.site_id
        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // webhook_dead_letters.site_id
        Schema::table('webhook_dead_letters', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // webhook_deliveries.site_id
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // webhooks.site_id
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // media_settings.site_id
        Schema::table('media_settings', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // media.site_id
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // front_settings.site_id
        Schema::table('front_settings', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // front_page_revisions.site_id
        Schema::table('front_page_revisions', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // front_pages.site_id
        Schema::table('front_pages', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // audit_logs.site_id
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // site_settings.site_id
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
        });

        // restore_records
        Schema::table('restore_records', function (Blueprint $table) {
            $table->dropForeign(['restored_by']);
            $table->dropForeign(['pre_restore_backup_id']);
            $table->dropForeign(['backup_record_id']);
        });

        // themes.source_id
        Schema::table('themes', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
        });

        // plugins.source_id
        Schema::table('plugins', function (Blueprint $table) {
            $table->dropForeign(['source_id']);
        });

        // members.custom_role_id
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['custom_role_id']);
        });

        // custom_role_permission_overrides.updated_by
        Schema::table('custom_role_permission_overrides', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
        });

        // custom_role_permission_overrides.custom_role_id
        Schema::table('custom_role_permission_overrides', function (Blueprint $table) {
            $table->dropForeign(['custom_role_id']);
        });

        // custom_roles.created_by
        Schema::table('custom_roles', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
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

        // front_page_revisions
        Schema::table('front_page_revisions', function (Blueprint $table) {
            $table->dropForeign(['front_page_id']);
            $table->dropForeign(['created_by']);
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
