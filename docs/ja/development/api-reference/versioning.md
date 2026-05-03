# Dixlase REST API バージョニング仕様

## 概要

このドキュメントは Dixlase REST API のバージョニング契約・レスポンス形式・認証モデル・マルチサイト/i18n の境界を定義します。コア開発者およびプラグイン作者にとっての規範となるリファレンスです。

API は機能ではなくアーキテクチャ層です。AI-operable ワークフロー、統合ハブ、ヘッドレス利用、モバイルアプリ、外部連携のすべてが API に依存します。ここでの決定はリリース後に変更しづらいため、意図的に保守的な選択をしています。

## バージョン

- **仕様バージョン**: 1.0
- **ステータス**: 契約は確定、v0.1.0 で部分実装
- **実装済みエンドポイント**: `GET /api/v1/health`
- **予約名前空間**: 第 2 章を参照

## 1. URL バージョニング

### 1.1 戦略

すべての API エンドポイントはバージョン付き URL prefix の配下に配置します:

```
/api/v1/...
```

将来の `v2` API は `/api/v2/...` の配下に新設し、`v1` は最低 1 メジャーバージョンサイクル分は維持します。

これは WordPress REST API (`/wp-json/wp/v2/`)、GitHub API (`/v3/` 後にヘッダ方式へ)、Stripe API (`/v1/`) と同方針です。`Accept: application/vnd.dixlase.v1+json` のようなヘッダ方式と比較し、**URL の明示性、デバッグ容易性、キャッシュフレンドリ性** を優先しています。

### 1.2 i18n の境界

`/api/*` はフロントサイトで使用している path-prefix 多言語ルーティング (`/{locale}/...`) の対象外です。API は言語非依存の制御面です。

- `routes/front.php` の `Route::fallback()` は `/api/*` を明示的にバイパスするため、未定義 API URL が `/{locale}/api/...` に 302 リダイレクトされることはありません。
- `SetFrontLocale` および `SetAdminLocale` ミドルウェアは API リクエストには走りません。
- API のエラーメッセージは常に英語です（第 5 章を参照）。

将来、ローカライズされたコンテンツを返す必要があるエンドポイント（特定ロケール向けに描画した公開ページ等）は、**エンドポイント側の責任** として `?locale=` クエリパラメータまたは `Accept-Language` ヘッダを受け取って解決します。URL prefix にロケールを埋め込むことはありません。

## 2. URL 名前空間の予約

`/api/v1/` 配下の以下の名前空間は予約されています。プラグインは予約パスと衝突するルートを登録してはいけません。

| Path | 状態 | オーナー | 備考 |
|---|---|---|---|
| `/api/v1/health` | 実装済 | Core | 認証不要、サイト・バージョン情報を返す |
| `/api/v1/resources/{type}/{slug}` | 予約 | 将来の DixlaseApi プラグイン | `ApiResourceProviderInterface` を自動発見 |
| `/api/v1/privacy/...` | 予約 | 将来の Privacy API | `PrivacyDataProviderInterface` 集約に基づく |
| `/api/v1/{plugin-slug}/...` | プラグイン公開枠 | プラグイン作者 | `routes/api/v1.php` で登録 |

`{plugin-slug}` は `plugin.json` で宣言されたスラッグ (kebab-case) に対応します。予約済みのトップレベルセグメント (`health`, `resources`, `privacy`) はプラグインスラッグとして使えません。

## 3. レスポンスエンベロープ

すべての API レスポンスは Laravel API Resource を基底にした統一 JSON エンベロープを使います。基底クラスは `App\Http\Resources\BaseApiResource`（コレクションは `BaseApiCollection`）です。

### 3.1 単一リソース

```json
{
  "data": {
    "id": "01J9...",
    "type": "page",
    "attributes": { "title": "Hello" }
  },
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z"
  },
  "links": {
    "self": "https://example.com/api/v1/pages/01J9..."
  }
}
```

### 3.2 コレクション

```json
{
  "data": [ ... ],
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z",
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 130,
      "last_page": 7
    }
  },
  "links": {
    "self": "https://example.com/api/v1/pages?page=1",
    "next": "https://example.com/api/v1/pages?page=2",
    "prev": null
  }
}
```

### 3.3 エラー

```json
{
  "error": {
    "code": "validation_failed",
    "message": "The given data was invalid.",
    "details": {
      "title": ["The title field is required."]
    }
  },
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z"
  }
}
```

`code` は安定したマシン可読の snake_case 識別子です。`message` は人間可読の英語文です。`details` は任意で、エラー種別ごとに形が違います（バリデーションエラーはフィールドキーの配列、その他のエラーでは省略可）。

