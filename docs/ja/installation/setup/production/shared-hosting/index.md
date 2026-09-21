# 共有レンタルサーバー

共有レンタルサーバーにDixlaseをデプロイします。ご利用のホスティング会社のガイドを選択してください。

## 前提条件

- PHP 8.3 以上
- MySQL 8.0 / MariaDB 10.6 以上
- SSHアクセス（推奨）またはFTPアクセス
- Composer（サーバーにインストール済み、またはローカルで実行）

## ホスティング会社別ガイド

### 日本

- [Xserver](xserver.md) — 日本で最も人気のあるレンタルサーバー
- [ConoHa WING](conoha.md) — 高性能レンタルサーバー
- [さくらインターネット](sakura.md) — 老舗の日本のホスティング
- [ロリポップ！](lolipop.md) — 低価格レンタルサーバー

### 海外

- [Hostinger](hostinger.md) — 世界最大級のホスティングサービス（hPanel）
- [SiteGround](siteground.md) — 高性能ホスティング（Site Toolsパネル）
- [A2 Hosting](a2-hosting.md) — SSH・Composer対応の開発者向けホスティング
- [cPanelホスティング](cpanel.md) — cPanelベースのホスティング汎用ガイド

## 一般的な手順

上記にないホスティング会社をご利用の場合は、以下の一般的な手順に従ってください：

1. ホスティングのコントロールパネルでMySQLデータベースとユーザーを作成
2. FTPまたはSSHでDixlaseファイルをアップロード
3. `composer install` を実行（SSH経由、またはアップロード前にローカルで）
4. データベース認証情報で `.env` を設定
5. ドメインを `public/` ディレクトリに向ける
6. サイトにアクセスして[インストールウィザード](../../wizard.md)を実行
