# 変更履歴

Dixlase CMS Core の主要な変更はすべてこのファイルに記録します。

フォーマットは [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) に準拠し、
Plugin API の安定性は [`PLUGIN-API.md`](./PLUGIN-API.md#stability-pledge) に宣言された
ポリシーに従います。

このファイルは、Plugin API の追加・非推奨化・削除に関する **正式な情報源** です。
プラグイン・テーマの作者は、変更通知を受け取るため、コアリポジトリの GitHub
リリースを購読することを推奨します。

## バージョニングと Plugin API

- リリースはセマンティックバージョニング（`MAJOR.MINOR.PATCH`）に従います。
- 0.x のベータ期間中、Plugin API（`PLUGIN-API.md` に列挙したインターフェース、DTO、
  サービス、イベント、Blade コンポーネント等の要素）は **公開済みですが、まだ凍結して
  いません**。設計が固まるまでは、互換性のない変更が入ることがあります。
- Plugin API の破壊的変更は MINOR リリース（例: `0.1` → `0.2`）でのみ行い、PATCH
  リリースでは行いません。`^0.1` を宣言した拡張は、`0.1.x` の更新で壊れません。
- 破壊的変更はすべて、このファイルの **「Plugin API — 破壊的変更」** の見出しに
  移行方法とともに記載します。可能な場合は、旧シンボルを `@deprecated` として
  1 リリース分残します。
- 破壊的変更のたびに `dixlase_api` のバージョン
  （`App\Extension\ExtensionApi::CURRENT_VERSION`）を上げます。
- Plugin API は、設計が固まった時点で凍結します。凍結はこのファイルで告知し、
  `plugin-api-v1.0` タグで示します。**特定のコアのバージョン（1.0 を含む）とは
  結び付けません**。凍結後の破壊的変更は MAJOR リリースでのみ行い、少なくとも
  MINOR 1 サイクル分の非推奨期間を設けます。

---

## [Unreleased]

### 追加

- **削除:** `livewire/livewire` を依存から外し、Livewire 版 UI コンポーネント 2 つ
  （`x-ui-livewire-modal`、`x-ui-livewire-notification`）を Plugin API の境界から外した。
  いずれも使われていなかった。管理画面が描画するモーダルは `x-ui-modal`（Alpine）で、
  `Livewire\Component` を継承したクラスも `@livewire(...)` を呼ぶビューも
  `@livewireScripts` を出すレイアウトも存在しない。2026-02-06 の試行が翌日に撤回された
  際の残骸である。Livewire を使いたいプラグインは自身で require すればよく、コアは同梱を
  やめる。あわせて `config/livewire.php`、`@livewireScriptsWithoutNavigate` ディレクティブ
  （参照先ビューが存在せず、呼ぶと例外になっていた）、共通バンドルの
  `livewire-notification.js` も削除した。
- `dls:install` でコマンドラインからインストールできるようにした。ブラウザのウィザードと
  同じ処理を実行する。設定はオプション（`--site-name`、`--admin-email`、`--db=sqlite` など）
  で渡し、不足分は対話で尋ねる。パスワードはコマンドラインではなく
  `DIXLASE_ADMIN_PASSWORD` / `DIXLASE_DB_PASSWORD` / `DIXLASE_MAIL_PASSWORD` でも渡せる。
  既定では対象データベースの全テーブルを削除して作り直すため、非対話実行には `--force` が
  必要。`--preserve-data` を付ければ削除せずにマイグレーションする。ウィザードの実行処理は
  `App\Services\Install\InstallRunner` に移し、両方の入口で共有する。
- `App\Multilingual\SiteTaglineProvider` を Plugin API の境界に追加。コアの
  `site_tagline` 設定について主要ロケールの値を供給するクラスで、拡張機能がクラス名で
  参照するため、その参照がプラグイン例外の範囲に入るよう境界に載せる。

### 変更

- インストールウィザードの完了画面で、管理画面のボタンを先頭に置き、プライマリ色にした。
  各ボタンの下に遷移先の URL を表示する。どちらのボタンも同じ finalize を実行するため、
  押し間違えるとインストールは完了したままフロントページに飛び、「インストールは終わったのに
  管理画面に行けない」という不具合と同じ見え方になっていた。

### 修正

- リリース ZIP に同梱する公式テーマが、インストールするたびに署名の検証に失敗していた。コア向けに書いた
  リリースの除外リストが、テーマの署名対象の `CHANGELOG.md` / `CONTRIBUTING*` / `SECURITY.md` / `tests/` /
  `phpunit.xml` と `.gitignore` まで外していたため。同梱テーマは composer が置いたままの形でコピーし直し、
  署名に含まれるファイルが 1 つでも欠けていればリリースのビルドを失敗させる。
- `dls:theme:migrate` / `dls:theme:migrate:refresh` / `dls:theme:migrate:rollback` / `dls:theme:seed` に
  テーマの slug を渡すと失敗していた。ディレクトリ名を `Str::studly()` だけで作っていたため、`dixlase-onepage` が
  `DixlaseOnePage` ではなく `DixlaseOnepage` を探していた。先にテーマのレコードから引くようにした。
- コアの更新・ロールバックで `public/` をディレクトリごと置き換えないようにした。中身だけを入れ替え、
  ディレクトリ自体は残すので、組み込みサーバ(`php -S`。ワンライナーは `public/` から起動する)が
  `Failed opening required '/index.php'` で止まらず、再起動しなくても動き続ける。
- パッケージが減るコアの更新は、1 回目が必ず失敗していた。`vendor/` を入れ替えたあとの `view:cache` が
  更新中のプロセスの中で動き、そのプロセスが古い `vendor/` から読み込んだ Blade の拡張(livewire/livewire)が、
  もう無いファイルを読みに行っていた。`vendor/` を入れ替えたあとのマイグレーション・シーダー・キャッシュの
  クリアは、新しい `php artisan` のプロセスで実行する。コアのロールバックのキャッシュのクリアも同じ。
- **PHP-FPM では `composer dump-autoload` が一度も成功していなかった。**コアは composer を
  `[PHP_BINARY, composer, …]` で起動するが、PHP_BINARY が処理系になるのは実行中の SAPI が
  CLI のときだけで、FPM では FPM のバイナリを指す。FPM は渡されたスクリプトを無視して自身の
  usage を stdout に出し、終了コード 64 で終わる。そのため Web リクエストからコアが実行する
  オートロードの更新は FPM 環境ですべて黙って無効になっていた — 管理画面からプラグインや
  テーマを入れてもマップは更新されずクラスが解決できず、コア更新後の再同期も何もしていなかった。
  処理系は決め打ちせず解決するようにし(`App\Support\Process\PhpBinary`)、失敗時は
  コマンドと stdout も記録する(composer も php-fpm も stdout に出すため、stderr だけでは
  何も分からなかった)。
- **リリース ZIP からの新規インストールが、拡張機能のオートロード無しで起動していた。**
  `composer.local.json` は生成物で gitignore されているため ZIP から除外されていた。
  composer-merge-plugin はこのファイルを Composer の初期化時に読むが、それはコアの
  pre-autoload-dump フックが書くより前なので、最初の `composer install` が拡張機能の PSR-4 を
  一つも含まないオートローダーをダンプし、その後二度とダンプされなかった。同梱テーマの
  `ServiceProvider` が解決できず、フロントページが
  `Call to undefined function dls_onepage_localized_setting()` で 500 になっていた。ZIP に
  `composer.local.json` を同梱し、さらに `scripts/verify-local-autoload.php` を
  `post-autoload-dump` に追加して、マニフェストが宣言する PSR-4 がマップに無いときだけ
  もう一度ダンプする。
- `vendor/` を入れ替えるコアのロールバックが、キャッシュのクリアの段で
  `include(.../ArtisanProcess.php): Failed to open stream` で失敗し、その復旧のあともサイトが
  500 のままになっていた。入れ替え後のサブプロセスを起動するクラスは、ロールバック先のどの
  リリースよりも新しいため、ソースを戻したあとに解決すると、もう無いファイルを読みに行く。
  復旧はソース・vendor・スキーマを戻したが、`bootstrap/cache/packages.php` は破棄した `vendor/`
  向けに書かれたまま残り、すべてのリクエストが無くなったプロバイダーで落ちていた。起動の仕組みは
  ディスクに触れる前に解決して 1 度動かすようにし、復旧ではパッケージ検出のキャッシュを削除して
  作り直し、更新もロールバックも新しいプロセスでアプリケーションが起動するかを確認する(成功時は
  サイトを開ける前、復旧時は成功と報告する前)。この組み合わせは、更新処理を変えたリリースから戻す
  たびに起きる。`0.1.1` から `0.1.0` へのロールバックでも同じ。
- 失敗したコアの更新は、ソースを戻す前に、その実行が適用したマイグレーションを戻すようにした。これまでは
  データベースだけが新しいスキーマのまま残り、次の更新がそのスキーマを開始時点として記録するため、
  `dls:core:rollback` でも戻らなかった。
- コアの更新の直後は、管理画面のロールバックボタンを押しても何も起きなかった。確認のモーダルが、
  更新できるものがあるときにしか描画されていなかった。
- `dls:core:update` は、更新のたびに `npm install` と `vite build` を実行していた。ビルド済みかどうかの判定が、
  コアの出力先 `public/assets/build` を見ていなかったため。リリース ZIP のビルド済みファイルをそのまま使う。
- ビルド済みの画面用ファイルを含まない署名付きのプラグイン / テーマをインストールすると、有効化の前に署名が無効に
  なっていた。画面用ファイルのビルドが `npm install` を実行し、署名対象の `package-lock.json` を手元の npm の
  書式で書き直していたため。lock ファイルがあるときは `npm ci` を使うようにした(無いときだけ `npm install`)。
  拡張機能のインストール / 更新コマンドと `dls:theme:build` の両方が対象。
- 管理画面からのインストールでは、画面用ファイルのビルドが失敗しても分からなかった。npm の出力が捨てられ、
  インストールは成功と表示され、拡張機能は JS / CSS の無いまま動いていた。失敗した手順を npm の出力の末尾と
  ともにログに残し、プラグインとテーマのインストール画面に再実行のコマンドつきの警告を出すようにした。
  管理画面のフラッシュメッセージのコンポーネント(`x-ui-flash-message`)は、これまで表示していなかった
  `warning` も表示する。

## [0.1.0] — 2026-10-01
Plugin API を初めて公開するリリースです。AGPL プラグイン・テーマ例外（`LICENSE` を
参照）の下で、プラグイン・テーマが依拠できる境界を定めます。この境界は公開済みですが、
まだ凍結していません — 冒頭の「バージョニングと Plugin API」を参照してください。

### 動作要件

- PHP `>= 8.3`
- Laravel 13

同梱の Docker ランタイムは PHP 8.3 を提供します。データベース・Web サーバー・
PHP 拡張を含む環境の全体像は `docs/ja/requirements.md` を参照してください。

### 追加

#### Plugin API サーフェス（公開）

- `App\Contracts\PluginIntegration\` 配下の **プラグイン統合 Contract**:
  `LinkableInterface`, `LinkableProviderInterface`, `MenuProviderInterface`,
  `PreviewProviderInterface`, `PrivacyPolicyProviderInterface`,
  `SeoMetaProviderInterface`, `DashboardWidgetProviderInterface`,
  `DashboardNotificationProviderInterface`。
- `App\DTO\PluginIntegration\` 配下の **プラグイン統合 DTO**:
  `LinkableDTO`, `MenuDTO`, `MenuItemDTO`, `PreviewDTO`, `PreviewFieldDTO`,
  `SearchQueryDTO`, `PaginatedResultDTO`, `SeoMetaDTO`, `DashboardWidgetDTO`,
  `DashboardNotificationDTO`。
- `App\Contracts\Plugin\` 配下の **プラグイン Capability Contract**:
  `PluginCapabilityInterface`, `ApiResourceProviderInterface`,
  `ContentProviderCapableInterface`, `EditorCapableInterface`, `MailCapableInterface`,
  `SignatureVerifierInterface`, `PluginPermissionServiceInterface`。
- `App\Contracts\` 配下の **サービス Contract**: 管理ナビゲーション、CSP ポリシー、
  暗号化、拡張ソース、ファイル整合性、法的ページ、ロギング、メール、ルートスラッグ、
  テーマ権限、翻訳、二要素認証をカバー。
- `App\Contracts\Repositories\` 配下の **リポジトリ Contract**: 設定、メディア、
  プラグイン、テーマ向け。
- **コアイベント名**: バックアップ、デプロイ、翻訳、プラグインライフサイクル、
  テーマライフサイクル、キャッシュ、メンテナンス、セキュリティ、監査、AI の各
  カテゴリを表す `App\Events\DixlaseEvents` 定数。全一覧は `PLUGIN-API.md` §9.8 に列挙。
- フォーム、UI、管理、コンテンツエディタ、セキュリティ／認証、メディア、
  フロントエンド向けの **Blade コンポーネント**。全一覧は `PLUGIN-API.md` §6 に列挙。
- プラグインルート向けの **ミドルウェアグループ**: `plugin`, `plugin.web`,
  `plugin.admin`, `plugin.api`, `plugin.api.public`。
- プラグインの管理ナビゲーション、ロール権限、データベースクリーンアップ向けの
  **設定構造**。

#### ゼロトラスト拡張ポイント（no-op 既定値で予約）

- `App\Contracts\Security\SecretProviderInterface` — 差し替え可能な秘密情報ストア
  バックエンド（Vault / AWS KMS / GCP KMS）。既定:
  `App\Services\Security\EnvSecretProvider`。
- `App\Contracts\Security\RiskEvaluatorInterface` — 条件付きアクセスのリスク
  スコアリングフック。既定: `App\Services\Security\LowRiskEvaluator` は
  `RiskScore::low()` を返す。
- `App\Contracts\Security\PolicyEvaluatorInterface` — `PermissionService` 向けの
  ABAC フック。既定: `App\Services\Security\NullPolicyEvaluator` は `null` を返し、
  RBAC に委譲する。
- `App\DTO\Security\LoginContext`, `App\DTO\Security\RiskScore` — サポート型。
- `App\Enums\AccessRiskLevel`, `App\Enums\PolicyDecision`。
- `auth.iap` / `auth.mtls` ミドルウェアエイリアス — 予約済み名前空間。プラグインが
  置き換えるまで、既定実装は `abort(501)`。

#### 監査・マルチサイト・プライバシー・キャッシュ・i18n の基盤

- `App\Events\AuditLogCreated::SCHEMA_VERSION = 1` および
  `App\DTO\Audit\AuditLogPayload` — SIEM サブスクライバ向けにバージョン管理された監査
  イベントのペイロード形状。
- `App\Contracts\Site\SiteContextInterface` および `App\Facades\SiteContext` —
  マルチサイト解決の境界。`BelongsToSite` トレイトはモデルをアクティブサイトに
  スコープする。
- `App\Contracts\PluginIntegration\PrivacyDataProviderInterface`,
  `App\DTO\PluginPrivacy\UserDataExportDTO`,
  `App\DTO\PluginPrivacy\UserDataDeletionDTO`,
  `App\Enums\PluginPrivacy\DeletionMode` — 個人データを保存するプラグイン向けの、
  GDPR アクセス権・忘れられる権利の Contract。
- `App\Support\Cache\CacheKey` および `App\Support\Cache\SiteScopedCacheKey` —
  コア／プラグイン／テーマ間の衝突を防ぎ、`site_id` で分割する、規約化された
  キャッシュキービルダー。
- `App\Contracts\TranslationResolver`,
  `App\Contracts\I18n\MissingTranslationHandler`,
  `App\Traits\TranslatableTrait` — 翻訳された行を供給するプラグイン向けの
  コンテンツ i18n フック。
- `App\Helpers\DateTimeHelper` および `<x-ui-datetime>` Blade コンポーネント —
  UTC 保存／`display_timezone` 描画の日時 API。
- `App\Events\DixlaseEvents::URL_SLUG_CHANGED` および `ADMIN_URL_CHANGED` —
  リダイレクト系プラグイン向けの予約フック。`web` ミドルウェアグループの先頭
  prepend スロットは、同一プラグインクラス専用に予約されている（`PLUGIN-API.md`
  §7.5 を参照）。

#### 安定性インフラ

- 安定性の状態、変更の告知手順、非推奨化ポリシー、告知チャネルを文書化した
  `PLUGIN-API.md` の Stability Pledge セクション。
- `PLUGIN-API.md` §9 のサービスクラス命名規約表（`*Service` / `*Registry` /
  `*Manager` / `*Resolver`）。
- `docs/development/naming.md` で凍結された公開識別子の命名規約（API スコープ、
  権限キー、イベント名、Webhook イベント種別、監査ログアクション、プラグイン
  capability、env／config キー）。
- マイグレーション不変性ゲートの足場: `php artisan dls:migration:lint` を Tier 1
  の CI ジョブとして配線。ロックファイル（`database/migration-lock.json`）は、
  スキーマの変更を妨げないよう Beta 1 → GA のベータシリーズ中は意図的に
  **コミットしない**。ロックが存在しない場合、コマンドは 0 で終了する。ロックは
  GA リリースタグの直前に生成・コミットされる（`docs/operations/upgrading.md` と
  CLAUDE.md の「Migration Editing Policy」を参照）。
- すべての in-tree プラグインの `plugin.json` に `requires.dixlase: ^0.1.0` を宣言。

#### 監査ログ整合性（運用）

- `audit:integrity verify` は、すべての日次シール（1 日あたり O(1)）に加え、
  最後に検証したレコード以降のハッシュチェーンを検証するようになった。`--all` は
  全行を再検証する。`--date` と `--from` / `--to` は従来の意味のまま。
- サイトヘルスに **監査ログ整合性** の項目を追加（改ざん検出時は critical、毎時
  ビルドまたは日次シールが停止した場合は warning、30 日間何も検証されていない
  場合は推奨）。
- Web インストーラーは、完了時にハッシュチェーンを 1 回構築する。
- 監査ログの詳細画面に、チェーンシーケンス、レコードハッシュ、検証ステータス、
  最終検証時刻を表示する。
- `AUDIT_LOG_SECRET` を `config('app.audit_log_secret')` に配線し、日次シール用の
  任意の専用 HMAC 鍵とする（未設定時は `APP_KEY` にフォールバック）。

### プラグイン作者向けの注記

- v0.1 系を対象とするプラグインは、`plugin.json` に
  `"requires": {"dixlase": "^0.1.0", "php": ">=8.3"}` を宣言してください。
  `>=1.0.0` はこの系列では正しい制約ではありません（0.1.x のリリースと
  非互換として扱われます）。
- `PLUGIN-API.md` に列挙されていない内部クラス（特に
  `App\Services\Plugin\PluginHealthScorer`, `App\DTO\Plugin\HealthScoreResult` など
  の実装詳細）は Plugin API の **一部ではありません**。これらは予告なく任意の
  リリースで変更される可能性があります。
- `App\Services\Plugin\CoreSignatureVerifier` は意図的に `@api` を付与していません。
  プラグインは代わりに `App\Contracts\Plugin\SignatureVerifierInterface` に依存して
  ください。

[Unreleased]: https://github.com/Dixlase/dixlase-core/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/Dixlase/dixlase-core/releases/tag/v0.1.0
