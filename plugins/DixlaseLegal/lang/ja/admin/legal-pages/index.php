<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 */

return [
    'heading' => '法務ページURL設定',
    'description' => 'プライバシーポリシーや利用規約などの法務ページURLを管理します。設定されたURLは他のプラグインから参照されます。',

    'url_label' => ':name URL',
    'url_placeholder' => 'https://example.com/privacy-policy',
    'required_badge' => '必須',
    'optional_badge' => '任意',

    'save_success' => '法務ページURLを保存しました。',

    'confirm_title' => '法務ページURLの保存',
    'confirm_message' => '法務ページURLの設定を保存してもよろしいですか？',

    'validation' => [
        'url' => ':name は有効なURLを入力してください。',
        'max' => ':name のURLは2048文字以内で入力してください。',
    ],
];
