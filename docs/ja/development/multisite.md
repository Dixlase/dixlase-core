# マルチサイトアーキテクチャ

Dixlase v0.1.0 は表面上シングルサイトとしてリリースされますが、データ層・サービス層全体は内部的にマルチサイト対応になっています。この文書は、プラグイン・テーマ作者が v0.1.0 (シングルサイト) と v2+ (複数サイト) の両方で動くコードを書けるよう、アーキテクチャを解説します。

## TL;DR

- すべての per-site DB テーブルに `site_id` カラムがある
- リクエストスコープの `SiteContext` がリクエスト開始時に現在のサイトを解決する
- `BelongsToSite` トレイトを使う Eloquent モデルは Global Scope で自動的に現在のサイトのみに絞り込まれる
- 設定は 3 つの scope に分かれる: `Global` / `PerSite` / `Overridable`。`SettingResolver` が登録された scope に応じて適切なテーブルを選ぶ
- ファイルストレージは `SiteStorage` 経由で、各サイトが `storage/app/private/sites/{site_id}/` 配下に独立した木を持つ

以下の規約に従ってプラグインを書けば、現在のシングルサイト環境でも、v2 のマルチサイト環境でも、変更なしで動作します。

---

## コンポーネント

### `App\Models\Site`

per-site レコードの本体。カラム: `id`, `slug`, `name`, `description`, `host`, `path_prefix`, `primary_locale`, `timezone`, `is_primary`, `is_active`, 標準の timestamps + soft delete。

v0.1.0 では行は 1 件のみ (`id=1`、slug `main`、`is_primary=true`)。

### `App\Contracts\Site\SiteContextInterface`

リクエストの「現在のサイト」を解決する。公開メソッド:

```php
public function currentSiteId(): int;
public function currentSite(): Site;
public function setCurrent(int $siteId): void;
public function isPluginActive(string $slug): bool;
public function isThemeActive(string $slug): bool;
public function connection(): \Illuminate\Database\ConnectionInterface;
```

シングルトンとしてバインド済み。`App\Http\Middleware\ResolveSiteContext` がリクエスト開始時に解決する。プラグインからは `App\Facades\SiteContext` ファサード経由か、interface を DI で注入して使う。

