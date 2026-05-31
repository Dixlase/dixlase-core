# 2026-05-29 — Brand 本番 MySQL テーブル全 DROP 事故

> 英語版は [`docs/incidents/2026-05-29-mysql-tables-dropped.md`](../../incidents/2026-05-29-mysql-tables-dropped.md) を参照。

## サマリ

2026-05-29、Claude Code セッションが Brand サイトの **本番** PHP コンテナに対して
`php artisan test` を実行した。テスト起動経路で `RefreshDatabase` がロードされ、
本番 MySQL に対して `migrate:fresh` が走り、Brand 本番テーブル全件が `DROP TABLE` された。
Time Machine から復旧したため恒久的なデータ喪失は無いが、復旧作業のため数時間
サービスが止まった。

本ドキュメントは、時系列・本番への到達経路・既存の安全装置が一つも機能しなかった
理由・実装済みの再発防止策・残対策をまとめる。

## 時系列(UTC)

| 時刻     | イベント                                                                                                                                                              |
| -------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 16:30:04 | セッション `4eabe326` が `docker exec dixlase-brand-app php artisan test --compact themes/DixlaseOnePage/tests/Unit/DixlaseOnePageSettingsLocalizationTest.php` を実行 |
| 16:30:24 | 同セッションが `--filter` 付きで再実行                                                                                                                                |
| 16:30:41 | 同セッションが Inquiry プラグインのテストを path-direct 形式で実行                                                                                                    |
| 16:30:48 | `dls_migrations.created_at` が更新 — DROP サイクル完了                                                                                                               |
| (後刻)   | オペレータが空スキーマを発見、Time Machine から MySQL 生ファイルを復旧、サイトを復活                                                                                  |

## 連鎖の機序

1. **`php artisan test <path>` を path-direct 実行**。引数はテストファイルの相対パス、`--testsuite` 名ではない。
2. **`phpunit.xml` の `<env name="DB_CONNECTION" value="sqlite"/>` が適用されなかった**。`force="true"` が付与されていない `<env>` は「値が未設定なら set」だけで、既に設定されていれば上書きしない。本番コンテナの `.env` は既に `DB_CONNECTION=mysql` を export 済みのため、既存の値が勝った。
3. **`RefreshDatabase` が実行時の active connection を `mysql` として解決**。`phpunit.xml` のオーバーライドが silent に無視された結果、trait は通常運用とまったく同じ経路で本番 DB に繋がった。
4. **`migrate:fresh` が本番で実行**。`RefreshDatabase::setUp()` は in-memory マーカーが無ければ `migrate:fresh` を呼ぶ仕様で、今回はプロセス初回テストだったため確実に発火した。
5. **Brand 本番テーブル全件 DROP TABLE → 空再生成**。

## なぜ既存の安全装置が機能しなかったか

- **PHPUnit の `<env>` セマンティクスは "未設定なら set" がデフォルトで "force" ではない**。文字通り根本原因はこれ。`force="true"` 一つの欠如で override が全て silent に無効化された。
- **Themes のテストパスは `phpunit.xml` の `<testsuites>` に未登録**。`themes/DixlaseOnePage/tests/...` はスイート定義に含まれておらず、path-direct でしか実行できなかった。path-direct 実行は testsuite レベルの `<php>` / `<env>` を suite 名指定実行とは異なる扱いで評価するため、override がさらに弱められた可能性が高い。
- **本番コンテナに Composer 依存全部 (phpunit 含む) が入っている**。「テストを走らせ得るコード」と「本番データに対して走るコード」が物理的に分離していなかった。
- **DB 側の権限分離が無い**。Brand アプリケーションの runtime MySQL ユーザは migration ユーザと同じ `DROP TABLE` 権限を持っていた。誤配信された一発の artisan に DB 側で push back する仕組みが無かった。
- **テーブル数のテレメトリ無し**。DROP は silent。検知はオペレータが UI 上の症状に気付くまで遅延した。

## 実装済み対策

