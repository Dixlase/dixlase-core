# コアのリリース

コアのリリースにタグを付ける前に必要な手順です。リリース ZIP そのものは、GitHub のリリースを公開したときに `.github/workflows/release.yml` が作ります。

英語版は [`docs/development/releasing.md`](../../development/releasing.md) にあります。

---

## テーマの最新リリースを同梱する

リリース ZIP、ワンライナーインストーラ、`composer create-project` は、どれも `composer.lock` が固定している公式テーマ(`dixlase/dixlase-onepage`)をインストールします。テーマがリリースを出しても、この lock は自動では動きません。コアをリリースする前に、毎回手で更新します(#517)。

1. lock をテーマの最新リリースに更新する。

   ```bash
   composer update dixlase/dixlase-onepage
   ```

   `composer.json` はテーマをリリースの範囲(`>=0.2 <1.0`)で要求しているので、0.x のリリースならどれでも入ります。テーマが 1.0 になったときは、先に範囲を上げます。

2. 結果を確かめる。

   ```bash
   php scripts/check-bundled-theme.php
   ```

   `composer.lock` がテーマの最新リリースを固定していなければ、終了コード 1 で止まります。GitHub API の匿名の利用制限を避けるには `GITHUB_TOKEN` を設定します。

3. `composer.json` と `composer.lock` を Pull Request でコミットし(`No issue: dependencies`)、マージしてからリリースにタグを付ける。

`release.yml` も ZIP を作る前に同じ確認をし、lock が遅れていれば失敗します。そのため、古いテーマを同梱した ZIP は公開されません。古いタグを手動で作り直すとき(`workflow_dispatch`)は、`allow_outdated_theme` の入力で確認を省けます。

この確認は新規インストールだけが対象です。コア更新はインストール済みのテーマを変更しません。テーマの更新は `dls:theme:update` で行います。