コアは、プラグインが足すすべてのルートを `App\Http\Middleware\EnsurePluginActiveOnSite` で、解決したサイトの `isPluginActive()` によって確かめる。`routes/api/v1.php` と非推奨の `routes/api.php` は 404 JSON を、`routes/web.php` はサイトの HTML の 404 ページを返す。全体では有効でも現在のサイトで有効でないプラグインは、そのサイトでフロントのルートを一切出さない(#494)。プライマリサイトの有効化の行は、`plugin:enable` か管理画面でプラグインを有効にしたときに書かれる(`Plugin` モデルの `saved()` フック)。

```php
use App\Facades\SiteContext;

$siteId = SiteContext::currentSiteId();
$site = SiteContext::currentSite();
```

### `App\Models\Traits\BelongsToSite`

`site_id` カラムを持つテーブルの Eloquent モデルに適用するトレイト。トレイトは:

1. `site()` `belongsTo` リレーションを追加
2. Global Scope を登録し、すべてのクエリを現在のサイトでフィルタ
3. 作成時に `site_id` を現在のサイトで自動セット

```php
use App\Models\Traits\BelongsToSite;

class Post extends Model
{
    use BelongsToSite;

    protected $fillable = ['site_id', 'title', 'content'];
}

// 以後:
Post::all();              // 現在のサイトの投稿のみ返す
Post::create([...]);      // 現在のサイトの site_id が自動付与される
Post::allSites()->get();  // スコープを外す（ネットワーク管理用）
Post::forSite(2)->get();  // 特定サイトを明示
```

### 設定: 3 分類モデル

すべての設定キーは以下 3 つの scope のいずれかとして登録される:

| Scope | 保存先 | 用途 |
|-------|--------|------|
| `Global` | `global_settings` のみ | ネットワーク全体ポリシー: ライセンスキー、デフォルトテーマ、セキュリティ方針 |
| `PerSite` | `site_settings` のみ (site_id でフィルタ) | サイト固有値: サイト名、メンテナンスメッセージ、OGP 画像 |
| `Overridable` | 両方: `site_settings` 行が `global_settings` 行を上書き | ネットワーク既定 + サイト個別上書き: SMTP、デフォルトロケール |

プラグインは自身の設定を `SettingDefinitionRegistry` 経由で登録する (通常はサービスプロバイダの `boot()` で):

```php
use App\Enums\SettingScope;
use App\Services\Site\SettingDefinition;
use App\Services\Site\SettingDefinitionRegistry;

public function boot(SettingDefinitionRegistry $registry): void
{
    $registry->register(new SettingDefinition(
        name: 'myplugin_api_endpoint',
        scope: SettingScope::Overridable,
        default: 'https://api.example.com',
        type: 'string',
    ));
}
```

その後 `SettingResolver` 経由で読み書き:

```php
use App\Services\Site\SettingResolver;

$resolver = app(SettingResolver::class);

// scope 対応の fallback で読み取り (site → global → default)
$endpoint = $resolver->get('myplugin_api_endpoint');

// グローバル (ネットワーク既定) に書き込み
$resolver->setGlobal('myplugin_api_endpoint', 'https://prod.api.example.com');

// 現在のサイト (per-site 上書き) に書き込み
$resolver->setForSite('myplugin_api_endpoint', 'https://eu.api.example.com', $siteId);
```

未登録キーは `UnknownSettingException` を発火する。先に登録、その後アクセス。

#### Strict モードポリシー（`^0.1` で凍結）

`SettingResolver::get()` は**設計として strict**: 未登録キーは
`UnknownSettingException` を投げ、`null` / `$default`
をサイレントに返さない。これは `^0.1` 内の Plugin API
契約の一部であり、パッチリリースで緩和されることはない。理由:

- **タイポを呼び出し位置で捕捉。** `site_namr`（`site_name` の typo）はリクエスト全体で
  古い `null` を返し続けるのではなく、即座に失敗する。
- **キー名の偶発的衝突を防ぐ。** プラグインがコアキーの近似名を誤って書き込むことは
  不可能。レジストリが所有を仲介する。
- **監査しやすい。** 正規の設定キー一覧はレジストリそのものであり、
  「`site_settings` にたまたま入っていた何か」ではない。
- **後で緩める方が、後で締めるよりも安全。** strict を前提とした書き方をする
  プラグイン作者は、後の lenient 化で壊れる側にしかならない。逆方向
  （lenient → strict）はデータをサイレントに腐らせる遷移なので、v0.1.0
  から strict をロックインする。

「設定が存在するか」を例外なしで確認したい場合は、
`SettingDefinitionRegistry::has($key)` を先に呼ぶこと:

```php
if (app(SettingDefinitionRegistry::class)->has($key)) {
    $value = $resolver->get($key);
}
```

プラグイン・テーマ作者は、読み書きする全ての設定をサービスプロバイダの
`boot()` で（値に触れる最初のリクエスト前に）必ず登録すること。

### ファイルストレージ

`App\Services\Site\SiteStorage` がマルチサイト対応の Filesystem インスタンスを返す:

```
storage/app/
├── public/
│   └── sites/{site_id}/      <- SiteStorage::public()
├── private/
│   ├── global/                <- SiteStorage::global()
│   └── sites/{site_id}/       <- SiteStorage::private()
└── temp/
```

```php
use App\Services\Site\SiteStorage;

SiteStorage::private()->put('reports/q4.csv', $csv);  // 現在のサイト
SiteStorage::global()->put('plugin-vault/foo.zip', $zip);  // ネットワーク全体
SiteStorage::public()->put('uploads/image.jpg', $bytes);  // 現在のサイトの公開ファイル
```

`Storage::disk('public')` や `storage_path('app/...')` を直接呼ぶ既存コードはそのまま動く。`SiteStorage` は新規 per-site コード向けの推奨 API。

---

## プラグイン・テーマ作者向けガイダンス

以下の規約に従えば、サイト数に依存しないコードが書ける:

1. **per-site モデルには `BelongsToSite` を適用** — トレイトが `site_id` 注入とスコープを自動処理。テーブルマイグレーションに `site_id` を追加し、モデルの `$fillable` にも含める
2. **設定は事前登録** — 未登録キーで `SiteSetting::getValue('foo')` を呼ばない。サービスプロバイダの `boot()` で全設定を定義し、scope を明示する
3. **設定の読み取りは適切な API で:**
   - プラグイン新規コード: `app(SettingResolver::class)->get('your_key')`
   - レガシー呼び出し: `SiteSetting::getValue('your_key')` (引き続き動作。内部で resolver 経由)
4. **新規ファイルパスは `SiteStorage` 経由** — `storage_path('app/private/myplugin/...')` のハードコードは避け、`SiteStorage::private()` を使う
5. **デフォルトでクロスサイトクエリを発行しない** — `BelongsToSite` を使えばスコープが自動でフィルタする。本当にクロスサイトが必要 (ネットワーク管理レポート等) なら `allSites()` や `forSite($id)` を明示的に呼ぶ
6. **v0.1.0 のシングルサイトを前提にしない** — 今は 1 サイトしかなくても、複数サイトを綺麗に扱えるコードを書く。土台の意義は v2 移行コストをゼロにすること

---

## DB 戦略

v0.1.0 は **共有 DB + `site_id` カラム** 方式 (論理分離)。全サイトが単一の MySQL DB に同居し、クエリは `site_id` でフィルタする。

Laravel の全機能 (リレーション、トランザクション、キュー、Eager Load) とそのまま共存する。トレードオフは論理分離のみで物理分離ではない点。

v2+ で追加予定のオプション: ハイブリッド方式。`Site.db_connection` を設定したサイトは専用 DB を使う。大半のサイトはデフォルト DB、コンプライアンス要件のあるテナントだけ物理分離。`SiteContext::connection()` メソッドは今のうちから存在しているので、移行は非破壊的。

詳細は `.backlog/multisite-database-strategy.md` 参照。

---

## v0.1.0 で出荷しないもの

以下は v0.1.0 では明示的に対象外で、v2+ で対応:

- ネットワーク管理 UI (サイト一覧 / 作成 / 切替ドロップダウン)
- ホスト名ベースのサイト解決 (`acme.example.com` → site X)
- パスプレフィックスベースのサイト解決 (`/blog` → site Y)
- DB-per-site ルーティング
- per-site のテーマ有効化 UI (データモデルは存在、UI は未実装)

`ResolveSiteContext` ミドルウェアは v0.1.0 では常に primary site を選択。v2 で解決ロジックを差し替えても呼び出し元は影響を受けない。

---

## 参照

- `App\Models\Site` — Site モデル
- `App\Contracts\Site\SiteContextInterface` — サイトコンテキスト契約
- `App\Facades\SiteContext` — 簡易アクセス用 Facade
- `App\Models\Traits\BelongsToSite` — per-site モデル用トレイト
- `App\Enums\SettingScope` — Global / PerSite / Overridable
- `App\Services\Site\SettingDefinition` — 設定定義 value object
- `App\Services\Site\SettingDefinitionRegistry` — 中央設定レジストリ
- `App\Services\Site\SettingResolver` — scope 対応の設定リーダー / ライター
- `App\Services\Site\SiteStorage` — サイト対応ファイルシステムヘルパー
- `App\Services\Site\Exceptions\UnknownSettingException` — 未登録キーで発火する例外
- `App\Models\SitePluginActivation`, `App\Models\SiteThemeActivation` — per-site プラグイン / テーマ有効化レコード
- `App\Models\GlobalSetting` — ネットワーク全体設定モデル
- `PLUGIN-API.md` — 全公開 Plugin API エントリ
