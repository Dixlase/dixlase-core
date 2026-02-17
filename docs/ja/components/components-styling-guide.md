# Dixlase コンポーネントスタイルガイド

このガイドでは、Dixlaseのコンポーネントスタイルシステムの使用方法について説明します。

## 概要

Dixlaseでは、管理画面とプラグインで共有できるコンポーネントスタイルを提供しています。これらのスタイルは`resources/src/common/scss/_components.scss`に定義されており、プラグイン開発者も利用できます。

## ファイル構成

```
resources/
├── src/
│   ├── common/scss/
│   │   ├── _components.scss    # 共有コンポーネントスタイル
│   │   ├── _tailwind-custom.scss
│   │   └── style.scss          # メインスタイルファイル
│   └── admin/scss/
│       └── _admin.scss         # 管理画面専用スタイル
└── views/
    └── components/             # Bladeコンポーネント
```

## 利用可能なコンポーネントスタイル

### 1. ナビゲーションボタン

```html
<!-- 基本的なナビゲーションボタン -->
<button class="nav-button">基本ボタン</button>

<!-- アクティブ状態のボタン -->
<button class="nav-button nav-button--blue nav-button--active">アクティブ（青）</button>
<button class="nav-button nav-button--green nav-button--active">アクティブ（緑）</button>
<button class="nav-button nav-button--red nav-button--active">アクティブ（赤）</button>
<button class="nav-button nav-button--yellow nav-button--active">アクティブ（黄）</button>
```

### 2. アクションボタン

```html
<button class="action-button action-button--primary">プライマリ</button>
<button class="action-button action-button--success">成功</button>
<button class="action-button action-button--danger">危険</button>
<button class="action-button action-button--warning">警告</button>
```

### 3. ページネーション

```html
<div class="pagination">
    <button class="pagination-button pagination-button--disabled">前へ</button>
    <span class="pagination-number pagination-number--current">1</span>
    <span class="pagination-number">2</span>
    <span class="pagination-ellipsis">...</span>
    <span class="pagination-number">10</span>
    <button class="pagination-button">次へ</button>
</div>
<div class="pagination-info">全100件中 1-10件目</div>
```

### 4. モーダル

```html
<div class="modal modal--animate modal--visible">
    <div class="modal-overlay">
        <div class="modal-container">
            <div class="modal-content">
                <div class="modal-icon modal-icon--warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="modal-title">確認</h3>
                <div class="modal-message">
                    <p>この操作を実行しますか？</p>
                </div>
                <div class="modal-actions">
                    <button class="modal-button modal-button--confirm red">実行</button>
                    <button class="modal-button modal-button--cancel">キャンセル</button>
                </div>
            </div>
        </div>
    </div>
</div>
```

### 5. メッセージ

```html
<div class="message success">
    <p>操作が正常に完了しました。</p>
</div>

<div class="message warning">
    <p>注意が必要です。</p>
</div>

<div class="message error">
    <p>エラーが発生しました。</p>
</div>

<div class="message info">
    <p>情報をお知らせします。</p>
</div>
```

### 6. テーブル

```html
<table class="component-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>名前</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1</td>
            <td>サンプル</td>
            <td>
                <button class="action-button action-button--primary">編集</button>
            </td>
        </tr>
    </tbody>
</table>
```

### 7. レスポンシブテーブル

```html
<div class="responsive-table">
    <table class="component-table">
        <!-- テーブル内容 -->
    </table>
</div>
```

## プラグインでの使用方法

### 1. スタイルのインポート

プラグインのSCSSファイルで共有スタイルをインポートします：

```scss
// プラグインのSCSSファイル
@use '../../../common/scss/components';

.your-plugin-styles {
    // プラグイン固有のスタイル
}
```

### 2. Bladeテンプレートでの使用

```blade
{{-- プラグインのBladeテンプレート --}}
<div class="your-plugin-container">
    <button class="action-button action-button--primary">
        プライマリボタン
    </button>

    <div class="message success">
        <p>成功メッセージ</p>
    </div>
</div>
```

### 3. カスタムスタイルの追加

プラグイン固有のスタイルは、共有スタイルを拡張して作成できます：

```scss
// プラグイン固有のボタンスタイル
.your-plugin-button {
    @extend .action-button;
    @extend .action-button--primary;

    // 追加のカスタマイズ
    border-radius: 8px;
    font-weight: bold;
}
```

## ダークモード対応

すべてのコンポーネントスタイルはダークモードに対応しています。Tailwind CSSの`dark:`プレフィックスを使用して自動的に切り替わります。

## ベストプラクティス

1. **既存のコンポーネントスタイルを優先使用**
   - 新しいスタイルを作成する前に、既存のコンポーネントスタイルが使用できないか確認してください

2. **一貫性の維持**
   - 色、サイズ、間隔などは既存のデザインシステムに合わせてください

3. **レスポンシブ対応**
   - モバイルファーストでデザインし、適切なブレークポイントを使用してください

4. **アクセシビリティ**
   - 適切なコントラスト比、フォーカス状態、キーボードナビゲーションを考慮してください

## カスタマイズ

プラグイン開発者は、共有スタイルをベースにして独自のバリエーションを作成できます：

```scss
// カスタムアクションボタン
.custom-action-button {
    @extend .action-button;

    &--custom-color {
        @apply bg-purple-600 hover:bg-purple-700 text-white;
    }
}
```

## サポート

スタイルに関する質問や提案がある場合は、開発チームまでお問い合わせください。

---

このガイドは、Dixlaseのコンポーネントスタイルシステムを効果的に活用するためのリファレンスです。定期的に更新されるため、最新の情報を確認してください。
