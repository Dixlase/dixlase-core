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

---

## マークダウンタグサンプル

### テキスト装飾

これは**太字**、*斜体*、***太字斜体***、~~取り消し線~~のテストです。

`インラインコード`もサポートされています。

> これはブロック引用です。複数行にまたがることができ、重要なコンテンツの強調に使えます。

### ネストされたリスト

- 項目1
- 項目2
  - サブ項目2-1
  - サブ項目2-2
- 項目3

### 番号付きリスト

1. 最初の項目
2. 2番目の項目
3. 3番目の項目

### リンク

[Dixlase公式サイト](https://example.com)

### テーブル

| 列1 | 列2 | 列3 |
|------|------|------|
| A1 | B1 | C1 |
| A2 | B2 | C2 |
| A3 | B3 | C3 |

### コードブロック

```javascript
function hello() {
    console.log("Hello, Dixlase!");
}
```

### タスクリスト

- [x] 完了したタスク
- [ ] 未完了のタスク
- [ ] もう一つの未完了タスク

## 画像サンプル

![サンプル画像](data:image/svg+xml,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20width='600'%20height='300'%20viewBox='0%200%20600%20300'%3E%3Crect%20fill='%234f46e5'%20width='600'%20height='300'/%3E%3Ctext%20x='50%25'%20y='50%25'%20dominant-baseline='middle'%20text-anchor='middle'%20fill='white'%20font-family='system-ui'%20font-size='24'%3E%E3%82%B5%E3%83%B3%E3%83%97%E3%83%AB%E7%94%BB%E5%83%8F%20(600x300)%3C/text%3E%3C/svg%3E)
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

<hr>

<h2>HTMLタグサンプル</h2>

<h3>テキスト装飾</h3>
<p>これは<strong>太字</strong>、<em>斜体</em>、<u>下線</u>、<s>取り消し線</s>、<mark>ハイライト</mark>のテストです。</p>
<p><code>インラインコード</code>もサポートされています。<a href="#">リンクテスト</a>。</p>

<blockquote>
  <p>これはブロック引用です。複数行にまたがることができ、重要なコンテンツの強調に使えます。</p>
</blockquote>

<h3>ネストされたリスト</h3>
<ul>
    <li>項目1</li>
    <li>項目2
        <ul>
            <li>サブ項目2-1</li>
            <li>サブ項目2-2</li>
        </ul>
    </li>
    <li>項目3</li>
</ul>

<h3>番号付きリスト</h3>
<ol>
    <li>最初の項目</li>
    <li>2番目の項目</li>
    <li>3番目の項目</li>
</ol>

<h3>テーブル</h3>
<table>
    <thead>
        <tr><th>列1</th><th>列2</th><th>列3</th></tr>
    </thead>
    <tbody>
        <tr><td>A1</td><td>B1</td><td>C1</td></tr>
        <tr><td>A2</td><td>B2</td><td>C2</td></tr>
    </tbody>
</table>

<h3>画像</h3>
<p>キャプション付き画像:</p>
<figure>
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='600' height='300' viewBox='0 0 600 300'%3E%3Crect fill='%234f46e5' width='600' height='300'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-family='system-ui' font-size='24'%3E%E3%82%B5%E3%83%B3%E3%83%97%E3%83%AB%E7%94%BB%E5%83%8F (600x300)%3C/text%3E%3C/svg%3E" alt="サンプル画像" style="max-width:100%;height:auto;border-radius:0.5rem;">
    <figcaption>図1: サンプルプレースホルダー画像</figcaption>
</figure>

<p>横並びの画像:</p>
<div style="display:flex;gap:1rem;flex-wrap:wrap;">
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='280' height='180' viewBox='0 0 280 180'%3E%3Crect fill='%230d9488' width='280' height='180'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-family='system-ui' font-size='16'%3E%E5%86%99%E7%9C%9F 1%3C/text%3E%3C/svg%3E" alt="写真1" style="border-radius:0.5rem;">
    <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='280' height='180' viewBox='0 0 280 180'%3E%3Crect fill='%23d97706' width='280' height='180'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' fill='white' font-family='system-ui' font-size='16'%3E%E5%86%99%E7%9C%9F 2%3C/text%3E%3C/svg%3E" alt="写真2" style="border-radius:0.5rem;">
</div>

<h3>コードブロック</h3>
<pre><code>function hello() {
    console.log("Hello, Dixlase!");
}</code></pre>

<h3>カスタムスタイルカード</h3>
<div class="custom-card">
    <h3>サンプルカード</h3>
    <p>このカードはカスタムCSSのスタイリングを示しています。</p>
</div>

<div class="custom-card accent">
    <h3>アクセントカード</h3>
    <p>異なるスタイルのバリエーションです。</p>
</div>
HTML,

        'content_css' => <<<'CSS'
/* カスタムカードスタイル */
.custom-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.custom-card.accent {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.custom-card h3 {
    margin-top: 0;
    font-size: 1.25rem;
}
CSS,

        'content_js' => <<<'JS'
// サンプル: ページ読み込みログ
console.log('フロントページが正常に読み込まれました。');
JS,
    ],
];
