# Laravel 12

- 重要: バージョン固有の Laravel ドキュメントと最新のコード例を取得するため、常に `search-docs` ツールを使用すること
@if (file_exists(base_path('app/Http/Kernel.php')))
- このプロジェクトは Laravel 10 からアップグレードされたが、新しい簡素化されたファイル構造には移行していない
- これは Laravel が推奨する方法であり問題ない。Laravel 10 の既存構造に従うこと。ユーザーが明示的に要求しない限り、新しい Laravel 構造への移行は不要

## Laravel 10 構造
- ミドルウェアは通常 `app/Http/Middleware/` に、サービスプロバイダーは `app/Providers/` に配置
- Laravel 10 構造には `bootstrap/app.php` のアプリケーション設定はない:
    - ミドルウェア登録は `app/Http/Kernel.php` で行う
    - 例外処理は `app/Exceptions/Handler.php` で行う
    - コンソールコマンドとスケジュールは `app/Console/Kernel.php` で登録
    - レート制限は `RouteServiceProvider` または `app/Http/Kernel.php` に定義される場合が多い
@else
- Laravel 11 以降、新しい簡素化されたファイル構造が採用されており、このプロジェクトはそれを使用している

## Laravel 12 構造
- Laravel 12 ではミドルウェアは `app/Http/Kernel.php` に登録しない
- ミドルウェアは `bootstrap/app.php` で `Application::configure()->withMiddleware()` を使って宣言的に設定する
- `bootstrap/app.php` はミドルウェア、例外、ルーティングファイルの登録先
- `bootstrap/providers.php` にアプリケーション固有のサービスプロバイダーを記述する
- `app\Console\Kernel.php` は存在しない。コンソール設定には `bootstrap/app.php` または `routes/console.php` を使用する
- `app/Console/Commands/` 内のコンソールコマンドは自動で利用可能になり、手動登録は不要
@endif

## データベース
- カラムを変更するマイグレーションでは、そのカラムに以前定義されていた全ての属性を含める必要がある。含めない属性は削除される
- Laravel 12 では外部パッケージなしでネイティブに Eager Load のレコード数を制限できる: `$query->latest()->limit(10);`

### モデル
- キャストは `$casts` プロパティではなく `casts()` メソッドで設定する。他のモデルの既存の慣例に従う
