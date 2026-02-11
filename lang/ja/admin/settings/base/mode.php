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
