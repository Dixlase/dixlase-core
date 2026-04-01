# フロントページコンテンツのテーマプレビュー反映

> テーマ設定プレビュー開発セッションからの引き継ぎプランです。
> 実装完了後にこのファイルは削除してください。

## 目的

テーマ設定画面のプレビューに、フロントページ編集で作成したコンテンツを
ヒーローエリアとフッター（またはお問い合わせフォーム）の間に表示する。

## 現状の構造

### フロントページコンテンツの保存先
- **テーブル**: `dls_front_pages`（`page_type='main_content'`）
- **モデル**: `App\Models\FrontPage`
- **エディタ種別**: GUI(JSON), Markdown, HTML, Blade の4種
- **保存先**: DB (`content`カラム) または ファイル (`storage/app/private/core/front/`)

### コンテンツ取得の流れ（現在の本番フロントページ）
```
FrontWelcomeController
  → FrontPage::findByTypeAndLang('main_content', locale)
  → FrontPageContentService::getContent()
  → エディタ種別に応じてHTML変換（GUI: EditorManager::renderContent(), MD: Str::markdown()）
  → shortcode_parse() で最終HTML
  → テーマの index.blade.php に $frontContent として渡す
```

### テーマの現在のレンダリング（index.blade.php）
```blade
@if(!empty($frontContent))
  <section class="front-content">
    @if($frontEditorType === 'blade')
      {!! Blade::render($frontContent) !!}
    @elseif($frontEditorType === 'markdown')
      {!! Str::markdown($frontContent) !!}
    @else
      {!! shortcode_parse($frontContent) !!}
    @endif
  </section>
@endif
```

## 設計方針

### コアの機能なのでContract+DTOではなく、サービス経由で取得

フロントページはプラグインではなくコアの機能のため、`PreviewProviderInterface`（プラグイン用）は不要。
代わりに既存の `FrontPageContentService` を直接使って、テーマ設定コントローラーから
コンテンツを取得し、プレビューに渡す。

### 実装アプローチ

```
AdminThemeSettingsController::settings()
  → FrontPage::findByTypeAndLang('main_content', locale)
  → FrontPageContentService::getContent()
  → エディタ種別に応じてHTML変換
  → $this->viewParams['frontContentPreview'] = $renderedHtml
  → preview.blade.php でヒーローとフッターの間に表示
```

## 実装ステップ

### Step 1: テーマコントローラーにコンテンツ取得ロジック追加

**ファイル**: `themes/DixlaseOnePage/app/Http/Controllers/Admin/Settings/AdminThemeSettingsController.php`

```php
// Front page content for preview
$frontContentPreview = null;
$frontPage = \App\Models\FrontPage::findByTypeAndLang('main_content', app()->getLocale());
if ($frontPage) {
    $contentService = app(\App\Services\FrontPageContentService::class);
    $rawContent = $contentService->getContent($frontPage, app()->getLocale());
    if ($rawContent) {
        $editorType = $frontPage->editor_type;
        if ($editorType === \App\Enums\ContentEditorType::GUI) {
            $renderedHtml = app(\App\Services\Editor\EditorManager::class)->renderContent($rawContent);
        } elseif ($editorType === \App\Enums\ContentEditorType::MARKDOWN) {
            $renderedHtml = \Illuminate\Support\Str::markdown($rawContent);
        } elseif ($editorType === \App\Enums\ContentEditorType::BLADE) {
            $renderedHtml = \Illuminate\Support\Facades\Blade::render($rawContent);
        } else {
            $renderedHtml = $rawContent;
        }
        $frontContentPreview = shortcode_parse($renderedHtml);
    }
}
$this->viewParams['frontContentPreview'] = $frontContentPreview;
```

### Step 2: プレビューにコンテンツセクション追加

**ファイル**: `themes/DixlaseOnePage/resources/views/admin/settings/themes/partials/preview.blade.php`

ヒーローセクションとお問い合わせフォームセクションの間に追加:

```blade
{{-- ===== FRONT PAGE CONTENT PREVIEW ===== --}}
@if(!empty($frontContentPreview))
<section class="pv-content py-12">
    <div class="container mx-auto px-8">
        <div class="prose max-w-none pv-content-text">
            {!! $frontContentPreview !!}
        </div>
    </div>
</section>
@endif
```

### Step 3: プレビューCSS追加

`pv-content` と `pv-content-text` のライト/ダークテーマスタイルを追加:

```css
#preview-inner[data-preview-theme="dark"] .pv-content { background: #111827 !important; }
#preview-inner[data-preview-theme="dark"] .pv-content-text { color: #d1d5db !important; }
#preview-inner[data-preview-theme="light"] .pv-content { background: #fff !important; }
#preview-inner[data-preview-theme="light"] .pv-content-text { color: #374151 !important; }
```

## 注意事項

1. **セキュリティ**: Bladeレンダリングは管理者が作成したコンテンツのみ。XSSリスクは管理者権限に限定
2. **パフォーマンス**: GUIエディタのJSON→HTMLレンダリングはプレビュー時にも実行される
3. **ショートコード**: `shortcode_parse()` がプラグインのショートコードを展開する。プラグイン未インストール時は未展開のまま表示
4. **リアルタイム更新**: フロントページコンテンツの編集はテーマ設定画面とは別ページのため、リアルタイムプレビューは不要。保存後のページリロードで反映
5. **カスタムJS/CSS**: プレビューではカスタムJS/CSSの実行は避ける（セキュリティとプレビュー表示の安定性のため）

## 関連ファイル

- `app/Models/FrontPage.php` — モデル
- `app/Services/FrontPageContentService.php` — コンテンツ取得サービス
- `app/Services/Editor/EditorManager.php` — GUIエディタのレンダリング
- `app/Enums/ContentEditorType.php` — エディタ種別
- `app/Http/Controllers/Front/FrontWelcomeController.php` — 本番フロントの参考実装
- `themes/DixlaseOnePage/resources/views/index.blade.php` — テーマのフロントページ参考