| # | 場所                                                                                                  | 内容                                                                                                                                                                                                                                                       | ステータス                                                                                                                                                                                                                                          |
| - | ----------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 | `dixlase-core` `tests/TestCase.php` + `phpunit.xml`                                                   | `setUp()` 内の static ガードで `DB_CONNECTION` が `mysql`/`mariadb`/`pgsql`/`sqlsrv`/`oci` のいずれかなら `RuntimeException` を投げる(脱出口: `DLS_TESTS_ALLOW_NON_SQLITE=1`)。`<env force="true">` を全ての test env 行に付与。`themes/*/tests` 用に `Themes` testsuite を登録。 | コミット [`c809f073`](https://github.com/Dixlase/dixlase-core/commit/c809f073) で `main` および `v0.1.0` に反映済み。PR [#44](https://github.com/Dixlase/dixlase-core/pull/44) はダイレクト push の結果としてクローズされた(内容は既に main 入り)。 |
| 2 | `dixlase-docker-installer` `.claude/settings.json` + `.claude/hooks/block-destructive-container-ops.sh` | `PreToolUse:Bash` フックで `docker (compose) exec <non-dev-container> ... (php artisan test|migrate:fresh|migrate:reset|migrate:refresh|db:wipe|vendor/bin/phpunit|composer test)` を全て deny。dev/sandbox コンテナは allow-list で除外。                | コミット [`77f776b`](https://github.com/Dixlase/dixlase-docker-installer/commit/77f776b) で `main` に反映済み。検証マトリクス 18/18 pass。                                                                                                          |
| 3 | `plugin-dixlase-core-devkit` / `plugin-dixlase-devkit` の shared rules                                | `Test Execution Safety` セクションを共有コーディングルールに追加。`dls:claude:setup` で全プラグインの `CLAUDE.md` に伝播。                                                                                                                                          | オープン PR: [plugin-dixlase-core-devkit#1](https://github.com/Dixlase/plugin-dixlase-core-devkit/pull/1), [plugin-dixlase-devkit#3](https://github.com/Dixlase/plugin-dixlase-devkit/pull/3)。マージ待ち。                                          |

## 残対策

詳細は [`.claude/plans/test-incident-followup.md`](../../../.claude/plans/test-incident-followup.md) (gitignored 引き継ぎメモ)。推奨着手順:

| 順 | タスク                                                                | 効果                                                       |
| -- | --------------------------------------------------------------------- | ---------------------------------------------------------- |
| 1  | ~~**G**: Claude Code PreToolUse フック~~                              | 完了(上記対策 2 を参照)                                  |
| 2  | ~~**J**: 本ドキュメント~~                                             | 完了                                                       |
| 3  | **B**: DB ユーザー権限分離                                            | テスト以外の経路にも効く最深防御                           |
| 4  | **I**: アプリレベル定期バックアップ                                   | Time Machine 非依存の復旧保険                              |
| 5  | **C**: 本番コンテナイメージから phpunit / テストランナーを除外        | 物理分離 — 火種そのものが存在しなくなる                    |
| 6  | **H**: テーブル数の急減アラート                                       | 早期検知                                                   |

## 学んだこと

- **単一の安全装置を過信しない**。本プロジェクトが事前に持っていた 5 層 (CLAUDE.md ルール / `phpunit.xml` `<env>` / `RefreshDatabase` 規約 / dev・prod コンテナ分離 / ホスト側 Time Machine) のうち、最初の 4 つは全て失敗した。復旧できたのは 6 つ目の Time Machine のおかげで、プロジェクト内の安全装置が機能した結果ではない。
- **AI セッションは「正しそうなコマンド」を躊躇なく実行する**。人間オペレータなら `docker exec dixlase-brand-app php artisan test` を見て「待って、本番じゃないよね?」と一拍置く可能性があるが、AI の「どのコンテナが本番か」のメンタルモデルは信頼の置けるものではない。修正は AI の判断ではなく harness 層 (G) かそれより下に置く必要がある。
- **テストランナーを本番データと同じイメージに置いてはいけない**。`vendor/bin/phpunit` が本番コンテナにある限り、`tests/` 配下のパスから Laravel を起動するあらゆるコマンドが同じ事故を再発させ得る。対策 C はこのクラスの事故を完全に消す唯一の構造的修正。
- **DB 層での権限分離はあらゆるアプリ層バグから独立**。アプリやテストフレームワークが何を決めようとも、runtime user に `GRANT SELECT, INSERT, UPDATE, DELETE` だけ与えていれば `DROP TABLE` はワイヤープロトコルレベルで拒否される。対策 B は最も汎用的な防御。

## 参考

- `phpunit.xml` `<env force="true">` 変更: コミット [`c809f073`](https://github.com/Dixlase/dixlase-core/commit/c809f073)
- Installer フック: コミット [`77f776b`](https://github.com/Dixlase/dixlase-docker-installer/commit/77f776b)
- 残対策の引き継ぎメモ: [`.claude/plans/test-incident-followup.md`](../../../.claude/plans/test-incident-followup.md)
- マイグレーション編集ポリシー(関連背景): `CLAUDE.md`「Migration Editing Policy」
