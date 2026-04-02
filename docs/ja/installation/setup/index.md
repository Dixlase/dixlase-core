# セットアップガイド

環境に合ったセットアップ方法を選択してください。

## Dixlaseの入手

まず、Dixlaseのソースコードを入手してください。利用可能な方法は[ダウンロード・入手方法](download/index.md)をご覧ください。

## 開発環境

開発・テスト用のローカル環境を構築します。

- [Docker](development/docker.md) — 推奨。全サービスがコンテナで事前設定済み
- [Laravel Herd](development/laravel-herd.md) — Mac/Windows向けワンクリックPHP + Nginx
- [XAMPP / MAMP](development/xampp-mamp.md) — クラシックなGUIベースのローカルサーバー
- [Valet](development/valet.md) — Mac向け軽量バックグラウンドサーバー
- [手動セットアップ](development/manual.md) — PHP、Composer、データベースを個別にインストール

## 本番環境

Dixlaseをライブサーバーにデプロイします。

- [VPS / クラウドサーバー](production/vps-cloud.md) — 自前サーバーでNginx + PHP-FPM
- [共有レンタルサーバー](production/shared-hosting.md) — 共有ホスティングにデプロイ
- [Docker本番環境](production/docker-production.md) — コンテナで本番運用
- [PaaS](production/paas.md) — Laravel ForgeやPloiなどのマネージドプラットフォーム