### 3.4 命名上の区別

`App\DTO\Api\ApiResourceDTO` および `ApiResourceCollection` (`app/DTO/Api/` 配下) は `BaseApiResource` とは **別レイヤー** です。前者はプラグインが `ApiResourceProviderInterface` を実装してコアにデータを渡すためのコンテンツ抽象化です。後者は出力 JSON を整形する Laravel HTTP Resource の基底です。混同しないでください。

## 4. HTTP ステータスコード

| Code | 意味 | 用途 |
|---|---|---|
| 200 | OK | 読み取り成功 |
| 201 | Created | リソース作成 |
| 204 | No Content | 削除成功 |
| 400 | Bad Request | 不正な JSON、解析不能なボディ |
| 401 | Unauthorized | 認証情報なし、または無効 |
| 403 | Forbidden | 認証は通ったが許可されていない（scope、IP、サイト不一致） |
| 404 | Not Found | リソースまたはルートが存在しない |
| 405 | Method Not Allowed | HTTP メソッドが不正 |
| 422 | Unprocessable Entity | バリデーションエラー |
| 429 | Too Many Requests | レートリミット超過 |
| 500 | Internal Server Error | サーバ側の未処理失敗 |

API クライアントへ 3xx リダイレクトを返してはいけません。`routes/front.php` の locale fallback は `/api/*` を明示的にバイパスします。

## 5. 多言語化ポリシー

### 5.1 エラーメッセージは英語固定

API エラーレスポンスの `message` フィールドは常に英語です。`Accept-Language` ヘッダはシステム生成メッセージには影響しません。ローカライズされた人間可読文字列が必要なクライアントは、安定した `code` をクライアント側の翻訳テーブルにマップしてください。

これは GitHub、Stripe、その他多くの公開 REST API の慣例と一致します。以下を回避するためです:
- ロケール間でドリフトするエラーカタログ
- テストの脆弱化（スナップショットテスト、契約テスト）
- サーバがロケールを解決できないエッジケース（内部スケジュールジョブ等）

### 5.2 ローカライズされたコンテンツ

ユーザー向けコンテンツ（ページ、記事等）を返すエンドポイントは、**エンドポイント側の責任** として以下を受け付けます:
- 明示的な `?locale=en` / `?locale=ja` クエリパラメータ、または
- `Accept-Language` ヘッダ（フォールバックルールはドキュメント化必須）

URL prefix にロケールを含めることはありません。`/api/v1/pages/about?locale=ja` は正、`/api/v1/ja/pages/about` は誤です。

## 6. 認証

### 6.1 v0.1: API キー (Bearer token)

```
Authorization: Bearer dxl_live_AbCdEf...
```

キーは sha256 でハッシュ化して保存し、scope リスト、任意の IP 許可リスト、任意の有効期限、環境タグ (`live` / `test`)、キー単位のレートリミットオーバーライドを保持します。

### 6.2 v0.2 以降: Sanctum

Laravel Sanctum を Personal Access Token と SPA cookie 認証として導入予定です。依存追加には明示的な承認が必要というプロジェクトルールがあるため、v0.1.0 では同梱しません。

### 6.3 マルチサイトのキーバインド

すべての API キーは `site_id` を持ちます:

| `site_id` | 種別 | 通用範囲 | 生成方法 |
|---|---|---|---|
| `1`, `2`, ... (非 null) | サイトキー | バインド先のサイトのみ | そのサイトの Admin UI（既定）|
| `null` | ネットワークキー | 全サイト | CLI 限定: `dls:api:create-network-key` |

ネットワークキーは強力で危険です。次の制約があります:
- どの Web UI からも作成できません
- CLI フローで明示的な確認プロンプトを要求します
- 使用時は `audit_logs` に記録されます (`network_api_key_used` イベント)
- 必要最小の scope のみを持たせてください

サイトキーは `BelongsToSite` トレイトのグローバルスコープ経由で検証されます: サイト A に解決されたリクエストでサイト B 用のキーを使うことはできません。

## 7. マルチサイト境界

`ResolveSiteContext` ミドルウェアはグローバルスタックで動作し、API 認証より **前** に現在のサイトを解決します。

- v0.1.0: 常に primary site (`is_primary=true`) を選択。
- v2 以降: hostname-based / path-prefix-based 解決を API 呼び出し側を変えずに導入。

API クライアントは URL にサイトを含めません。API はリクエストのホスト名（および将来はヘッダ）から解決します。`SiteContext::currentSiteId()` がセットされたあとは、`BelongsToSite` を使うすべての Eloquent クエリが自動的にフィルタされます。

