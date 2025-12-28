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
    'heading' => 'メディア設定',
    'description' => 'アップロード可能なファイルタイプと最大ファイルサイズを設定します。',
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
    'files' => '個',
];
