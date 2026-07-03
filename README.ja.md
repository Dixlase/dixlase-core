# Dixlase

For English, see [README.md](./README.md).

**Dixlase** は [exc-D inc.](https://exc-d.com) が開発する、Laravel ベースの日本発オープンソース CMS です。  
守りはコアで固め、表現はプラグインとテーマで自由に広げる — コーポレートサイト、受託案件、社内限定運用、個人サイトまで幅広く対応します。  
ページやブログを含め、本質的な機能以外はすべてインストール可能なプラグインとして提供されます。  
コアがセキュリティを担うため、運用者・制作者はコンテンツの作成と運用に集中できます。  
「**世界一セキュアな CMS**」を目指し、AGPL ライセンスのオープンソースとして公開しています。

---

## 🚀 特徴

- **多層的なセキュリティ** — パスキー / メールコードによる 2 要素認証、IP 制限、ログイン通知、ロールベース権限を標準装備。
- **プラグインで組み立てるアーキテクチャ** — コアは最小限。機能はプラグインで追加し、コア機能の多くも同じ API で実装。
- **オンプレミス対応** — レンタルサーバー・VPS・専用サーバーなど、あなたの環境であなたのデータを運用。
- **詳細な監査ログ** — 管理操作を構造化された形式で記録し、運用の透明性と説明責任を担保。
- **サイトヘルスチェック** — セキュリティ設定・構成・運用状態を定期診断し、機械可読な形式で出力。
- **安心のセットアップ** — インストールウィザードが環境設定・DB 接続・管理者登録・初期セキュリティまでガイド。
- **CSP 対応** — Content Security Policy ヘッダーを標準送信し、XSS やコンテンツ改ざんから訪問者を保護。
- **プラグインの安全性** — 権限宣言・静的解析・署名検証・健全性スコアで、リスクや改ざんをインストール時に検出。

---

## 🕹️ ライブデモ

インストール不要 — Dixlase の管理画面と運用体験をブラウザ上でそのまま試せます。  
訪問者ごとに SQLite テンプレートから使い捨てインスタンスが生成され、一定時間で自動破棄されるため、何度でもリセットして試せます。  
管理画面・コンテンツ編集・プラグインインストールまで、すべて本番同様の操作感です。

ライブデモは [公式サイト](https://dixlase.org/) をご覧ください。

---

## 📦 セットアップ

環境に合わせて以下から選んでください:

### 手動インストール(ZIP)

[GitHub Releases](https://github.com/Dixlase/dixlase-core/releases) から最新リリースの ZIP をダウンロードして:

```bash
unzip dixlase-*.zip
cd dixlase
composer install
```

その後ブラウザでサイト URL にアクセスすると、インストールウィザードがデータベース設定・管理者アカウント作成・初期設定をガイドします。

### クイックインストールスクリプト

PHP 8.2 以上と Composer が用意されたクリーンな VPS / ベアメタル環境では、1 コマンドでインストールできます:

```bash
curl -sS https://install.dixlase.net | php
```

スクリプトは PHP バージョンと必要な拡張のチェック、最新リリースのダウンロード、`composer install` の実行、アプリケーションキーの生成、ディレクトリパーミッションの設定、Web インストールウィザードへの URL 表示を行います。

### Docker インストーラ

Docker 環境のホストでは [Docker インストーラ](https://github.com/Dixlase/dixlase-installer-docker) を利用し、`docker compose` 経由で Dixlase を立ち上げます。前提条件と手順はインストーラリポジトリの README をご確認ください。

### Composer create-project

Composer が使える環境では、1 コマンドで新規インストールをスキャフォールドできます:

```bash
composer create-project dixlase/dixlase-core dixlase
```

---

コア自体の開発を行う場合は、本リポジトリを clone し、[Docker インストーラ](https://github.com/Dixlase/dixlase-installer-docker) が提供する開発用 Docker スタックを利用してください。開発者向けドキュメントは準備中です。

---

## 🧩 公式プラグイン

Dixlase はミニマルなコアを出荷し、以下の公式プラグインをコアと並行して保守しています。必要な機能だけを — 管理画面のプラグイン一覧から、コマンド操作や手動のファイル配置なしで — インストールできます。

| プラグイン | 概要 |
| --- | --- |
| **Dixlase Pages** | 固定ページなど、コンテンツ作成の基本機能。ブログ投稿やカスタム投稿タイプは別プラグインとして提供予定。 |
| **Dixlase Inquiry** | 自動返信メール・スパム対策・管理側メッセージ管理を備えたお問い合わせフォーム。 |
| **Dixlase Menus** | ヘッダー / フッター / サイドバーのメニュー構造をドラッグ&ドロップで視覚的に編集。 |
| **Dixlase SEO** | メタタグ・サイトマップ・OGP・構造化データをページ単位／サイト全体で管理。 |
| **Dixlase Cookie** | Cookie 同意の表示・記録、カテゴリ別同意、同意状態に応じたスクリプト発火。 |

公式プラグインは今後も拡充予定です。全ラインナップは [GitHub](https://github.com/Dixlase) でも確認できます。

---

## 🎨 公式テーマ

テーマはサイトの見た目と構造を決めるもので、必要に応じて切り替えられます。

- **Dixlase OnePage** — シングルページ構成に最適化された既定テーマ(ヒーロー・コンテンツビルダー・お問い合わせフォーム・フッター)。コーポレートサイトやランディングページに好適。

今後のリリースでテーマの拡充を予定しています。

---

## 📚 ドキュメント

- [プロジェクトサイト — dixlase.org](https://dixlase.org/) — 概要・ライブデモ・お知らせ
- [ドキュメント](docs/ja/index.md) — インストール・運用・開発者ガイド
- [開発ガイド](docs/ja/development/index.md) — アーキテクチャ・API リファレンス・コーディング規約

### 開発者向けガイド

- [Plugin API](./PLUGIN-API.ja.md) — プラグイン / テーマ API の境界定義
- [ソースコメントのロケール切替](docs/ja/development/comment-translation.md) — `convert-comments.sh` でソースコメントの言語を切り替える
- [MCP サーバー (Laravel Boost)](docs/ja/development/mcp-laravel-boost.md) — VSCode / Cursor 向け AI 開発支援のセットアップ

---

## 🤝 コントリビュート

> **現状**: Dixlase は初期開発期にあり、**外部からのコード Pull Request
> は受け付けていません**。コントリビューターライセンス契約 (CLA) は
> 現在 **正式法務レビュー中** で、確定後にコード Pull Request の受付を
> 開始します。

それまでも **GitHub Issues** でのバグ報告・機能提案、および
**GitHub Discussions** での質問は歓迎します。現時点のコントリビューション
スコープは [CONTRIBUTING.ja.md](./CONTRIBUTING.ja.md) をご覧ください。

CLA 確定後、コントリビューションは [コピーライトポリシー](./COPYRIGHT-POLICY.ja.md)
と Dixlase CLA(全文は [CLA.ja.md](./CLA.ja.md) で公開)の対象となります。
受付開始時には [CONTRIBUTING.ja.md](./CONTRIBUTING.ja.md) を PR ベースの
コントリビューションガイドへ全面差し替えます。

---

## 📖 ガバナンス・ポリシー

- [コントリビューションガイド](./CONTRIBUTING.ja.md) — コントリビュート方法
- [コピーライトポリシー](./COPYRIGHT-POLICY.ja.md) — デュアルライセンス方針と CLA モデルの概要
- [コントリビューターライセンス契約](./CLA.ja.md) — 策定中。外部コントリビューション受付の再開時に全文を公開
- [セキュリティポリシー](./SECURITY.ja.md) — 脆弱性の報告

---

## 📜 ライセンス

Dixlase CMS は**デュアルライセンス**で配布されています:

- **オープンソースライセンス**: [GNU Affero General Public License v3](./LICENSE) および Dixlase プラグイン・テーマ例外条項([LICENSE-EXCEPTIONS.ja](./LICENSE-EXCEPTIONS.ja))
- **商用ライセンス**: AGPL v3 の遵守が現実的でないユースケース(クローズドソース SaaS での改変版配布など)向けに、別途商用ライセンスの提供を予定しています。

**現時点では商用ライセンスはまだ提供しておりません。**  
(雛形のみ [LICENSE-COMMERCIAL.ja](./LICENSE-COMMERCIAL.ja) に Draft として置いています)。  
提供開始時期や条件に関するお問い合わせは **info@dixlase.org** までご連絡ください。

ファイル全体の構成は [NOTICE.ja](./NOTICE.ja) にまとめています([English](./NOTICE))。

### プラグイン・テーマについて

[Plugin API](./PLUGIN-API.ja.md) のみを通じて Dixlase CMS と連携するプラグイン・テーマは派生物とはみなされず、**プロプライエタリライセンスを含む任意のライセンスで配布可能**です。詳細は [LICENSE-EXCEPTIONS.ja](./LICENSE-EXCEPTIONS.ja) をご確認ください。

### ソースコードの提供(AGPL §13)

Dixlase CMS をサーバーで運用し、ネットワーク経由でユーザーに提供する場合、AGPL §13 により、実行中のバージョンのソースコードをユーザーが取得できる必要があります。管理画面フッターの「Source」リンクがユーザーからアクセス可能であることをご確認ください。公開 URL は環境変数 `DIXLASE_SOURCE_URL` で指定できます。

---

## 🛡️ ビジョン

> **安全性・公平性・透明性** を基盤に、  
> **拡張性** と **持続可能性** を育み、  
> 誰もが安心して自由に使える CMS を提供することを目指します。
