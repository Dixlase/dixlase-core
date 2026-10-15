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

4. **タグを push する直前に、もう一度確認します。**

   ```bash
   php scripts/check-bundled-theme.php
   ```

   形式的な手順ではありません。lock をマージしてからタグを push するまでの隙間で足ります。2026-10-13 には、コアの `v0.1.10` を公開する **53 秒前**にテーマが `v0.4.0` を公開しました。確認はその 30 分前に通っており、その時点では正しかったのです。その結果 `v0.1.10` は資材が 1 つも付かない状態で公開されました(#557)。

## テーマとコアを続けてリリースするとき

**テーマのリリース → コアの lock を更新する Pull Request → コアのリリース**、の順です。逆にすると構造的に負けます。lock はまだ存在しないテーマのリリースを指せないので、コアの lock を決めたあとに出たテーマは、必ずその lock より先を行きます。

## どこで止まり、どこでは警告だけなのか

| 場所 | 挙動 |
| --- | --- |
| `VERSION` を変える Pull Request | **止まります。** `release-preflight.yml` が出す `Bundled Theme Lock` のチェックで、`main` の必須チェックです |
| 公開されたリリースでの `release.yml` | **警告を出し、それでもビルドします** |
| `workflow_dispatch` での `release.yml` | 失敗します(`allow_outdated_theme` を指定した場合を除く) |

リリースのビルドは以前ここで失敗していましたが、それは割に合わない判断でした。このビルドの引き金は `release: published` なので、失敗できる時点でリリースはすでに公開されています。そしてリリースの資材が添付されるのは公開される瞬間だけなので、失敗すると、二度と資材を付けられない公開済みのリリースが残ります。`releases/latest/download/dixlase-core.zip` は 404 を返し、ワンライナーのインストーラは Composer 経路に切り替わりますが、これは Composer が無いホストでは失敗します。1 つ古いテーマはインストールできますし、`dls:theme:update` で動かせます。そこで、止めるのはマージの前に行い、ビルドでは警告だけにしました(#557)。

この確認は新規インストールだけが対象です。コア更新はインストール済みのテーマを変更しません。テーマの更新は `dls:theme:update` で行います。
