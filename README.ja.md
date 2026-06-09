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

## 🌐 コメントの言語

Dixlase のソースは正本として英語コメント付きで配布されます。各言語のコメントアーカイブは `resources/comment-translations/{locale}/`（およびプラグイン/テーマ内の同パス）にあり、開発環境のソースに in-place で適用できます。

```bash
# すべてのソースコメントを日本語に変換（コア + プラグイン + テーマ）
./convert-comments.sh ja

# 英語に戻す
./convert-comments.sh ja --reverse

# 書き込まずにプレビュー
./convert-comments.sh ja --dry-run

# 利用可能な言語を一覧表示
./convert-comments.sh --list
```

置換は AST 認識ベース — PHP のコメントトークンのみが書き換えられ、文字列リテラルは触りません — また冪等（既に変換済みのソースに再適用しても何も起きない）。

辞書はソースと同じリポジトリでバージョン管理されているため、翻訳の改善は Pull Request として受け付けます。新しい言語（例: `zh`, `ko`）を追加するには、`resources/comment-translations/{locale}/_glossary.php` と各ファイルの辞書（英語キーを共有）を作成するだけです。

GitHub リリースには正本である英語ソースのみが含まれます。ユーザーは（または [Dixlase Docker Installer](https://github.com/Dixlase/dixlase-installer-docker) のセットアップ時に）このスクリプトでインストール後に言語を切り替えます。

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
- [コピーライトポリシー](./COPYRIGHT-POLICY.ja.md) — デュアルライセンス方針と CLA モデルの概要
- [コントリビューターライセンス契約](./CLA.ja.md) — 個人・法人共通の CLA(冒頭で署名区分を選択)
- [セキュリティポリシー](./SECURITY.ja.md) — 脆弱性の報告
- [Plugin API](./PLUGIN-API.md) — Plugin API 境界定義

---

## 📜 ライセンス

Dixlase CMS は**デュアルライセンス**で配布されています:

- **オープンソースライセンス**: [GNU Affero General Public License v3](./LICENSE) および Dixlase プラグイン・テーマ例外条項([LICENSE-EXCEPTIONS.ja](./LICENSE-EXCEPTIONS.ja))
- **商用ライセンス**: AGPL v3 の条件に準拠できない用途(クローズドソース SaaS での改変版配布など)に対しては、別途商用ライセンスをご用意しています — 詳細は [LICENSE-COMMERCIAL](./LICENSE-COMMERCIAL)(現在ドラフト)、または **info@dixlase.org** までお問い合わせください。

ファイル全体の構成は [NOTICE.ja](./NOTICE.ja) にまとめています([English](./NOTICE))。

### プラグイン・テーマについて

[Plugin API](./PLUGIN-API.md) のみを通じて Dixlase CMS と連携するプラグイン・テーマは派生物とはみなされず、**プロプライエタリライセンスを含む任意のライセンスで配布可能**です。詳細は [LICENSE-EXCEPTIONS.ja](./LICENSE-EXCEPTIONS.ja) をご確認ください。

### ソースコードの提供(AGPL §13)

Dixlase CMS をサーバーで運用し、ネットワーク経由でユーザーに提供する場合、AGPL §13 により、実行中のバージョンのソースコードをユーザーが取得できる必要があります。管理画面フッターの「Source」リンクがユーザーからアクセス可能であることをご確認ください。公開 URL は環境変数 `DIXLASE_SOURCE_URL` で指定できます。

### コントリビューションのライセンス

> **現状**: Dixlase は初期開発期にあり、**外部からの Pull Request は受け付けていません**。Issue でのバグ報告と Discussions での質問は歓迎します — [CONTRIBUTING.md](./CONTRIBUTING.md) をご覧ください。コードを伴うコントリビューションは、コントリビューターライセンス契約 (CLA) の正式法務レビュー完了後に再開予定です。

Dixlase コアリポジトリへのコントリビューションは、コントリビューターライセンス契約 (CLA) により規律されます。契約本文([CLA.ja.md](./CLA.ja.md) — 個人・法人共通の 1 文書で、冒頭の署名区分で個人／法人を選択)は透明性と事前確認のため repository に含まれていますが、**現在は運用していません**。外部からのコードコントリビューションが再開された時点で運用開始します。ライセンス全体の文脈は [コピーライトポリシー](./COPYRIGHT-POLICY.ja.md) を、運用予定の PR ベースのコントリビューションフローは [CONTRIBUTING-FUTURE.md](./CONTRIBUTING-FUTURE.md) をご覧ください。

---

## 🛡️ ビジョン

> **安全性・公平性・透明性** を基盤に、
> **拡張性** と **持続可能性** を育み、
> 誰もが安心して自由に使える CMS を提供する。
