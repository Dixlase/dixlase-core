# ファイル保存のコンテンツの公開

## 概要

コンテンツは、データベースではなくファイルに保存できます。何もしなければ、サーバー上のそのファイルを書き換えるだけで、監査ログもリビジョンも残らないまま公開サイトが変わります。コアは共通の仕組みを一つ用意し、ファイル保存のコンテンツの持ち主(コアのフロントページと、ファイル保存を提供する拡張)が、どれも同じやり方で公開できるようにしています。

データベースが常に **公開済みのコピー** を持ちます。ファイルは **作業用のコピー** です。

> **ファイルの保存で即時公開できるのは、開発環境（`APP_ENV=local`）だけです。**
> ステージングや本番では、設定にかかわらず、ファイルを変えただけでは反映されません。サーバー上で書き換えたときも、デプロイで届いたときも同じです。
> 反映するには公開の処理が必要です。編集画面で差分を読み、**公開する** を押してください。それまでサイトは公開済みのコピーを出し続け、管理画面の上のバナーに公開待ちとして数えられます。

## 公開のしかた

持ち主ごとに、自分の設定画面で決めます(コアのフロントページは *フロントページ → 設定*)。既定は `admin` です。

| 公開のしかた | サイトに表示されるもの | ファイルを変更したとき |
|---|---|---|
| `admin`(既定) | データベースの公開済みのコピー | 監査ログに 1 回記録され、編集画面に差分つきで表示される。編集の権限がある人が **公開する** を押すと反映される |
| `file_save` | ファイル。**開発環境（`APP_ENV=local`）のときだけ** | すべての部分をすぐに公開済みのコピーとして取り込み、`auto` のリビジョンと監査ログに記録する。ほかの環境ではこのモードは効かず、`admin` と同じく **公開する** を待つ |

2 つの値は `App\Enums\ContentPublishMode` にあります。

### ファイルの保存で即時公開は、開発環境でだけ効く

ファイルの変更を、誰も公開しないまま取り込むのは、開発のための便利な機能です。ステージングや本番では、ファイルを書き込める者がサイトを変えられる、という意味にしかなりません。`storage/` はアプリが書き込む場所なので、コードのディレクトリは書けなくても書けるからです。そこで次のようにしています（#786）。

- `FileContentPublisher::fileSaveModeAvailable()` は、`APP_ENV` が `local` のときだけ true を返します。判定は「`local` か」であり、「`production` でないか」ではありません。ステージングや打ち間違えた値では閉じたままです。
- `FileContentPublisher::effectivePublishMode($model)` は持ち主の設定値を返します。ただし、`file_save` が使えない環境では `admin` を返します。取り込む部分、`FileContentRegistry::usesFileSaveMode()`、監査ログなど、この仕組みはすべて実際に効くモードに従います。保存された設定値は選んだまま残るので、サイトを手元の環境に写せばまた効きます。
- 開発環境では、スクリプトを含められる部分も含めて、変わった部分をすべて取り込みます。そこにあるファイルは開発者自身のものだからです。ほかの環境では何も取り込まず、変更は管理画面の「公開待ちの変更」のバナーの件数に入ります。手元で編集したファイルをデプロイすると、受け取ったサイトではこれで気づけます。
- このモードを選べる設定画面には、このサイトでどちらが効くかを出します（`common.content_publish_mode.in_effect_local` / `not_in_effect`）。

### スクリプトを含められる部分

#786 より前は、ファイルの保存で即時公開はどこでも効き、スクリプトを含められない部分だけを取り込んでいました（#744）。どの部分がそれにあたるかは今も持ち主が決め、`FileContentPublisher::scriptCapableParts()` が答えます。ただし、何が公開されるかにはもう影響しません。

- **何もしなければ**、部分の名前から決まります。`FileContentPublisher::DEFAULT_SCRIPT_CAPABLE_PARTS` が、慣例的な `custom_css` と `custom_js` を覆います。
- **`App\Contracts\Content\DeclaresScriptCapableParts` を実装**するのは、名前では分からない部分があるときです。多くは本文で、エディタの種類によって答えが変わります。任意のインターフェイスで、すでにある実装はそのまま動きます。

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

7. ダッシュボードがレコードを名前で並べ、その編集画面に飛べるよう、`App\Contracts\Content\ProvidesFileContentEditLink` を実装します。任意の Contract で、`PublishesFileContent` とは別です。実装しないと、レコードはモデル名とキーで、リンクなしで並びます。URL は見ている人に関係なく返してください。ダッシュボードは、ルートの `check.menu.access` ミドルウェアを通る人にだけリンクにします。

