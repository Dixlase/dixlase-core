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

return [

    'invalid_scope' => '無効なスコープ: :scope（two_fa, password_reset, all を指定してください）',
    'reason_prompt' => 'バイパスを有効にする理由を入力してください',
    'reason_required' => '理由の入力は必須です。',
    'warning' => '⚠️ 警告: メールバイパスはセキュリティリスクを伴います。',
    'confirm_details' => '設定内容: :minutes 分間、スコープ: :scope、理由: :reason',
    'confirm_enable' => 'メールバイパスを有効にしますか？',
    'cancelled' => '操作がキャンセルされました。',
    'enabled' => '✅ メールバイパスが有効になりました（:minutes 分間、:expires_at まで）',
    'enable_failed' => 'メールバイパスの有効化に失敗しました。',
    'security_notice' => '⚠️ メール依存機能が一時的に無効化されています。復旧後は必ず無効化してください。',
    'not_active' => 'メールバイパスは現在アクティブではありません。',
    'disabled' => '✅ メールバイパスが無効になりました。',
    'status_title' => '【メールバイパス状態】',
    'status_active' => '⚠️ バイパスがアクティブです',
    'status_inactive' => '✅ バイパスは無効です（通常運用中）',
    'field' => '項目',
    'value' => '値',
    'scope' => 'スコープ',
    'reason' => '理由',
    'expires_at' => '有効期限',
    'remaining' => '残り時間',
    'minutes' => '分',
    'enabled_at' => '有効化日時',
    'affected_features' => '影響を受ける機能:',
    'feature_two_fa' => '二段階認証（メール認証）',
    'feature_password_reset' => 'パスワードリセット',
    'invalid_action' => '無効なアクション: :action',
    'valid_actions' => '有効なアクション:',
    'action_enable' => 'バイパスを有効化',
    'action_disable' => 'バイパスを無効化',
    'action_status' => '現在の状態を表示',
];
