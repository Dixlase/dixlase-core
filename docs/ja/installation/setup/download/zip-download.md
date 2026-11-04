# ZIPダウンロード

公式サイトまたはGitHubからDixlaseをZIPアーカイブでダウンロードします。

## GitHubから

1. [Dixlaseリリースページ](https://github.com/Dixlase/dixlase-core/releases)にアクセス
2. 最新リリースの `dixlase-core.zip` をダウンロード（同じファイルが `dixlase-vX.Y.Z.zip` としても添付されています。照合用の `checksums.sha256` もあります）
3. アーカイブを任意の場所に展開

## 公式サイトから

1. Dixlase公式サイトにアクセス
2. ダウンロードページに移動
3. 最新バージョンをダウンロード

## ダウンロード後

```bash
# 展開してディレクトリに移動（アーカイブには dixlase-vX.Y.Z というフォルダが 1 つ入っています）
unzip dixlase-core.zip
cd dixlase-v*
```

リリースの ZIP には `vendor/` とビルド済みのフロントエンドのアセットが含まれているので、`composer install` も `npm run build` も不要です。

その後、[開発環境](../development/index.md)または[本番環境](../production/index.md)のセットアップに進んでください。
