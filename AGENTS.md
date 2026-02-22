<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost ガイドライン

Laravel Boost ガイドラインは Laravel メンテナーによってこのアプリケーション専用に作成されたものです。ユーザー満足度を高めるため、このガイドラインに忠実に従ってください。

## 基本コンテキスト

このアプリケーションは Laravel アプリケーションであり、主要な Laravel エコシステムのパッケージとバージョンは以下の通りです。全てに精通してください。これらの特定のパッケージとバージョンを遵守すること。

- php - 8.3.24
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## 規約

- このアプリケーションの既存コード規約に必ず従うこと。ファイルの作成・編集時は、隣接ファイルの構造、アプローチ、命名を確認する
- 変数やメソッドには説明的な名前を使用する。例: `isRegisteredForDiscounts` であり `discount()` ではない
- 新しいコンポーネントを作成する前に、再利用可能な既存コンポーネントを確認する

## Git コミットメッセージ

- コミットメッセージに `Co-Authored-By` 行を含めない
- conventional commit 形式を使用する（例: `feat:`, `fix:`, `refactor:`）
- コミットメッセージは**日英バイリンガル形式**で記述する
- タイトルは英語・日本語を連続して冒頭に配置し、その後に英語の箇条書き、`----` 区切り、日本語の箇条書きの順
- 例:
  ```
  feat: add user profile page
  feat: ユーザープロフィールページを追加

  - Add ProfileController with show/edit actions
  - Create profile Blade views with avatar upload

  ----

  - ProfileControllerにshow/editアクションを追加
  - アバターアップロード付きプロフィールBladeビューを作成
  ```

## ビューロジック分離ルール

- Blade テンプレート内で `\App\Enums\*`, `\App\Helpers\*`, `\App\Services\*`, `\App\Models\*` クラスを直接参照しない
- enum の値/ラベル/オプション配列、ヘルパー結果、サービス結果は全てコントローラーで準備し、ビュー変数として渡す
- Blade コンポーネントは必要なデータをプロパティとして受け取る（親ビュー/コントローラーから注入）
- 例外: 共有フルスクリーンパーシャル（例: サイドバー）でのヘルパー呼び出しは許可
- **管理画面の Blade ビューでは `@php` ブロック禁止**（サイドバーなどの共有フルスクリーンパーシャルを除く）
  - ビジネスロジック、データ変換、config 配列の構築はコントローラーまたは Presenter で行うこと
  - 許可される例外: `{{ $var ?? 'default' }}` のようなインライン式、`old()` ヘルパーのインライン使用、`@php $appearanceValue = old('appearance', ...) @endphp` のような単純な変数代入

## 翻訳キーのスコープ

- `lang/*/admin/navigation.php` はサイドバー専用の翻訳ファイル。サイドバー以外のビューから参照しないこと
- 各ビューは独自の翻訳ファイルに翻訳キーを定義する（例: `lang/*/admin/settings/base/index.php`）
- コンポーネントは `lang/*/components/<component-name>.php` に翻訳キーを定義する

### プラグイン翻訳ファイル構成

- プラグインはディレクトリベースの構成を使用: `lang/{en,ja}/admin/{resource}/{page}.php`
- `admin.php` にはプラグイン情報（plugin, provider）のみを含める。ページ固有の翻訳は別ファイルにする
- 各管理ページの翻訳ファイルには先頭に `heading` と `description` キーが必要:
  ```php
  return [
      'heading' => 'Page Master',
      'description' => 'Manage all pages. ...',
      // other keys
  ];
  ```
- `heading`: `resolvePluginHeadingKey()` により自動解決（ルート名をディレクトリパスに変換）
- `description`: そのページで何ができるかを1〜2文で説明

## 検証スクリプト

- テストがその機能をカバーし動作を証明している場合、検証スクリプトや tinker を作成しない。ユニットテストとフィーチャーテストの方が重要

## アプリケーション構成とアーキテクチャ

- 既存のディレクトリ構成に従う — 承認なしに新しいベースフォルダを作成しない
- 承認なしにアプリケーションの依存関係を変更しない

## フロントエンドバンドル

- フロントエンドの変更がUIに反映されない場合、`npm run build`、`npm run dev`、または `composer run dev` の実行が必要な可能性がある。ユーザーに確認する

## ドキュメントファイル

- ドキュメントファイルはユーザーが明示的に要求した場合のみ作成すること

## 応答

- 説明は簡潔に — 自明な詳細の説明ではなく重要な点に集中する

=== boost rules ===

# Laravel Boost

- Laravel Boost はこのアプリケーション専用の強力なツールを備えた MCP サーバー。積極的に活用すること

## Artisan

- Artisan コマンドを実行する際は `list-artisan-commands` ツールで利用可能なパラメータを確認する

## URL

