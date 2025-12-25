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
    'index' => [
        'heading' => 'メディアマスター',
        'upload_new_file' => '新しいファイルをアップロード',
        'no_files' => 'ファイルがありません',
        'upload_first_file' => '最初のファイルをアップロードしてください',
        'preview' => 'プレビュー',
        'download' => 'ダウンロード',
        'delete' => '削除',
    ],
    'upload' => [
        'heading' => 'メディアアップロード',
        'select_file' => 'メディアファイルを選択:',
        'drag_drop_text' => 'ここにファイルをドラッグするか、クリックしてアップロード',
        'supported_formats' => '対応形式:',
    ],
    'preview' => [
        'heading' => 'メディアプレビュー',
        'no_preview' => 'ファイルはプレビューできません。',
        'media_url' => 'メディアURL',
        'url_description' => 'このURLを使用してメディアファイルに直接アクセスできます。',
        'delete_confirmation' => '削除の確認',
        'delete_message' => 'このメディアファイルを削除しますか？',
        'copy_failed' => 'コピーに失敗しました。手動でURLを選択してコピーしてください。',
    ],
    'settings' => [
        'heading' => 'メディア設定',
        'allowed_file_types' => '許可するファイルタイプ',
        'max_file_size' => '最大ファイルサイズ',
        'file_size_range' => '(1MB - 100MB)',
        'save_confirmation_title' => 'メディア設定保存の確認',
        'save_confirmation_message' => 'メディア設定を保存しますか？',
        'svg_warning' => 'SVGファイルはセキュリティリスクがあります',
        'zip_warning' => 'ZIPファイルはセキュリティリスクがあります',
        'pdf_warning' => 'PDFファイルはマクロを含む可能性があります',
        'docx_warning' => 'Wordファイルはマクロを含む可能性があります',
        'tex_warning' => 'TeXファイルは外部コマンドを実行する可能性があります',
        'zip_security_note' => 'ZIPファイルはセキュリティチェックが適用されます',
        'risky_types_warning_title' => 'セキュリティリスクのあるファイルタイプが有効です',
        'risky_types_warning_description' => '以下のファイルタイプにはセキュリティリスクがあります。信頼できるユーザーのみがアップロードできるようにしてください。',
        'risk' => [
            'svg' => 'JavaScriptや外部参照を含む可能性があり、XSS攻撃に悪用される恐れがあります。',
            'zip' => 'ZIP爆弾やマルウェアを含む可能性があります。展開時にサーバーリソースを消費する恐れがあります。',
            'pdf' => 'JavaScriptやマクロを含む可能性があり、ダウンロードしたユーザーに影響を与える恐れがあります。',
            'docx' => 'VBAマクロを含む可能性があり、ダウンロードしたユーザーに影響を与える恐れがあります。',
            'tex' => '\\input や \\write18 などのコマンドで外部ファイルを読み込んだり、シェルコマンドを実行する可能性があります。',
        ],
        'file_size_limits' => 'ファイルタイプ別サイズ上限',
        'file_size_limits_description' => 'ファイルの種類ごとに最大アップロードサイズを設定できます。',
        'category' => [
            'image' => '画像',
            'video' => '動画',
            'document' => 'ドキュメント',
            'archive' => 'アーカイブ',
        ],
        'security' => 'セキュリティ設定',
        'mime_validation' => 'MIME実体検証',
        'mime_validation_description' => 'ファイルの実体を検証し、拡張子偽装を検出します。',
        'svg_sanitization' => 'SVGサニタイズ',
        'svg_sanitization_description' => 'SVGファイルから危険なスクリプトや外部参照を自動的に除去します。無効にすると、危険なSVGはアップロードが拒否されます。',
        'zip_security' => 'ZIPセキュリティチェック',
        'zip_security_description' => 'ZIP爆弾対策として圧縮率とファイル数をチェックします。',
        'zip_max_compression_ratio' => '最大圧縮率',
        'zip_compression_ratio_help' => '展開後サイズ / 圧縮サイズの上限（ZIP爆弾対策）',
        'zip_max_file_count' => '最大ファイル数',
        'times' => '倍',
        'files' => 'ファイル',
    ],
    'search' => [
        'heading' => '検索・フィルター',
        'file_name_placeholder' => 'ファイル名で検索',
        'date_from' => 'アップロード日（開始）',
        'date_to' => 'アップロード日（終了）',
    ],
    'types' => [
        'image' => '画像',
        'video' => '動画',
        'audio' => '音声',
        'document' => 'ドキュメント',
    ],
    'error' => [
        'file_not_found' => 'ファイルが取得できませんでした',
        'save_failed' => 'ファイルの保存に失敗しました',
        'file_not_exists' => 'ファイルが存在しません',
    ],
    'success' => [
        'uploaded' => 'ファイルが正常にアップロードされました。',
        'settings_updated' => 'メディア設定が更新されました。',
    ],
    'security' => [
        'size_exceeded' => ':categoryのファイルサイズが上限を超えています（:size / 最大:max）',
        'category' => [
            'image' => '画像',
            'video' => '動画',
            'document' => 'ドキュメント',
            'archive' => 'アーカイブ',
            'other' => 'その他',
        ],
        'svg' => [
            'read_error' => 'SVGファイルの読み込みに失敗しました',
            'will_sanitize' => 'SVGファイルに危険な要素が含まれているため、サニタイズされます',
            'unsafe' => 'SVGファイルに危険な要素が含まれています。サニタイズが無効のためアップロードできません。',
        ],
        'zip' => [
            'file_not_found' => 'ZIPファイルが見つかりません',
            'invalid_zip' => '無効なZIPファイルです',
            'too_many_files' => 'ZIP内のファイル数が多すぎます（:count / 最大:max）',
            'path_traversal' => 'ZIPファイルに危険なパスが含まれています: :file',
            'forbidden_extension' => 'ZIPファイルに禁止された拡張子のファイルが含まれています: :file (:extension)',
            'hidden_file' => 'ZIPファイルに隠しファイルが含まれています: :file',
            'size_exceeded' => 'ZIP展開後のサイズが上限を超えています（:size / 最大:max）',
            'compression_bomb' => 'ZIP爆弾の可能性があります。圧縮率が高すぎます（:ratio倍 / 最大:max倍）',
        ],
        'mime' => [
            'unknown_extension' => '不明な拡張子です: :extension',
            'mime_mismatch' => 'ファイルの実体が拡張子と一致しません（:extension: 期待値 :expected, 検出値 :detected）',
            'invalid_image' => '画像ファイルが破損しているか、無効な形式です',
            'invalid_svg' => 'SVGファイルが無効な形式です',
            'magic_bytes_mismatch' => 'ファイルのマジックバイトが一致しません',
        ],
    ],
];
