# MCP Server (Laravel Boost)

Dixlase supports [Laravel Boost](https://laravel-news.com/supercharge-your-laravel-projects-real-ai-coding-with-laravel-boost) as an MCP (Model Context Protocol) server for AI-assisted development. With it, editors such as VSCode or Cursor can run Laravel commands and generate components directly against your Dixlase checkout.

The Japanese mirror of this page lives at [`docs/ja/development/mcp-laravel-boost.md`](../ja/development/mcp-laravel-boost.md).

---

## How to enable

1. Copy the example config:

   ```bash
   cp mcp.example.json .mcp.json
   ```

2. Edit `.mcp.json` for your environment:

   - `-p` → your Docker project name (e.g., `dixlase`)
   - `-f` → absolute path to `docker-compose.yml`
   - Service name → usually `laravel.test`

3. Enable MCP in VSCode / Cursor.

   Laravel Boost starts automatically, providing 16+ tools such as Livewire
   component scaffolding, running tests, and database migrations.
