# テーマプレビュー機能の共通化プラン

> テーマ設定プレビュー開発セッションからの引き継ぎプランです。
> 各Phase実装完了後にこのファイルは更新してください。

## 目的

1. テーマプレビューのUI部品をコアのBladeコンポーネント+JSモジュールとして抽出
2. 新しいテーマでもプレビュー機能を簡単に利用できるようにする
3. フロントページ編集・ページプラグイン・リーガルプラグインでも、テーマデザインの上にコンテンツを載せたプレビューができるようにする

## 現状の構造（DixlaseOnePage テーマ）

### プレビューコンテナ
- `#preview-outer` — 外側コンテナ（overflow-hidden, border, shadow）
- `#preview-inner` — 内側（transform scale, data-preview-theme属性）
- スケーリング: `containerWidth / deviceWidth`（デスクトップは上限なし、モバイル/タブレットは1.0上限）
- 幅変化検知: 200ms setIntervalポーリング（幅+高さ）

### デバイス切替
- モバイル(375px) / タブレット(768px) / デスクトップ(1440px)
- Alpine.jsの`previewDevice`/`previewDeviceWidth`で管理

### 外観モード切替
- `data-preview-theme="light|dark"` で管理画面ダークモードから完全隔離
- `@appearance-changed.window` カスタムイベントで切替
- `!important` 付きCSS（管理画面のTailwind dark:を上書き）

### 右カラム（サイドバー）
- Fixed位置（top-12, w-80, z-50）
- トグルボタンでスライドイン/アウト
- アコーディオンセクション（x-data="{ open: false }" + x-collapse）

### プラグイン連携
- MenuProviderInterface → メニュー選択（ヘッダー/フッター）
- PreviewProviderInterface → お問い合わせフォームプレビュー
- 全てContract+DTO経由（プラグイン直接参照なし）

### メディアピッカー連携
- `media-selected` カスタムイベントでURL取得
- `change` イベントで削除検知
- プレビューにロゴ/背景画像を即反映

---

## Phase 1: コアBladeコンポーネント抽出

### 1A: プレビューコンテナコンポーネント

**作成先**: `resources/views/components/admin/preview-container.blade.php`

**機能:**
- スケーリング＋リサイズポーリング（JSモジュール）
- デバイス切替ボタン
- 外観モード対応（data-preview-theme）
- スロットでプレビューコンテンツを注入

**使用例:**
```blade
<x-admin.preview-container
    :appearanceMode="$settings->appearance_mode ?? '0'"
    defaultDevice="desktop"
>
    {{-- テーマ固有のプレビューコンテンツ --}}
    <x-slot:header>
        <header>...</header>
    </x-slot:header>

    <x-slot:content>
        {!! $frontContentPreview !!}
    </x-slot:content>

    <x-slot:footer>
        <footer>...</footer>
    </x-slot:footer>
</x-admin.preview-container>
```

**抽出するJS（別ファイル化）:**
```
resources/src/admin/js/preview-container.js
- updatePreviewScale()
- setPreviewDevice()
- ポーリングロジック
- media-selected イベント連携
```

### 1B: プレビューサイドバーコンポーネント

**作成先**: `resources/views/components/admin/preview-sidebar.blade.php`

**機能:**
- トグルボタン（スライドイン/アウト）
- スロットでサイドバーコンテンツを注入
- 管理画面レイアウトのright-sidebar-active連携

**使用例:**
```blade
<x-admin.preview-sidebar>
    <x-admin.preview-sidebar-section title="外観モード" icon="fas fa-palette" :open="true">
        <select ...>...</select>
    </x-admin.preview-sidebar-section>

    <x-admin.preview-sidebar-section title="ヘッダーロゴ" icon="fas fa-heading">
        <x-media.picker name="header_logo_id" ... />
    </x-admin.preview-sidebar-section>
</x-admin.preview-sidebar>
```

### 1C: サイドバーセクションコンポーネント

**作成先**: `resources/views/components/admin/preview-sidebar-section.blade.php`

**機能:**
- アコーディオン開閉（x-collapse）
- アイコン + タイトル + シェブロン
- スロットで内容を注入

### 1D: プレビューCSS分離コンポーネント

**作成先**: `resources/views/components/admin/preview-theme-styles.blade.php`

**機能:**
- data-preview-theme ベースのCSS変数定義
- テーマがカスタムカラーパレットを上書き可能
- 管理画面スタイルのリセット（section border/bg透明化）

---

## Phase 2: テーマシェルContract

