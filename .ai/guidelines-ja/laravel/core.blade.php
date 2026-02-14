@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
@endphp
# Laravel の流儀に従う

- 新しいファイル（マイグレーション、コントローラー、モデル等）の作成には `{{ $assist->artisanCommand('make:') }}` コマンドを使用する。利用可能な Artisan コマンドは `list-artisan-commands` ツールで確認できる
- 汎用 PHP クラスの作成には `{{ $assist->artisanCommand('make:class') }}` を使用する
- 全ての Artisan コマンドに `--no-interaction` を渡してユーザー入力なしで動作させる。正しい `--options` も指定すること

## データベース
- 常に戻り値型ヒント付きの Eloquent リレーションメソッドを使用する。生クエリや手動結合よりリレーションメソッドを優先
- 生のデータベースクエリの前に Eloquent モデルとリレーションを使用する
- `DB::` は避け `Model::query()` を優先する。ORM 機能を活用するコードを生成する
- Eager Loading を使用して N+1 クエリ問題を防止するコードを生成する
- 非常に複雑なデータベース操作には Laravel のクエリビルダーを使用する

### モデル作成
- 新しいモデル作成時はファクトリとシーダーも作成する。他に必要なものがあるかユーザーに確認し、`list-artisan-commands` で `{{ $assist->artisanCommand('make:model') }}` のオプションを確認する

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
- テスト作成には `{{ $assist->artisanCommand('make:test [options] {name}') }}` を使用する。フィーチャーテストがデフォルト、ユニットテストには `--unit` を渡す

## Vite エラー
- "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" エラーが発生した場合、`{{ $assist->nodePackageManagerCommand('run build') }}` を実行するか、ユーザーに `{{ $assist->nodePackageManagerCommand('run dev') }}` または `{{ $assist->composerCommand('run dev') }}` の実行を依頼する
