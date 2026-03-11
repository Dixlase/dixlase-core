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
    'main_content' => [
        'content_markdown' => <<<'MARKDOWN'
# 当サイトへようこそ

当サイトにお越しいただき、ありがとうございます。このページはサイトのフロントページです。訪問者に向けてサイトの紹介を行いましょう。

## 私たちについて

ここに組織、ビジネス、またはプロジェクトについての簡単な紹介を記述してください。

## サービス紹介

- **サービス1**: 最初のサービスの説明
- **サービス2**: 2番目のサービスの説明
- **サービス3**: 3番目のサービスの説明

## お問い合わせ

お気軽にお問い合わせください：

- メール: [email@example.com]
- 電話: [電話番号]
- 住所: [住所]
MARKDOWN,
        'content_html' => <<<'HTML'
<h1>当サイトへようこそ</h1>
<p>当サイトにお越しいただき、ありがとうございます。このページはサイトのフロントページです。訪問者に向けてサイトの紹介を行いましょう。</p>

<h2>私たちについて</h2>
<p>ここに組織、ビジネス、またはプロジェクトについての簡単な紹介を記述してください。</p>

<h2>サービス紹介</h2>
<ul>
    <li><strong>サービス1</strong>: 最初のサービスの説明</li>
    <li><strong>サービス2</strong>: 2番目のサービスの説明</li>
    <li><strong>サービス3</strong>: 3番目のサービスの説明</li>
</ul>

<h2>お問い合わせ</h2>
<p>お気軽にお問い合わせください：</p>
<ul>
    <li>メール: [email@example.com]</li>
    <li>電話: [電話番号]</li>
    <li>住所: [住所]</li>
</ul>
HTML,
    ],
];
