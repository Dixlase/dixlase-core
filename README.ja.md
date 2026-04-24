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
git clone https://github.com/Dixlase/dixlase-core.git
cd dixlase-core/docker
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

## 📖 プロジェクトドキュメント

- [コントリビューションガイド](./CONTRIBUTING.ja.md) — コントリビュート方法
- [コピーライトポリシー](./COPYRIGHT-POLICY.ja.md) — コントリビューター同意書(CAA)
- [セキュリティポリシー](./SECURITY.ja.md) — 脆弱性の報告
- [Plugin API](./PLUGIN-API.md) — Plugin API 境界定義

---

## 📜 ライセンス

Dixlase CMS は**デュアルライセンス**で配布されています:

- **オープンソースライセンス**: [GNU Affero General Public License v3](./LICENSE-ja) および Dixlase プラグイン・テーマ例外条項
- **商用ライセンス**: AGPL v3 の条件に準拠できない用途(クローズドソース SaaS での改変版配布など)に対して、別途商用ライセンスをご用意しています。

商用ライセンスに関するお問い合わせは **office@exc-d.com** までお願いします。

### プラグイン・テーマについて

[Plugin API](./PLUGIN-API.md) のみを通じて Dixlase CMS と連携するプラグイン・テーマは派生物とはみなされず、**プロプライエタリライセンスを含む任意のライセンスで配布可能**です。詳細は [Dixlase プラグイン・テーマ例外条項](./LICENSE-ja) をご確認ください。

### ソースコードの提供(AGPL §13)

Dixlase CMS をサーバーで運用し、ネットワーク経由でユーザーに提供する場合、AGPL §13 により、実行中のバージョンのソースコードをユーザーが取得できる必要があります。管理画面フッターの「Source」リンクがユーザーからアクセス可能であることをご確認ください。公開 URL は環境変数 `DIXLASE_SOURCE_URL` で指定できます。

### コントリビューションのライセンス

Dixlase コアリポジトリへのコントリビューションは [コピーライトポリシー](./COPYRIGHT-POLICY.ja.md) の対象となります。Pull Request の提出前にお読みください。

---

## 🛡️ ビジョン

> **安全性・公平性・透明性** を基盤に、
> **拡張性** と **持続可能性** を育み、
> 誰もが安心して自由に使える CMS を提供する。
