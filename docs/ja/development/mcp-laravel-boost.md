# MCP サーバー (Laravel Boost)

Dixlase は AI コーディング支援のための MCP (Model Context Protocol) サーバーとして [Laravel Boost](https://laravel-news.com/supercharge-your-laravel-projects-real-ai-coding-with-laravel-boost) に対応しています。これにより、VSCode や Cursor などのエディタから、Dixlase のチェックアウトに対して Laravel コマンドの実行やコンポーネント生成を直接行えます。

このページの英語版は [`docs/development/mcp-laravel-boost.md`](../../development/mcp-laravel-boost.md) にあります。

---

## 有効化手順

1. サンプルをコピー

   ```bash
   cp mcp.example.json .mcp.json
   ```

2. `.mcp.json` を環境に合わせて編集

   - `-p` → Docker プロジェクト名(例: `dixlase`)
   - `-f` → `docker-compose.yml` の絶対パス
   - サービス名 → 通常は `laravel.test`

3. VSCode / Cursor で MCP を有効化

   Laravel Boost が自動起動し、Livewire コンポーネント生成やテスト実行、
   データベースマイグレーションなど 16 種類以上のツールが利用できます。
