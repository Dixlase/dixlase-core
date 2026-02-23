<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 */

return [
    'heading' => '法務ページコンテンツ',
    'description' => '各法務ページ種別と言語ごとのコンテンツを管理します。HTMLまたはMarkdown形式でコンテンツを作成・編集できます。',

    'table_page_type' => 'ページ種別',
    'edit_link' => '編集',
    'create_link' => '作成',

    'title_label' => 'タイトル',
    'title_placeholder' => 'ページタイトルを入力',
    'editor_type_label' => 'エディタータイプ',
    'editor_html_description' => 'HTMLで直接記述します。書式を完全に制御できます。',
    'editor_markdown_description' => 'Markdown形式で記述します。シンプルで読みやすい記法です。',
    'content_label' => 'コンテンツ',
    'content_placeholder' => 'ページコンテンツを入力...',
    'status_label' => 'ステータス',
    'published_at_label' => '公開日時',
    'published_at_help' => 'このコンテンツが自動的に公開される日時を設定します。',

    'save_success' => '法務ページコンテンツを保存しました。',

    'confirm_title' => '法務ページコンテンツの保存',
    'confirm_message' => '法務ページコンテンツを保存してもよろしいですか？',

    'validation' => [
        'title_max' => 'タイトルは255文字以内で入力してください。',
        'content_max' => 'コンテンツは500,000文字以内で入力してください。',
        'editor_type_required' => 'エディタータイプを選択してください。',
        'editor_type_in' => '無効なエディタータイプです。',
        'status_required' => 'ステータスを選択してください。',
        'status_in' => '無効なステータスです。',
        'published_at_required' => 'ステータスが「日付指定」の場合、公開日時は必須です。',
        'published_at_date' => '有効な日時を入力してください。',
    ],
];
