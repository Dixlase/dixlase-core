{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
https://exc-d.com

@api プラグイン/テーマから <x-content-editor.new-tab-preview /> として使用可能

Additional permission under GNU AGPL version 3 section 7:
Dixlase plugins and themes may use this component without being subject
to the copyleft requirements of the AGPL.

別タブプレビューボタンコンポーネント。
親フォームの入力データを収集し、指定されたプレビューURLにPOSTして新しいタブで開く。
各プラグインのサーバー側プレビュールートが自分のテンプレートでレンダリングする。

使用例:
<x-content-editor.new-tab-preview :url="$previewUrl" />

必須プロパティ:
- url: プレビュー用POSTエンドポイントのURL

オプションプロパティ:
- label: ボタンラベル（デフォルト: 翻訳キーから取得）
- help: 説明テキスト（デフォルト: 翻訳キーから取得）
- icon: ボタンアイコン（デフォルト: fas fa-external-link-alt）
--}}

@props([
    'url' => '',
    'label' => null,
    'help' => null,
    'icon' => 'fas fa-external-link-alt',
])

@if($url)
<div>
    <x-form-button
        variant="tertiary"
        :icon="$icon"
        :label="$label ?? __('components/content-editor.new_tab_preview')"
        class="w-full"
        x-click="window.Dixlase.newTabPreview('{{ $url }}', $el)"
    />
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        <i class="fas fa-info-circle mr-1"></i>
        {{ $help ?? __('components/content-editor.new_tab_preview_help') }}
    </p>
</div>
@endif