- ユーザーにプロジェクトURLを共有する際は `get-absolute-url` ツールで正しいスキーム、ドメイン/IP、ポートを確認する

## Tinker / デバッグ

- PHP コードのデバッグや Eloquent モデルの直接クエリには `tinker` ツールを使用する
- データベースの読み取りのみが必要な場合は `database-query` ツールを使用する
- マイグレーションやモデルを作成する前に `database-schema` ツールでテーブル構造を確認する

## ブラウザログの読み取り（`browser-logs` ツール）

- `browser-logs` ツールでブラウザのログ、エラー、例外を読み取れる
- 最近のブラウザログのみが有用 — 古いログは無視する

## ドキュメント検索（重要）

- Boost には強力な `search-docs` ツールがあり、Laravel エコシステムパッケージの作業時は他のアプローチより先に使用すること。このツールはインストール済みパッケージとバージョンを自動的に Boost API に送信し、バージョン固有のドキュメントのみを返す。特定パッケージのドキュメントが必要な場合はパッケージ名の配列を渡す
- コード変更前にドキュメントを検索して正しいアプローチを確認する
- 複数の広範でシンプルなトピックベースのクエリを一度に使用する。例: `['rate limiting', 'routing rate limiting', 'routing']`。最も関連性の高い結果が最初に返される
- クエリにパッケージ名を含めない（パッケージ情報は既に共有済み）。例: `test resource table` を使用し、`filament 4 test resource table` は不可

### 検索構文

1. 自動ステミング付き単語検索 - query=authentication - 'authenticate' や 'auth' も検出
2. 複数単語（AND論理） - query=rate limit - "rate" AND "limit" を含む結果
3. 引用フレーズ（完全一致） - query="infinite scroll" - 隣接した単語でこの順序
4. 混合クエリ - query=middleware "rate limit" - "middleware" AND 完全一致 "rate limit"
5. 複数クエリ - queries=["authentication", "middleware"] - いずれかの用語

=== php rules ===

# PHP

- 制御構文では単一行でも常に波括弧を使用する

## コンストラクタ

- `__construct()` では PHP 8 のコンストラクタプロパティプロモーションを使用する
    - `public function __construct(public GitHub $github) { }`
- コンストラクタが private でない限り、パラメータゼロの空 `__construct()` は許可しない

## 型宣言

- メソッドと関数には常に明示的な戻り値型を宣言する
- メソッドパラメータには適切な PHP 型ヒントを使用する

<!-- 明示的な戻り値型とメソッドパラメータの例 -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enum

- Enum のキーは通常 TitleCase にする。例: `FavoritePerson`, `BestLake`, `Monthly`

## コメント

- インラインコメントよりPHPDocブロックを優先する。ロジックが非常に複雑な場合を除き、コード内にコメントを書かない

## PHPDoc ブロック

- 配列には適切な array shape 型定義を追加する

=== tests rules ===

# テストの必須化

- 全ての変更はプログラム的にテストすること。新しいテストを作成するか既存テストを更新し、該当テストが通ることを確認する
- コード品質と速度を確保するため、必要最小限のテストを実行する。`php artisan test --compact` にファイル名やフィルタを指定して使用する

=== laravel/core rules ===

# Laravel の流儀に従う

- 新しいファイル（マイグレーション、コントローラー、モデル等）の作成には `php artisan make:` コマンドを使用する。利用可能な Artisan コマンドは `list-artisan-commands` ツールで確認できる
- 汎用 PHP クラスの作成には `php artisan make:class` を使用する
- 全ての Artisan コマンドに `--no-interaction` を渡してユーザー入力なしで動作させる。正しい `--options` も指定すること

## データベース

- 常に戻り値型ヒント付きの Eloquent リレーションメソッドを使用する。生クエリや手動結合よりリレーションメソッドを優先
- 生のデータベースクエリの前に Eloquent モデルとリレーションを使用する
- `DB::` は避け `Model::query()` を優先する。ORM 機能を活用するコードを生成する
- Eager Loading を使用して N+1 クエリ問題を防止するコードを生成する
- 非常に複雑なデータベース操作には Laravel のクエリビルダーを使用する

### モデル作成

- 新しいモデル作成時はファクトリとシーダーも作成する。他に必要なものがあるかユーザーに確認し、`list-artisan-commands` で `php artisan make:model` のオプションを確認する

### API と Eloquent リソース

- API ではデフォルトで Eloquent API リソースと API バージョニングを使用する。既存の API ルートが異なる慣例の場合はそれに従う

## コントローラーとバリデーション

- バリデーションはコントローラー内のインラインではなく、常に Form Request クラスで行う。バリデーションルールとカスタムエラーメッセージの両方を含める
- 配列ベースか文字列ベースかは既存の Form Request を確認して合わせる

## 認証と認可

