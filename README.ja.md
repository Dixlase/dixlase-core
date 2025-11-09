# Dixlase

**Dixlase** は Laravel をベースに開発中の次世代 CMS です。  
ミニマルなランディングページから、イベント管理、予約管理、EC まで、必要な機能をプラグインとして追加できます。  
「**世界一セキュアな CMS**」を目指し、AGPL ライセンスのオープンソースとして公開予定です。

---

## 🚀 特徴

- **セキュリティ重視**  
  二段階認証、ログイン通知、強力なパスワードポリシーを標準でサポート。
- **ミニマルコア + プラグイン構成**  
  コアはフロント編集と管理画面のみ。ブログやページ作成もプラグインとして提供。
- **高いカスタマイズ性**  
  `custom/` ディレクトリによる上書き・追加で案件対応も容易。
- **デュアルライセンス**  
  コアは AGPL、商用プラグインは別ライセンスで提供予定。
- **Docker ベースの開発環境**  
  Laravel, MySQL, Redis, Nginx, Vite, phpMyAdmin などをワンコマンドで起動可能。

---

## 📦 セットアップ

### 1. リポジトリをクローン

```bash
git clone https://github.com/your-org/dixlase.git
cd dixlase/docker
```

### 2. Docker コンテナを起動

```bash
docker compose up -d
```

### 3. Laravel を準備

```bash
docker compose exec -T -w /var/www/html laravel.test composer install
docker compose exec -T -w /var/www/html laravel.test php artisan key:generate
```

---

## 🤖 MCP サーバー (Laravel Boost)

Dixlase は AI コーディング支援ツール [Laravel Boost](https://laravel-news.com/supercharge-your-laravel-projects-real-ai-coding-with-laravel-boost) に対応しています。  
VSCode や Cursor から Laravel のコマンド実行やコンポーネント生成を直接利用可能です。

### 有効化手順

1. サンプルをコピー
   ```bash
   cp mcp.example.json .mcp.json
   ```

2. `.mcp.json` を環境に合わせて編集
   - `-p` → Docker プロジェクト名（例: dixlase）
   - `-f` → `docker-compose.yml` の絶対パス
   - サービス名 → 通常は `laravel.test`

3. VSCode / Cursor で MCP を有効化すると、Laravel Boost が自動起動し、  
   Livewire コンポーネント生成やテスト実行など **16種類以上のツール** が利用できます。

---

## 📚 ドキュメント

- [公式サイト（準備中）](https://dixlase.com)  
- [プラグインマーケット（計画中）](https://market.dixlase.com)  
- [開発ドキュメント（準備中）](https://docs.dixlase.com)  

---

## 🤝 コントリビュート

1. Issue でバグ報告・機能提案  
2. Fork & Pull Request  
3. Discord コミュニティ（予定）

---

## 📜 ライセンス

- コア: **AGPL v3**  
- プラグイン: 別途ライセンス (商用 / OSS) を予定

---

## 🛡️ ビジョン

> **安全性・公平性・透明性** を基盤に、  
> **拡張性** と **持続可能性** を育み、  
> 誰もが安心して自由に使える CMS を提供する。
