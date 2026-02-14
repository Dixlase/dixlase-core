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

@if (config('boost.browser_logs', true) !== false || config('boost.browser_logs_watcher', true) !== false)
## ブラウザログの読み取り（`browser-logs` ツール）
- `browser-logs` ツールでブラウザのログ、エラー、例外を読み取れる
- 最近のブラウザログのみが有用 — 古いログは無視する
@endif

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
