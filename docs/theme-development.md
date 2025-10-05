# Dixlaseテーマ開発ガイド

このドキュメントでは、Dixlaseのテーマ開発方法について説明します。

## 目次

- [テーマの基本構造](#テーマの基本構造)
- [開発環境のセットアップ](#開発環境のセットアップ)
- [ファイル構成](#ファイル構成)
- [カスタマイズ方法](#カスタマイズ方法)
- [ビュー優先順位](#ビュー優先順位)
- [ベストプラクティス](#ベストプラクティス)

---

## テーマの基本構造

Dixlaseのテーマは以下の構造を持ちます:

```
themes/YourTheme/
├── theme.json              # テーマメタデータ（必須）
├── screenshot.png          # プレビュー画像（必須）
├── composer.json           # PHP依存関係（オプション）
├── package.json            # npm依存関係（オプション）
├── vite.config.js          # ビルド設定（オプション）
├── README.md               # テーマ説明
├── resources/
│   ├── views/              # Bladeテンプレート
│   │   ├── layouts/        # レイアウトファイル
│   │   │   ├── app.blade.php           # 基本レイアウト（必須）
│   │   │   └── frontpage.blade.php     # フロントページ（必須）
│   │   ├── partials/       # パーシャルファイル
│   │   │   ├── header.blade.php        # ヘッダー（必須）
│   │   │   └── footer.blade.php        # フッター（必須）
│   │   ├── components/     # テーマ固有コンポーネント
│   │   │   └── site-logo.blade.php
│   │   └── pages/          # ページテンプレート
│   │       └── 404.blade.php           # エラーページ（推奨）
│   └── assets/             # アセットファイル
│       ├── css/
│       │   ├── variables.css           # CSS変数（推奨）
│       │   └── style.css               # メインスタイル（必須）
│       ├── js/
│       │   └── app.js                  # メインスクリプト（必須）
│       └── images/
│           └── default-logo.svg
├── lang/                   # 翻訳ファイル（推奨）
│   ├── ja/
│   │   └── theme.php
│   └── en/
│       └── theme.php
└── config/                 # 設定ファイル（オプション）
    └── theme.php
```

---

## 開発環境のセットアップ

### 1. 新しいテーマの作成

```bash
# テーマディレクトリを作成
mkdir -p themes/YourTheme

# 必須ファイルを作成
touch themes/YourTheme/theme.json
touch themes/YourTheme/screenshot.png
```

### 2. theme.jsonの設定

```json
{
    "name": "Your Theme Name",
    "slug": "your-theme-slug",
    "version": "1.0.0",
    "description": {
        "ja": "テーマの説明（日本語）",
        "en": "Theme description (English)"
    },
    "author": "Your Name",
    "author_url": "https://example.com",
    "license": "AGPL-3.0-or-later",
    "license_url": "https://www.gnu.org/licenses/agpl-3.0.html",
    "screenshot": "screenshot.png",
    "tags": ["modern", "responsive"],
    "requires": {
        "dixlase": ">=1.0.0",
        "php": ">=8.2"
    },
    "supports": {
        "dark_mode": true,
        "responsive": true,
        "front_page_builder": true,
        "custom_colors": true
    }
}
```

### 3. 依存関係のインストール（オプション）

```bash
cd themes/YourTheme

# npm依存関係
npm install

# Composer依存関係
composer install
```

### 4. 開発サーバーの起動

```bash
# Viteを使用する場合
npm run dev

# または本番ビルド
npm run build
```

---

## ファイル構成

### 必須ファイル

#### **theme.json**
テーマのメタデータを定義します。

#### **screenshot.png**
テーマ選択画面で表示されるプレビュー画像（推奨サイズ: 1200x900px）

#### **resources/views/layouts/app.blade.php**
基本的なHTMLレイアウト:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    
    @vite([
        'resources/src/front/scss/style.scss',
        'themes/YourTheme/resources/assets/css/style.css'
    ])
    @stack('styles')
</head>
<body>
    @include('partials.header')
    <main>@yield('content')</main>
    @include('partials.footer')
    
    @vite([
        'resources/src/front/js/scripts.js',
        'themes/YourTheme/resources/assets/js/app.js'
    ])
    @stack('scripts')
</body>
</html>
```

#### **resources/views/layouts/frontpage.blade.php**
フロントページビルダー用レイアウト:

```blade
@extends('layouts.app')

@section('content')
<div class="frontpage-builder">
    @yield('builder-content')
</div>
@endsection
```

---

## カスタマイズ方法

### カラーのカスタマイズ

`resources/assets/css/variables.css`:

```css
:root {
    --color-primary: #3b82f6;
    --color-secondary: #6b7280;
    --color-accent: #10b981;
}
```

### フォントのカスタマイズ

`config/theme.php`:

```php
'fonts' => [
    'body' => 'system-ui, sans-serif',
    'heading' => 'system-ui, sans-serif',
],
```

### 共通コンポーネントの使用

Dixlaseは共通コンポーネントを提供しています:

```blade
{{-- ナビゲーション --}}
<x-front.navigation :items="$menuItems" />

{{-- パンくず --}}
<x-front.breadcrumb :items="$breadcrumbs" />

{{-- ボタン --}}
<x-front.button variant="primary">クリック</x-front.button>

{{-- カード --}}
<x-front.card title="タイトル">
    コンテンツ
</x-front.card>
```

---

## ビュー優先順位

Dixlaseは以下の優先順位でビューを探索します:

### プラグインビュー
```
1. custom/plugins/{plugin}/resources/views/
2. themes/{active_theme}/resources/views/
3. plugins/{plugin}/resources/views/
4. resources/views/
```

### テーマビュー
```
1. custom/themes/{active_theme}/resources/views/
2. themes/{active_theme}/resources/views/
3. resources/views/
```

### 共通コンポーネント
```
1. custom/views/components/front/
2. themes/{active_theme}/resources/views/components/front/
3. resources/views/components/front/
```

---

## ベストプラクティス

### 1. CSS変数を活用する

テーマのカスタマイズ性を高めるため、CSS変数を使用:

```css
:root {
    --color-primary: #3b82f6;
}

.button {
    background-color: var(--color-primary);
}
```

### 2. レスポンシブデザイン

モバイルファーストで設計:

```css
/* モバイル */
.container {
    padding: 1rem;
}

/* タブレット以上 */
@media (min-width: 768px) {
    .container {
        padding: 2rem;
    }
}
```

### 3. ダークモード対応

```css
/* ライトモード */
:root {
    --color-bg: #ffffff;
    --color-text: #111827;
}

/* ダークモード */
.dark {
    --color-bg: #111827;
    --color-text: #f9fafb;
}
```

### 4. アクセシビリティ

- セマンティックHTMLを使用
- 適切なARIA属性を追加
- キーボード操作に対応
- 十分なコントラスト比を確保

### 5. パフォーマンス

- 画像の最適化
- CSS/JSの最小化
- 遅延読み込みの活用
- 不要なリソースの削除

---

## DixlaseDefaultThemeを参考にする

デフォルトテーマは完全な実装例です:

```bash
# デフォルトテーマのコードを参照
themes/DixlaseDefaultTheme/
```

主な参考ポイント:
- ✅ 完全なファイル構成
- ✅ CSS変数の活用例
- ✅ 共通コンポーネントの使用例
- ✅ ダークモード実装
- ✅ レスポンシブデザイン
- ✅ 多言語対応

---

## トラブルシューティング

### テーマが表示されない

1. `theme.json`が正しく設定されているか確認
2. 必須ファイルが存在するか確認
3. ファイルパーミッションを確認

### スタイルが反映されない

1. Viteビルドが完了しているか確認
2. ブラウザキャッシュをクリア
3. `@vite()`ディレクティブが正しいか確認

### コンポーネントが見つからない

1. ビュー優先順位を確認
2. 名前空間が正しいか確認
3. ファイルパスが正しいか確認

---

## 参考リンク

- [Dixlase公式ドキュメント](https://exc-d.com/docs)
- [Laravel Bladeドキュメント](https://laravel.com/docs/blade)
- [Viteドキュメント](https://vitejs.dev/)

---

Copyright (C) 2025 exc-D inc.
