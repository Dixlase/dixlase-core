# テストスイートがどのデータベースで走るか

答えは 2 つあり、どちらも意図したものです。

英語版は [`docs/development/test-database.md`](../../development/test-database.md) にあります。

---

| どこ | ドライバ | 決めているもの |
| --- | --- | --- |
| 手元の実行（`php artisan test`） | sqlite（インメモリ） | `phpunit.xml` の `<env name="DB_CONNECTION" value="sqlite" force="true"/>` |
| CI の `Core Tests` と `Plugin Tests` | サービスコンテナの MySQL 8.0 | `.github/workflows/test.yml` のステップの `env:` と `DLS_TESTS_ALLOW_NON_SQLITE: 1` |
| CI の `Migration Test` | サービスコンテナの MySQL 8.0 | スイートではなく `migrate` を走らせるジョブです |

**つまりテストは両方で通らなければなりません。** テストの中にドライバ依存のものを書かないでください。`PRAGMA defer_foreign_keys` を使ったテストは手元では毎回通り、CI でだけ落ちました（#515）。これがその代償の形です。

## CI が MySQL を使う理由

本番は MySQL / MariaDB で動いています。スイートをそれに対して走らせているのは CI のこのジョブだけなので、照合順序・strict モード・DDL での暗黙のコミット・遅延した外部キーの挙動といった 2 つのエンジンの違いは、サイトではなくここで現れます。

CI のデータベースはジョブの間だけ存在するサービスコンテナです。だからそこでは `RefreshDatabase` を許容できます。毎回の `setUp()` でスキーマを作り直しますが、そのコンテナには失うものが何もありません。

## `phpunit.xml` だけでは決められない理由

`force="true"` が勝てるのは `.env` に対してだけです。Dotenv は immutable で、すでに設定されているキーを飛ばすため、`force` が無ければ `.env` の `DB_CONNECTION=mysql` が実行まで残ってしまいます。

一方、**実プロセスの環境変数には勝てません。** phpunit は `<env>` の値を `putenv()` と `$_ENV` に書きますが、Laravel の `Illuminate\Support\Env` は `$_SERVER` を先に読み、CLI SAPI はプロセスの環境変数を `$_SERVER` に入れます。したがって export された `DB_CONNECTION`、コンテナの `environment:`、ワークフローのステップの `env:` は、`phpunit.xml` のすべての行に、**黙って**勝ちます。リポジトリ中のどのファイルも sqlite だと書いているのに、CI のスイートが何か月も MySQL で走っていたのはこれが原因です（#541）。

## `tests/TestCase.php` の 2 つの関門

どちらも実データを持つドライバの一覧（`FORBIDDEN_TEST_DRIVERS`）を見ており、どちらも `RefreshDatabase` が何かをマイグレートする前に走ります。

1. **アプリの起動前** — `guardAgainstNonSqliteConnection()` が環境変数を読みます。読む順序は `env()` と同じなので、アプリが使うドライバと違うものを報告することはありません。
2. **起動後の設定** — `guardAgainstNonSqliteDefaultConnection()` が `refreshApplication()` から `config('database.default')` を読みます。Laravel はこれを `setUpTraits()` より前に呼びます。こちらが本物の関門です。`config:cache` が残した `bootstrap/cache/config.php` や、環境変数を介さず `config/database.php` に書かれた既定は、データベースには届きますが環境変数には現れません。

`tests/Unit/TestSuiteDatabaseDriverTest.php` が、反対側からこの不変条件を検査します。許可していない実行は sqlite であること、そして関門が `env()` と同じ値を見ていることです。

## 手元で sqlite 以外に対して走らせる場合

`DLS_TESTS_ALLOW_NON_SQLITE=1` を設定します。使い捨ての、自分が持っているデータベースに対してだけにしてください。

```bash
DLS_TESTS_ALLOW_NON_SQLITE=1 php artisan test --testsuite=Feature
```

残したいデータが入っているコンテナに対しては決して実行しないでください。`RefreshDatabase` は毎回の `setUp()` で、使っている接続のスキーマを作り直します。

関連: [イシューの書き方](issues.md) · `phpunit.xml` · `.github/workflows/test.yml`
