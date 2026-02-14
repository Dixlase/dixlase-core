@php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
$bladePhp = '@' . 'php';
$bladeEndPhp = '@' . 'endphp';
$bladeEcho = '{{ $var ?? \'default\' }}';
@endphp
# Laravel Boost ガイドライン

Laravel Boost ガイドラインは Laravel メンテナーによってこのアプリケーション専用に作成されたものです。ユーザー満足度を高めるため、このガイドラインに忠実に従ってください。

## 基本コンテキスト
このアプリケーションは Laravel アプリケーションであり、主要な Laravel エコシステムのパッケージとバージョンは以下の通りです。全てに精通してください。これらの特定のパッケージとバージョンを遵守すること。

- php - {{ PHP_VERSION }}
@foreach (app(\Laravel\Roster\Roster::class)->packages()->unique(fn ($package) => $package->rawName()) as $package)
- {{ $package->rawName() }} ({{ $package->name() }}) - v{{ $package->majorVersion() }}
@endforeach

@if (! empty(config('boost.purpose')))
アプリケーションの目的: {!! config('boost.purpose') !!}

@endif

@if($assist->hasSkillsEnabled() && $assist->skills()->isNotEmpty())
## スキルの有効化

このプロジェクトにはドメイン固有のスキルが用意されています。該当ドメインの作業時は常に関連スキルを有効化してください。

@foreach($assist->skills() as $skill)
- `{{ $skill->name }}` — {{ $skill->description }}
@endforeach
@endif

## 規約
- このアプリケーションの既存コード規約に必ず従うこと。ファイルの作成・編集時は、隣接ファイルの構造、アプローチ、命名を確認する
- 変数やメソッドには説明的な名前を使用する。例: `isRegisteredForDiscounts` であり `discount()` ではない
- 新しいコンポーネントを作成する前に、再利用可能な既存コンポーネントを確認する

## Git コミットメッセージ
- コミットメッセージに `Co-Authored-By` 行を含めない

## ビューロジック分離ルール
- Blade テンプレート内で `\App\Enums\*`, `\App\Helpers\*`, `\App\Services\*`, `\App\Models\*` クラスを直接参照しない
- enum の値/ラベル/オプション配列、ヘルパー結果、サービス結果は全てコントローラーで準備し、ビュー変数として渡す
- Blade コンポーネントは必要なデータをプロパティとして受け取る（親ビュー/コントローラーから注入）
- 例外: 共有フルスクリーンパーシャル（例: サイドバー）でのヘルパー呼び出しは許可
- **管理画面の Blade ビューでは `{!! $bladePhp !!}` ブロック禁止**（サイドバーなどの共有フルスクリーンパーシャルを除く）
  - ビジネスロジック、データ変換、config 配列の構築はコントローラーまたは Presenter で行うこと
  - 許可される例外: `{!! $bladeEcho !!}` のようなインライン式、`old()` ヘルパーのインライン使用、`{!! $bladePhp !!} $appearanceValue = old('appearance', ...) {!! $bladeEndPhp !!}` のような単純な変数代入

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
- フロントエンドの変更がUIに反映されない場合、`{{ $assist->nodePackageManagerCommand('run build') }}`、`{{ $assist->nodePackageManagerCommand('run dev') }}`、または `{{ $assist->composerCommand('run dev') }}` の実行が必要な可能性がある。ユーザーに確認する

## ドキュメントファイル
- ドキュメントファイルはユーザーが明示的に要求した場合のみ作成すること

## 応答
- 説明は簡潔に — 自明な詳細の説明ではなく重要な点に集中する
