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

return [
    'heading' => 'モード設定',
    'description' => '管理画面の表示モードを切り替えます。かんたんモードでは、メニューの表示レベルをカスタマイズできます。',

    // モード選択
    'mode_selection' => 'モード選択',
    'mode_selection_description' => '管理画面の表示モードを選択してください。モードはいつでも変更できます。',

    'simple_mode' => 'かんたんモード',
    'simple_mode_description' => '必要最小限のメニューだけを表示し、専門的な設定は自動で最適化されます。初心者や日常運用に最適です。',
    'simple_feature_auto' => 'セキュリティなどの専門設定は自動で最適化',
    'simple_feature_clean' => 'すっきりしたメニューで迷わない',
    'simple_feature_customize' => 'メニューの表示レベルは自由にカスタマイズ可能',

    'advanced_mode' => '詳細モード',
    'advanced_mode_description' => 'すべてのメニューと設定項目が表示されます。システムの細かい調整が必要な方向けです。',
    'advanced_feature_full' => 'すべてのメニューと設定にアクセス可能',
    'advanced_feature_control' => '細かいセキュリティ設定やシステム管理が可能',

    'recommended' => '推奨',

    // かんたんモードの注意事項（カード内表示）
    'simple_caution_settings_reset' => '一部のセキュリティ設定が推奨値にリセットされます',
    'simple_caution_menu_hidden' => '一部のメニューが非表示になります',
    'simple_caution_auto_optimize' => '非表示メニューの設定は自動で最適化されます',

    // 詳細モードの注意事項（カード内表示）
    'advanced_caution_all_visible' => 'すべてのメニューと設定が表示されます',
    'advanced_caution_manual' => '設定の最適化は手動で行う必要があります',
    'advanced_caution_knowledge' => 'システムへの影響を理解した上でご利用ください',

    // モード切替警告モーダル
    'switch_modal_title' => 'モードを切り替えますか？',
    'switch_to_simple_warning' => 'かんたんモードに切り替えると、以下の変更が適用されます：',
    'switch_to_simple_warn_1' => '一部のセキュリティ設定が推奨値にリセットされる場合があります',
    'switch_to_simple_warn_2' => '一部のメニューが非表示になります',
    'switch_to_simple_warn_3' => '非表示メニューの設定は自動で最適化されます',
    'switch_to_simple_note' => 'いつでも詳細モードに戻すことができます。',
    'switch_to_advanced_warning' => '詳細モードに切り替えると、以下の変更が適用されます：',
    'switch_to_advanced_warn_1' => 'すべてのメニューと設定項目が表示されます',
    'switch_to_advanced_warn_2' => 'メニュー表示のカスタマイズ設定はクリアされます',
    'switch_to_advanced_warn_3' => '設定の最適化は手動で行う必要があります',
    'switch_to_advanced_note' => 'いつでもかんたんモードに戻すことができます。',
    'switch_confirm' => '切り替える',
    'switch_cancel' => 'キャンセル',

    // メニューカスタマイズ
    'menu_customize' => 'メニュー表示カスタマイズ',
    'menu_customize_description' => 'かんたんモードで各メニューの表示レベルを調整できます。非表示にしたメニューの設定は自動で最適化されます。',

    'always_visible' => '常に表示',
    'reset_to_defaults' => 'デフォルトに戻す',

    // 表示レベル
    'visibility' => [
        'full' => 'すべて表示',
        'full_description' => 'すべての機能が表示され、操作できます。',
        'partial' => '一部表示',
        'partial_description' => '主要な機能のみ表示し、詳細設定は自動で最適化されます。',
        'hidden' => '非表示',
        'hidden_description' => 'メニューを非表示にし、設定は自動で最適化されます。',
        'read_only' => '状態表示のみ',
        'read_only_description' => '現在の設定状態を確認できますが、変更はできません。',
        'guide_only' => '導線のみ',
        'guide_only_description' => 'メニューは表示されますが、設定変更には詳細モードへの切り替えが必要です。',
    ],

    // 詳細モード情報
    'advanced_info' => '詳細モードではすべてのメニューと設定項目が表示されます。メニューの表示カスタマイズはかんたんモードでのみ利用できます。',

    // 保存
    'settings_updated' => 'モード設定が更新されました。',
    'save_confirmation_message' => 'モード設定を保存しますか？メニューの表示が変更される場合があります。',

    // 概要ページ用
    'current_mode' => '現在のモード',
];
