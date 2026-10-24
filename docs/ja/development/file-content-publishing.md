# ファイル保存のコンテンツの公開

## 概要

コンテンツは、データベースではなくファイルに保存できます。何もしなければ、サーバー上のそのファイルを書き換えるだけで、監査ログもリビジョンも残らないまま公開サイトが変わります。コアは共通の仕組みを一つ用意し、ファイル保存のコンテンツの持ち主(コアのフロントページと、ファイル保存を提供する拡張)が、どれも同じやり方で公開できるようにしています。

データベースが常に **公開済みのコピー** を持ちます。ファイルは **作業用のコピー** です。

## 公開のしかた

持ち主ごとに、自分の設定画面で決めます(コアのフロントページは *フロントページ → 設定*)。既定は `admin` です。

| 公開のしかた | サイトに表示されるもの | ファイルを変更したとき |
|---|---|---|
| `admin`(既定) | データベースの公開済みのコピー | 監査ログに 1 回記録され、編集画面に差分つきで表示される。編集の権限がある人が **公開する** を押すと反映される |
| `file_save` | ファイル。ただし**スクリプトを含められる部分は除く** | すぐに公開済みのコピーとして取り込まれ、`auto` のリビジョンと監査ログに記録される。スクリプトを含められる部分は取り込まれず、`admin` と同じく **公開する** を待つ |

2 つの値は `App\Enums\ContentPublishMode` にあります。

### スクリプトを含められる部分

管理画面では、サイトにスクリプトを出せるのは管理者以上に限っています。そのままでは、ファイルの保存で即時公開のモードが、その制限を通らない 2 つめの入り口になります。`storage/` はアプリが書き込む場所なので、コードのディレクトリは書けなくても書けるからです。そこで `file_save` でも、取り込むのはスクリプトを含められない部分だけにしています。残りは公開を待ち、監査ログの `context.held` にその名前が残ります。

どの部分がそれにあたるかは、持ち主が決めます。

- **何もしなければ**、部分の名前から決まります。`FileContentPublisher::DEFAULT_SCRIPT_CAPABLE_PARTS` が、慣例的な `custom_css` と `custom_js` を覆います。
- **`App\Contracts\Content\DeclaresScriptCapableParts` を実装**するのは、名前では分からない部分があるときです。多くは本文で、エディタの種類によって答えが変わります。これは独立した任意のインターフェイスなので、`PublishesFileContent` は変わっておらず、すでにある実装はそのまま動きます。空の配列を返すことは「このレコードのどの部分もスクリプトを含められない」という表明で、名前による既定を無効にします。

コアのフロントページは、HTML エディタのときに本文をスクリプト可能として宣言します。そこでは `<script>` は著者が書く内容の一部だからです。Markdown や GUI の本文には書けないので、`file_save` では従来どおりすぐ公開されます。

公開のしかたが `file_save` の本番サイトには、ダッシュボードで警告を出します。設定のどこにも「公開前の確認をやめた」と書かれていないためです。

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
| `{prefix}.file_changed` | content(warning) | 管理画面の外での変更を最初に見たとき |
| `{prefix}.published_from_file` | content | 編集画面から変更を公開したとき |

### `file_changed` が持つもの

| キー | 値 |
|---|---|
| `source` | 常に `outside_admin`。このエントリの実行者がシステムなのは、アプリの外でファイルを書いた人をアプリが知りようがないからであって、Dixlase が変更したからではない。監査ログの画面でも実行者の横にその旨を出す |
| `detected_by` | `render`（ページや編集画面を表示したとき）か `scan`（定期の整合性スキャン）。cron が無効なら `render` しか起こらない |
| `published_hash` | この変更の**前に**公開されていた版のハッシュ |
| `file_hash` | **見つけたときの**ファイルのハッシュ |
| `publish_mode` | `admin` か `file_save` |
| `parts` | ファイルが公開済みのコピーと異なる部分 |
| `held` | 確認のために取り込まなかった部分（上記参照）。変更全体が反映されたときは空 |
| `published` | 変更全体が反映されたときだけ true |

`published_from_file` は `parts` と `published_hash` を持ちます。`published_hash` はそのレコードが現在公開しているハッシュで、この承認が応えた `file_changed` エントリの `file_hash` と一致します。承認した人はこのエントリの実行者で、公開は `manual` のリビジョンとしても記録されます。

公開のしかたの変更は、設定の変更(`settings_updated`、対象は持ち主の設定画面)として記録されます。