### 2A: ThemePreviewShellInterface

**作成先**: `app/Contracts/Theme/ThemePreviewShellInterface.php`

テーマがプレビュー用の「シェル」（ヘッダー/フッター構造）を提供するContract。
コンテンツ編集画面がこのシェル内にコンテンツを注入してプレビュー表示できる。

```php
interface ThemePreviewShellInterface
{
    /**
     * プレビューシェルのBladeビュー名を返す
     * 例: 'themes::admin.preview-shell'
     */
    public function getPreviewShellView(): string;

    /**
     * プレビューシェルに必要なデータを返す
     * （ヘッダーナビ、フッター情報、テーマ設定等）
     */
    public function getPreviewShellData(): array;

    /**
     * テーマ固有のCSSパレット（data-preview-theme用）
     */
    public function getPreviewColorPalette(): array;
}
```

### 2B: テーマ側の実装

**DixlaseOnePageの場合:**

```php
// app/Services/DixlaseOnePagePreviewShell.php
class DixlaseOnePagePreviewShell implements ThemePreviewShellInterface
{
    public function getPreviewShellView(): string
    {
        return 'themes::admin.preview-shell';
    }

    public function getPreviewShellData(): array
    {
        // テーマ設定、メニュー、ロゴ等を返す
        return [
            'settings' => ThemeSetting::getValues([...]),
            'headerLogo' => ...,
            'navigationItems' => ...,
        ];
    }

    public function getPreviewColorPalette(): array
    {
        return [
            'dark' => [
                'background' => '#030712',
                'header' => 'rgba(17,24,39,0.8)',
                // ...
            ],
            'light' => [
                'background' => '#fff',
                'header' => 'rgba(255,255,255,0.9)',
                // ...
            ],
        ];
    }
}
```

### 2C: プレビューシェルBlade

テーマがプレビューシェル用の専用Bladeビューを提供:

```blade
{{-- themes/DixlaseOnePage/resources/views/admin/preview-shell.blade.php --}}
<header class="pv-header ...">
    {{-- ロゴ + ナビ --}}
</header>

{{ $slot }} {{-- ← ここにコンテンツが注入される --}}

<footer class="pv-footer ...">
    {{-- フッター --}}
</footer>
```

---

## Phase 3: 各編集画面への統合

### 3A: フロントページ編集のプレビュー

**対象**: `resources/views/admin/front/edit.blade.php`

現在の右カラムにプレビュータブを追加:
- 編集中のコンテンツをリアルタイムまたは保存後にテーマシェル内でプレビュー
- `ThemePreviewShellInterface` 経由でアクティブテーマのシェルを取得
- コンテンツをシェルの `$slot` に注入

### 3B: ページプラグインのプレビュー

**対象**: `plugins/DixlasePages/resources/views/admin/pages/edit.blade.php`

ページ編集画面にプレビューボタンを追加:
- クリックでモーダルまたはサイドパネルにプレビュー表示
- テーマシェル内にページコンテンツを注入
- ページ固有のレイアウト（タイトル、パンくずリスト等）も含む

### 3C: リーガルプラグインのプレビュー

**対象**: `plugins/DixlaseLegal/`（利用規約・プライバシーポリシー等）

リーガルページ編集画面にプレビュー機能を追加:
- テーマシェル内にリーガルコンテンツを注入

---

## 実装優先順位

| 優先度 | Phase | タスク | 規模 |
|--------|-------|--------|------|
| 1 | 1A | プレビューコンテナコンポーネント | 中 |
| 2 | 1B+1C | サイドバー+セクションコンポーネント | 中 |
| 3 | 1D | CSS分離コンポーネント | 小 |
| 4 | - | DixlaseOnePageを新コンポーネントに移行 | 中 |
| 5 | 2A | ThemePreviewShellInterface | 小 |
| 6 | 2B+2C | DixlaseOnePageシェル実装 | 中 |
| 7 | 3A | フロントページ編集プレビュー | 中 |
| 8 | 3B | ページプラグインプレビュー | 中 |
| 9 | 3C | リーガルプラグインプレビュー | 小 |

## 注意事項

- Phase 1 はDixlaseOnePageの動作を壊さずにリファクタリングする
- Phase 2 のContractはコアに作成（テーマから直接参照するため）
- Phase 3 は各プラグインのセッションで進めるのが適切
- JSモジュールはViteでバンドルし`npm run build`が必要
- CSPの制約あり — インラインイベントハンドラー不可、Alpine.jsの`@click`等を使用