8. 拡張で公開のしかたを設定する画面に、スケジューラが動いていないことを出します。

   ```blade
   <x-content.scheduler-notice :running="$schedulerRunning" />
   ```

   `$schedulerRunning` は、コントローラーで `app(FileContentPublisher::class)->schedulerRunning()` から渡します。スケジューラが動いている間は何も出しません。

## 検知

- **表示のとき。** `resolve()` は、コンテンツを読むたびにファイルと公開済みのコピーを比べます。ファイルは小さく、もともとリクエストのたびに読んでいました。
- **毎日。** cron が有効なら毎日動く `dls:integrity:scan --scheduled` が、登録されたすべてのレコードを確かめます。誰も見ていないコンテンツの変更も、ここで見つかります。別の設定はありません。スキャンは cron の有無で動いたり止まったりします。

cron がないと、変更は、レコードを表示するページか編集画面を開いたときにしか見つかりません。スケジューラの動作の記録がないとき、ダッシュボードの項目と `<x-content.scheduler-notice>` がそのことを伝えます。

## 知らせ

- **ダッシュボード。** 公開待ちの変更がある間、サイトヘルスの項目にレコードが並びます（最初の 5 件と、残りの件数）。各行は `ProvidesFileContentEditLink` を通じて編集画面に飛びます。項目の見出しは、監査ログの `file_changed` の記録に飛びます。
- **バナーとメール。** 最初の変更を記録したとき、管理画面全体にバナーを出し、通知のメールを 1 通送ります。定期のセキュリティチェックと同じ仕組みを使います。バナーは公開待ちがなくなるまで、その時点の件数を出して残ります。残っている間、メールはもう送りません。見えるのは、フロントページの編集画面を開ける人です。ハッシュを動かすもの（変更の記録、公開、管理画面での保存、復元、毎日のスキャン）がすべてバナーを更新するので、スケジュールではなくレコードの状態に従います。

## コマンドで公開する

`dls:content:publish-files` は、**公開する** ボタンのコマンド版です。コンテンツのファイルがデプロイで届くサイト向けです（#805）。`FileContentRegistry` に登録されたモデルをすべて見るので、拡張機能のレコードも、自前のコードなしで対象になります。

```bash
php artisan dls:content:publish-files                    # 公開待ちを一覧にする。公開はしない
php artisan dls:content:publish-files --publish          # 公開待ちをすべて公開する
php artisan dls:content:publish-files --fail-if-pending  # 公開待ちがあれば 0 以外で終わる
php artisan dls:content:publish-files --model=FrontPage --id=1 --publish
```

- まず定期スキャンと同じように各レコードを確かめます。誰も見ていない変更は、一覧にしたり公開したりする前に記録されます（`file_changed`、`detected_by: scan`）。
- `--publish` は、各レコードに `PublishFileContentAction` を動かします。ボタンと同じく、`manual` のリビジョンと `published_from_file` の監査ログが残ります。実行者は `console` です。
- `--model` には登録済みのクラス名を、名前空間つきでもなしでも渡せます。`--id` は `--model` と一緒に使います。
- `--fail-if-pending` を付けると、誰も公開していないコンテンツがあるときにデプロイを止められます。`--publish` と一緒なら、公開に失敗したときだけ失敗します。

> **本番では `--publish` を使わないでください。** 公開待ちを、中身を見せずにすべて公開します。サーバー上で誰かが書き換えたファイルも含まれます。使ってよいのは、すべてのファイルが自分のリポジトリから来ていると分かっている環境（開発環境やステージングの複製）だけです。
> 本番では一覧を出し、どのレコードも自分がデプロイしたものかを確かめてから、編集画面で差分を読んで公開します。デプロイしていないレコードがあれば、サーバー上でファイルが書き換えられたということです。公開せず、その `file_changed` の監査ログを確かめてください。

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
| `publish_mode` | 実際に効いているモード。`admin`、または開発環境での `file_save` |
| `selected_publish_mode` | 持ち主が選んだモード。開発環境以外では、`admin` が効いていても `file_save` のことがある |
| `parts` | ファイルが公開済みのコピーと異なる部分 |
| `held` | 取り込まず、公開を待っている部分。ファイルの保存で即時公開が効いていなければ変わった部分すべて、効いていれば空 |
| `published` | 変更全体が反映されたときだけ true |

`published_from_file` は `parts` と `published_hash` を持ちます。`published_hash` はそのレコードが現在公開しているハッシュで、この承認が応えた `file_changed` エントリの `file_hash` と一致します。承認した人はこのエントリの実行者で、公開は `manual` のリビジョンとしても記録されます。

公開のしかたの変更は、設定の変更(`settings_updated`、対象は持ち主の設定画面)として記録されます。
