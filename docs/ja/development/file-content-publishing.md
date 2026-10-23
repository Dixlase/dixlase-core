# ファイル保存のコンテンツの公開

## 概要

コンテンツは、データベースではなくファイルに保存できます。何もしなければ、サーバー上のそのファイルを書き換えるだけで、監査ログもリビジョンも残らないまま公開サイトが変わります。コアは共通の仕組みを一つ用意し、ファイル保存のコンテンツの持ち主(コアのフロントページと、ファイル保存を提供する拡張)が、どれも同じやり方で公開できるようにしています。

データベースが常に **公開済みのコピー** を持ちます。ファイルは **作業用のコピー** です。

## 公開のしかた

持ち主ごとに、自分の設定画面で決めます(コアのフロントページは *フロントページ → 設定*)。既定は `admin` です。

| 公開のしかた | サイトに表示されるもの | ファイルを変更したとき |
|---|---|---|
| `admin`(既定) | データベースの公開済みのコピー | 監査ログに 1 回記録され、編集画面に差分つきで表示される。編集の権限がある人が **公開する** を押すと反映される |
| `file_save` | ファイル | すぐに公開済みのコピーとして取り込まれ、`auto` のリビジョンと監査ログに記録される |

2 つの値は `App\Enums\ContentPublishMode` にあります。

## 対象外のもの

- **Blade。** Blade のコンテンツは作成・編集・復元ができず、保存済みの Blade の本文も表示のときに実行されないので、この仕組みでは扱いません。
- **翻訳。** 翻訳のオーバーレイはファイルより優先されます。比較と **公開する** は、元の言語の本文が対象です。

## 拡張で使う

1. 新しいマイグレーションで、ハッシュの列を足します。

   ```php
   use App\Support\Content\FileContentColumns;

   Schema::table('plg_example_pages', function (Blueprint $table) {
       FileContentColumns::add($table);
   });
   ```

2. モデルに `App\Contracts\Content\PublishesFileContent` を実装します。ファイル保存かどうか、自分の設定から読んだ公開のしかた、部分とそれぞれの公開済みのコピーを持つ列、部分ごとのファイルのパスとディスク、監査ログのアクション名の接頭辞、ファイル保存のレコードの問い合わせを返します。公開をリビジョンに残すには `App\Contracts\Revisionable` も実装します。

3. 定期スキャンとダッシュボードが見られるよう、モデルを登録します。

   ```php
   app(\App\Services\Content\FileContentRegistry::class)->register(ExamplePage::class);
   ```

4. 表示では、ファイルを直接読まずに `FileContentPublisher::resolve($model, $part)` を通します。先にファイルを確かめるので、変更は、それを最初に見た読み込みで記録されます。

5. 管理画面の保存でデータベースとファイルを一緒に書いたあとは、`FileContentPublisher::markSaved($model)` を呼びます。リビジョンの復元のあとは `FileContentPublisher::writeBack($model)` を呼びます。復元はデータベースしか変えないためです。

6. 編集画面に、公開待ちの変更と「公開する」ボタンを出します。

   ```blade
   <x-content.file-changes
       :changes="$fileChanges"
       :publishUrl="route('example.pages.publish-file', $page)"
       :canPublish="$canEdit"
   />
   ```

   `$fileChanges` は、`FileContentPublisher::pendingChanges()` の各部分を `RevisionDiffPresenter::buildSideBySide($change['published'], $change['file'])` に通したものです。公開のルートでは、編集画面と同じ権限を渡して `App\Actions\Content\PublishFileContentAction` を実行します。

## 検知

- **表示のとき。** `resolve()` は、コンテンツを読むたびにファイルと公開済みのコピーを比べます。ファイルは小さく、もともとリクエストのたびに読んでいました。
- **毎日。** cron が有効なら毎日動く `dls:integrity:scan --scheduled` が、登録されたすべてのレコードを確かめます。誰も見ていないコンテンツの変更も、ここで見つかります。ダッシュボードには、公開待ちの変更があるレコードの数が出ます。別の設定はありません。スキャンは cron の有無で動いたり止まったりします。

## 監査ログのアクション

| アクション | カテゴリ | 記録されるとき |
|---|---|---|
| `{prefix}.file_changed` | content(warning) | 管理画面の外での変更を最初に見たとき。`context.published` で、ファイルの保存で即時公開のモードが取り込んだかが分かる |
| `{prefix}.published_from_file` | content | 編集画面から変更を公開したとき |

公開のしかたの変更は、設定の変更(`settings_updated`、対象は持ち主の設定画面)として記録されます。