## 8. レートリミット

- 既定のグローバル上限: `api_rate_limit` 設定（`ApiSettingDefinitions` で `Global` スコープ登録）
- キー単位のオーバーライド: `api_keys.rate_limit` カラム
- すべての API レスポンスに付与するヘッダ:
  - `X-RateLimit-Limit`
  - `X-RateLimit-Remaining`
  - `X-RateLimit-Reset` (Unix タイムスタンプ)
- 429 レスポンスには `Retry-After` を秒単位で含めます

将来のリビジョンで `api_rate_limit` を `Overridable` スコープに昇格させ、サイト管理者が値を上書きできるようにする可能性があります。

## 9. プラグイン API ルート規約

API エンドポイントを公開するプラグインは次の場所にルートを配置します:

```
plugins/{Name}/routes/api/v1.php
```

コアの自動ローダはファイルの内容を次のように包みます:

```php
Route::prefix('api/v1')
    ->middleware(\App\Http\Middleware\EnsurePluginActiveOnSite::class.':'.$slug)
    ->group(function () {
        require $apiV1RoutePath;
    });
```

そのため、プラグイン作者はスラッグ配下の部分のみを書きます:

```php
// plugins/DixlaseAuthority/routes/api/v1.php
use Illuminate\Support\Facades\Route;
use Plugins\DixlaseAuthority\App\Http\Controllers\Api\PublicKeyController;

Route::prefix('authority')
    ->middleware('plugin.api.public')
    ->name('dixlase-authority::api.v1.')
    ->group(function () {
        Route::get('/keys', [PublicKeyController::class, 'index'])->name('keys.index');
    });
```

結果: `GET /api/v1/authority/keys`。

プラグインが解決済みサイト上で有効化されていない場合、`EnsurePluginActiveOnSite` がコントローラ実行前に 404 JSON を返します。

旧来の `routes/api.php`（`v1.php` ファイル名なし）に置く方式は後方互換のため引き続きサポートしますが **deprecated** 扱いです。自動ローダはロード時にログ警告を出します。

## 10. 廃止 (Deprecation) ポリシー

- メジャー API バージョンは、次のメジャーが出てから少なくとも 1 メジャーサイクル分維持します（例: v1 は v3 が出るまで維持）。
- 安定バージョン内の個別エンドポイントの廃止は最低 6 ヶ月の事前告知を行います:
  - リリースノート
  - `Sunset: <RFC 8594 date>` レスポンスヘッダ
  - `Deprecation: true` レスポンスヘッダ
- 綺麗に deprecate できない破壊的変更は、暗黙の変更ではなくメジャーバージョンを上げて行います。

## 11. ヘルスチェック

v0.1.0 で実装する唯一のエンドポイントは、上述の契約の規範例です:

```
GET /api/v1/health
```

認証不要。アプリケーションが本当にブートストラップできない場合を除き、常に 200 を返します。

```json
{
  "data": {
    "status": "ok",
    "version": "0.1.0"
  },
  "meta": {
    "site_id": 1,
    "timestamp": "2026-05-03T12:00:00Z"
  }
}
```

`meta.site_id` を含めることで、v2 以降のマルチサイトデプロイにおいてリクエストがどのサイトに解決されたかをオペレータが確認できます。サイトの `slug`、`host` などの識別情報は、この公開エンドポイントでは意図的に **出しません**。

## 12. リファレンス

### コード
- `routes/api.php` — v1 ルートグループのエントリポイント
- `bootstrap/app.php` — `withRouting(api: ...)` と `withExceptions(...)` の設定
- `app/Http/Resources/BaseApiResource.php` — レスポンスエンベロープ基底クラス
- `app/Http/Resources/BaseApiCollection.php` — コレクションレスポンス基底クラス
- `app/Http/Controllers/Api/V1/HealthController.php` — ヘルスチェック
- `app/Http/Middleware/AuthenticateApiKey.php` — Bearer トークン認証
- `app/Http/Middleware/EnsurePluginActiveOnSite.php` — プラグイン有効化ゲート
- `app/Models/ApiKey.php` — `BelongsToSite` トレイト適用済みの API キーモデル
- `app/Console/Commands/Api/CreateNetworkApiKeyCommand.php` — ネットワークキー生成 CLI

### ドキュメント
- [マルチサイトアーキテクチャ](../multisite.md)
- [URL 多言語化の決定](../i18n-url-localization.md)
- [Events API](events.md)
- [Webhooks](webhooks.md)
