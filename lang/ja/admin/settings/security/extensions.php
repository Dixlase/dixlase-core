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

return [
    'heading' => '拡張機能セキュリティ',
    'description' => 'プラグインやテーマのインストール・有効化に関するセキュリティポリシーを設定します。',
    'security' => [
        'title' => '拡張機能セキュリティ設定',
        'description' => 'プラグインやテーマのインストール・有効化に関するセキュリティポリシーを設定します。',
        'preset_label' => 'セキュリティプリセット',
        'preset_help' => 'プリセットを選択すると、推奨される設定が自動的に適用されます。「カスタム」を選択すると個別に設定できます。',
        'dev_only' => '開発用',
        'custom_mode_hint' => '個別設定を変更するには「カスタム」プリセットを選択してください。',
        'preset' => [
            'strict' => '厳格モード',
            'strict_description' => '署名必須、権限定義必須。最も安全な設定です。',
            'balanced' => 'バランスモード',
            'balanced_description' => '署名推奨、「注意」レベルまで許可。一般的な運用に適しています。',
            'development' => '開発モード',
            'development_description' => '未署名・未定義も許可。開発・検証環境向けです。',
            'custom' => 'カスタム',
            'custom_description' => '個別に設定をカスタマイズできます。',
        ],
        'signature_settings' => '署名要件',
        'require_signature' => '署名を必須にする',
        'require_signature_help' => '有効にすると、署名されていないプラグインやテーマのインストール・有効化を禁止します。',
        'signature_required_warning' => '署名を必須にしているため、署名されていないプラグインやテーマはインストール・有効化できません。',
        'signature_authority_url_label' => 'プラグイン署名は次の Authority から取得した公開鍵で検証されます:',
        'permission_settings' => '権限定義要件',
        'require_permission_definition' => '権限定義を必須にする',
        'require_permission_definition_help' => '有効にすると、plugin.json/theme.jsonに権限情報が定義されていない拡張機能のインストールを禁止します。',
        'allow_undefined_permissions' => '未定義の権限を許可する',
        'allow_undefined_permissions_help' => '権限情報が定義されていない拡張機能のインストールを許可します。警告は表示されます。',
        'plugin_health_level' => 'プラグインの許可健全性レベル',
        'plugin_health_level_help' => 'インストール・有効化を許可するプラグインの健全性レベルを設定します。',
        'theme_health_level' => 'テーマの許可健全性レベル',
        'theme_health_level_help' => 'インストール・有効化を許可するテーマの健全性レベルを設定します。',
        'current_setting' => '現在の設定',
        'health_level' => [
            'healthy' => '良好（Healthy）',
            'warning' => '注意（Warning）',
            'needs_attention' => '要確認（Needs Attention）',
            'not_verified' => '未確認（Not Verified）',
        ],
        'health_level_short' => [
            'healthy' => '良好',
            'warning' => '注意',
            'needs_attention' => '要確認',
            'not_verified' => '未確認',
        ],
        'health_level_description' => [
            'healthy' => '基本機能のみを使用する拡張機能です。最も安全な設定です。',
            'warning' => '一部の拡張機能を使用します。基本的な機能に加え、追加の権限が必要な拡張機能が対象です。',
            'needs_attention' => 'より多くの機能を使用します。データベースアクセスや外部通信を行う拡張機能が含まれます。',
            'not_verified' => '未確認の拡張機能も許可します。すべての拡張機能をインストール可能ですが、十分な確認をお勧めします。',
        ],
        'logic_themes' => 'ロジックを含むテーマ',
        'allow_logic_themes' => 'ロジックを含むテーマを許可する',
        'allow_logic_themes_help' => 'PHPロジック（ServiceProvider、ミドルウェア等）を含むテーマのインストールを許可します。',
        'theme_types_title' => 'テーマの種類について',
        'theme_type_pure' => 'ピュアテーマ: テンプレートファイル（Blade/HTML/CSS/JS）のみで構成。健全性チェックは軽め。',
        'theme_type_logic' => 'ロジックテーマ: ThemeServiceProviderでPHPロジックを実行。プラグインと同等のスキャン・権限制御を適用。',
        'permission_mismatch' => '権限不一致時の動作',
        'permission_mismatch_help' => '宣言された権限と実際のコードが一致しない場合の動作を設定します。',
        'mismatch_action' => [
            'warn' => '警告のみ',
            'warn_description' => '権限不一致を検出しても警告を表示するだけで、インストール・有効化は許可します。',
            'block' => 'ブロック',
            'block_description' => '権限不一致を検出した場合、インストール・有効化を禁止します。',
        ],
    ],
    'notification' => [
        'title' => '拡張機能操作通知',
        'description' => 'プラグインやテーマの操作時にシステム管理者へメール通知を送信します。通知先は基本設定のシステム管理者メールアドレスです。',
        'notify_on_install' => 'インストール時に通知',
        'notify_on_install_help' => 'プラグインやテーマがインストールされた時にメール通知を送信します。',
        'notify_on_uninstall' => 'アンインストール時に通知',
        'notify_on_uninstall_help' => 'プラグインやテーマがアンインストールされた時にメール通知を送信します。',
        'notify_on_enable' => '有効化時に通知',
        'notify_on_enable_help' => 'プラグインやテーマが有効化された時にメール通知を送信します。',
        'notify_on_disable' => '無効化時に通知',
        'notify_on_disable_help' => 'プラグインやテーマが無効化された時にメール通知を送信します。',
        'notify_on_unhealthy' => '健全性警告を通知',
        'notify_on_unhealthy_help' => '健全性が「良好」以外の拡張機能が追加・インストール・有効化された時に警告メールを送信します。',
        'log_operations' => '操作をログに記録',
        'log_operations_help' => '拡張機能の操作履歴をログファイルに記録します。',
    ],
    'source' => [
        'title' => '拡張機能ソース',
        'description' => 'プラグインやテーマのダウンロード・更新に使用する外部ソースを設定します。',
        'source_type' => 'ソースタイプ',
        'source_type_help' => '拡張機能のダウンロード元を選択します。コアにプリセットされたソースのみ利用可能です。',
        'github_description' => 'GitHub リポジトリから Releases API を使用して拡張機能をダウンロードします。',
        'reference_url' => '参照先URL',
        'owner' => 'リポジトリオーナー',
        'owner_help' => '拡張機能リポジトリを所有する GitHub の組織名またはユーザー名です。',
        'owner_placeholder' => '例: Dixlase',
        'token' => '認証トークン（オプション）',
        'token_help' => '通常は入力不要です。トークンを設定すると API レート制限が 60回/時 から 5,000回/時 に増加します。コアや公式プラグインの開発者でプライベートリポジトリへのアクセスが必要な場合にも設定してください。GitHub Personal Access Token（PAT）を入力します。',
        'token_placeholder' => 'ghp_...',
        'token_saved' => 'トークンは保存済みです',
        'token_not_set' => 'トークンが未設定です',
        'token_clear_hint' => '空欄のままで現在のトークンを維持します。新しい値を入力すると更新されます。',
        'test_connection' => '接続テスト',
        'testing' => 'テスト中...',
        'connection_success' => '接続成功',
        'connection_failed' => '接続失敗',
        'connected_as' => ':login として接続',
        'rate_limit_remaining' => 'API レート制限残り: :count',
        'official_badge' => '公式',
        'third_party_badge' => 'サードパーティ',
        'signature_invalid' => '署名が無効です',
        'update_check_interval' => '更新チェック間隔',
        'update_check_interval_help' => '拡張機能の更新を自動的にチェックする頻度を設定します。',
        'interval_daily' => '毎日（24時間）',
        'interval_12h' => '12時間ごと',
        'interval_6h' => '6時間ごと',
        'interval_manual' => '手動のみ',
        'last_checked_at' => '最終チェック: :time',
        'never_checked' => '未チェック',
    ],
    'settings_updated' => '拡張機能セキュリティ設定が更新されました。',
];
