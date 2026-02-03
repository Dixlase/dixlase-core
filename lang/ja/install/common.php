<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    'title' => 'インストール',
    'header' => 'Dixlase インストール',
    
    // 共通ボタン
    'back' => '戻る',
    'next' => '次へ',
    'back_button' => '戻る',
    
    // ステップ表示
    'step_of_total' => ':current / :total ステップ',
    
    // 状態表示
    'ok' => 'OK',
    'failed' => 'NG',
    'enabled' => '有効',
    'disabled' => '無効',
    'none' => 'なし',
    'not_executed' => '未実行',
    
    // 必須・オプション
    'required' => '必須',
    'optional' => 'オプション',
    'not_required' => '必須ではありません',
    
    // 言語
    'languages' => [
        'en' => 'English',
        'ja' => '日本語',
    ],
    
    // レイアウト関連
    'installation_progress' => 'インストール進捗',
    'language_selection' => '言語選択',
    'error_label' => 'エラー',
    'validation_errors' => '入力エラー',
    'form_navigation' => 'フォーム操作',
    'back_to_previous_step' => '前のステップに戻る',
    
    // ツールチップ
    'tooltip_generate' => 'パスワードを自動生成',
    'tooltip_copy' => 'パスワードをコピー',
    'tooltip_toggle' => 'パスワードの表示切り替え',
    'tooltip_test_db' => 'DB接続テストを行ってください。',
    
    // エラーメッセージ
    'missing_required_fields' => '必須項目が不足しています。インストール手順を最初からやり直してください。',
    'please_complete_previous_steps' => '先に前の手順を完了させてください。',
    'please_complete_this_step' => 'この手順を完了させてください。',
    'password_paste_error' => 'パスワードのコピー＆ペーストは禁止されています。手入力してください。',
    'required_issues' => '必須項目に問題があります。インストールを続行するには上記の問題を解決してください。',
];
