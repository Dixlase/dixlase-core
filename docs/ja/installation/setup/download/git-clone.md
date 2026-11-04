# Git Clone

Dixlaseリポジトリをクローンして、バージョン管理付きの最新ソースコードを取得します。最新の変更を追い続けたい開発者におすすめの方法です。

## 前提条件

- [Git](https://git-scm.com/) がシステムにインストールされていること
- [Composer](https://getcomposer.org/) 2.x

## 手順

```bash
# リポジトリをクローン
git clone https://github.com/Dixlase/dixlase-core.git dixlase
cd dixlase

# PHP依存パッケージをインストール
composer install
```

## 特定バージョンのクローン

特定のリリースをクローンする場合は、そのタグを指定します（一覧は[リリースページ](https://github.com/Dixlase/dixlase-core/releases)）：

```bash
git clone --branch v0.2.2 https://github.com/Dixlase/dixlase-core.git dixlase
cd dixlase
composer install
```

## 更新

`main` には常に最新のリリースが入っています。取得して依存パッケージを更新します：

```bash
git pull origin main
composer install
```

## 次のステップ

クローン後、[開発環境](../development/index.md)または[本番環境](../production/index.md)のセットアップに進んでください。
