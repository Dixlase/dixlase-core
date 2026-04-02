# ZIPダウンロード

公式サイトまたはGitHubからDixlaseをZIPアーカイブでダウンロードします。

## GitHubから

1. [Dixlaseリリースページ](https://github.com/Dixlase/dixlase/releases)にアクセス
2. 最新リリースのZIPファイルをダウンロード
3. アーカイブを任意の場所に展開

## 公式サイトから

1. Dixlase公式サイトにアクセス
2. ダウンロードページに移動
3. 最新バージョンをダウンロード

## ダウンロード後

```bash
# 展開してディレクトリに移動
unzip dixlase-*.zip
cd dixlase

# PHP依存パッケージをインストール
composer install
```

その後、[開発環境](../development/index.md)または[本番環境](../production/index.md)のセットアップに進んでください。
