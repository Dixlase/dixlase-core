# 保存ボタンとモーダルの使用方法

DixlaseのCSP厳格モード対応に伴い、保存ボタンとモーダルのコンポーネントを3種類用意しています。

## コンポーネント一覧

### 1. Alpine.js版（従来版）
- **コンポーネント**: `<x-admin.save-button>`
- **モーダル**: `<x-ui-modal>`
- **用途**: Alpine.jsを使用している既存ページ
- **CSP**: 開発モード・標準モード

### 2. Vanilla JS版（CSP厳格モード対応）
- **コンポーネント**: `<x-admin.save-button-vanilla>`
- **モーダル**: `<x-ui-modal-vanilla>`
- **用途**: CSP厳格モードに対応したページ
- **CSP**: 厳格モード対応

## 使用方法

### Alpine.js版（従来版）

```blade
@extends('layouts.admin')

@section('content')
<div>
    <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <!-- フォームフィールド -->
    </form>
</div>
@endsection

@section('save')
    <x-admin.save-button
        id_confirmation="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirm_label="__('common.save')"
        :cancel_label="__('common.cancel')"
        form="settings-form"
    />
@endsection
```

### Vanilla JS版（推奨）

```blade
@extends('layouts.admin')

@section('content')
<div>
    <form id="settings-form" method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <!-- フォームフィールド -->
    </form>
</div>
@endsection

@section('save')
    <x-admin.save-button-vanilla
        modalId="confirmationModal"
        :label="__('common.save')"
        :title="__('common.save_confirmation_title')"
        :message="__('common.save_confirmation_message')"
        :confirmLabel="__('common.save')"
        :cancelLabel="__('common.cancel')"
        form="settings-form"
    />
@endsection
```

## パラメータ一覧

### 共通パラメータ

| パラメータ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `label` | string | `__('common.save')` | ボタンのラベル |
| `title` | string | `__('common.save_confirmation_title')` | モーダルのタイトル |
| `message` | string | `__('common.save_confirmation_message')` | モーダルのメッセージ |
| `confirmLabel` | string | `__('common.save')` | 確認ボタンのラベル |
| `cancelLabel` | string | `__('common.cancel')` | キャンセルボタンのラベル |
| `backUrl` | string | null | 戻るボタンのURL |
| `backLabel` | string | `__('common.back')` | 戻るボタンのラベル |
| `iconType` | string | `'info'` | アイコンタイプ（info, warning, danger, success） |
| `confirmColor` | string | `'blue'` | 確認ボタンの色（blue, red, green, yellow） |

### Alpine.js版 / Vanilla JS版 固有パラメータ

| パラメータ | 型 | デフォルト | 説明 |
|-----------|-----|-----------|------|
| `form` | string | null | 送信するフォームのID |
| `modalId` | string | `'confirmationModal'` | モーダルのID（Vanilla版） |
| `id_confirmation` | string | `'confirmationModal'` | モーダルのID（Alpine版） |

## レイアウトへの配置

すべてのバージョンで同じように `@section('save')` を使用して配置できます。

```blade
@section('save')
    <!-- Alpine.js版 -->
    <x-admin.save-button ... />
    
    <!-- Vanilla JS版 -->
    <x-admin.save-button-vanilla ... />
    
@endsection
```

レイアウトファイル（`layouts/admin.blade.php`）では以下のように配置されています：

```blade
@hasSection('save')
    <div class="sticky bottom-0 z-30 backdrop-blur-sm bg-white/75 dark:bg-gray-900/75 border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 lg:px-8 py-3">
        <div class="w-full mx-auto flex justify-center">
            @yield('save')
        </div>
    </div>
@endif
```

## 後方互換性

すべてのコンポーネントは後方互換性を保つため、スネークケース（`confirm_label`）とキャメルケース（`confirmLabel`）の両方をサポートしています。

```blade
<!-- どちらも動作します -->
<x-admin.save-button-vanilla
    :confirm_label="__('common.save')"  {{-- スネークケース --}}
/>

<x-admin.save-button-vanilla
    :confirmLabel="__('common.save')"   {{-- キャメルケース（推奨） --}}
/>
```

## 移行ガイド

### Alpine.js版からVanilla JS版への移行

1. コンポーネント名を変更
2. パラメータ名を統一（推奨）

```diff
- <x-admin.save-button
+ <x-admin.save-button-vanilla
-     id_confirmation="confirmationModal"
+     modalId="confirmationModal"
-     :confirm_label="__('common.save')"
+     :confirmLabel="__('common.save')"
-     :cancel_label="__('common.cancel')"
+     :cancelLabel="__('common.cancel')"
      form="settings-form"
  />
```

## トラブルシューティング

### モーダルが開かない（Vanilla JS版）

`window.modalManager` が初期化されているか確認してください。

```javascript
console.log(window.modalManager); // ModalManager インスタンスが表示されるはず
```

### フォームが送信されない

`form` パラメータにフォームのIDが正しく設定されているか確認してください。

```blade
<form id="my-form" method="POST" action="...">
    ...
</form>

<x-admin.save-button-vanilla
    form="my-form"  {{-- フォームIDと一致させる --}}
/>
```

## まとめ

- **新規開発**: Vanilla JS版を使用
- **既存ページ**: Alpine.js版を継続使用可能
- **CSP厳格モード**: Vanilla JS版が必須
- **使い方**: すべてのバージョンで `@section('save')` に配置するだけ