- Laravel の組み込み認証・認可機能（gates、policies、Sanctum 等）を使用する

## URL 生成

- 他のページへのリンク生成には名前付きルートと `route()` 関数を優先する

## キュー

- 時間のかかる操作には `ShouldQueue` インターフェースを使用したキュージョブを使用する

## 設定

- 環境変数は設定ファイル内でのみ使用する — config ファイル外で `env()` 関数を直接使用しない。`env('APP_NAME')` ではなく `config('app.name')` を使用すること

## テスト

- テスト用モデルの作成にはファクトリを使用する。手動セットアップ前にファクトリのカスタム state を確認する
- Faker: `$this->faker->word()` や `fake()->randomDigit()` を使用する。`$this->faker` と `fake()` のどちらを使うかは既存の慣例に従う
- テスト作成には `php artisan make:test [options] {name}` を使用する。フィーチャーテストがデフォルト、ユニットテストには `--unit` を渡す

## Vite エラー

- "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" エラーが発生した場合、`npm run build` を実行するか、ユーザーに `npm run dev` または `composer run dev` の実行を依頼する

=== laravel/v12 rules ===

# Laravel 12

- 重要: バージョン固有の Laravel ドキュメントと最新のコード例を取得するため、常に `search-docs` ツールを使用すること
- Laravel 11 以降、新しい簡素化されたファイル構造が採用されており、このプロジェクトはそれを使用している

## Laravel 12 構造

- Laravel 12 ではミドルウェアは `app/Http/Kernel.php` に登録しない
- ミドルウェアは `bootstrap/app.php` で `Application::configure()->withMiddleware()` を使って宣言的に設定する
- `bootstrap/app.php` はミドルウェア、例外、ルーティングファイルの登録先
- `bootstrap/providers.php` にアプリケーション固有のサービスプロバイダーを記述する
- `app\Console\Kernel.php` は存在しない。コンソール設定には `bootstrap/app.php` または `routes/console.php` を使用する
- `app/Console/Commands/` 内のコンソールコマンドは自動で利用可能になり、手動登録は不要

## データベース

- カラムを変更するマイグレーションでは、そのカラムに以前定義されていた全ての属性を含める必要がある。含めない属性は削除される
- Laravel 12 では外部パッケージなしでネイティブに Eager Load のレコード数を制限できる: `$query->latest()->limit(10);`

### モデル

- キャストは `$casts` プロパティではなく `casts()` メソッドで設定する。他のモデルの既存の慣例に従う

=== livewire/core rules ===

# Livewire

- Livewire は PHP のみでダイナミックかつリアクティブなインターフェースを構築できる — JavaScript は不要
- JavaScript フレームワークの代わりに、クライアントサイドのインタラクションが必要な場合は Alpine.js を使用してUIを構築する
- 状態はサーバーに保持し、UIはそれを反映する。アクション内でバリデーションと認可を行うこと（HTTPリクエストと同様）
- 重要: Livewire 関連のタスク作業時は必ず `livewire-development` を有効化すること

=== pint/core rules ===

# Laravel Pint コードフォーマッター

- 変更を確定する前に `vendor/bin/pint --dirty` を実行して、プロジェクトのコードスタイルに一致させること
- `vendor/bin/pint --test` は実行しない。フォーマット修正には `vendor/bin/pint` を使用する

=== phpunit/core rules ===

# PHPUnit

- このアプリケーションは PHPUnit を使用する。全テストは PHPUnit クラスで記述すること。新しいテストの作成には `php artisan make:test --phpunit {name}` を使用する
- "Pest" で書かれたテストを見つけたら PHPUnit に変換する
- テストを更新したら、その個別テストを実行する
- 機能に関連するテストが通ったら、テストスイート全体を実行するかユーザーに確認する
- テストは全てのハッピーパス、失敗パス、エッジケースをカバーすること
- 承認なしに tests ディレクトリからテストやテストファイルを削除しない。これらは一時ファイルではなくアプリケーションのコアである

## テストの実行

- 確定前に最小限のテストをフィルタ指定で実行する
- 全テスト実行: `php artisan test --compact`
- ファイル内の全テスト実行: `php artisan test --compact tests/Feature/ExampleTest.php`
- 特定テスト名でフィルタ: `php artisan test --compact --filter=testName`（関連ファイル変更後に推奨）

=== tailwindcss/core rules ===

# Tailwind CSS

- 既存の Tailwind の慣例を常に使用する。新しいパターンを追加する前にプロジェクト内の既存パターンを確認する
- 重要: バージョン固有の Tailwind CSS ドキュメントと最新のコード例を取得するため、常に `search-docs` ツールを使用すること。トレーニングデータに頼らない
- 重要: Tailwind CSS やスタイリング関連のタスク作業時は必ず `tailwindcss-development` を有効化すること
</laravel-boost-guidelines>
