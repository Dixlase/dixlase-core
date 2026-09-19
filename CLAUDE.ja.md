# Dixlase CMS

<!-- DIXLASE_RULES_START -->

## Dixlase Coding Rules (Shared)

### コードコメント言語
- **コードコメントと PHPDoc ブロックはすべて英語で記述する**（v0.1.0 以降のプロジェクト方針）
- 日本語が許される例外（後述の lazy migration の対象外）:
  - テストフィクスチャやテストデータ内の日本語文字列（意図的、i18n テスト用途）
  - `lang/ja/` 翻訳配列（Laravel 翻訳機構）
  - `*.ja.md` ドキュメント（バイリンガルミラー）
  - バイリンガル形式を選んだ場合のコミットメッセージの日本語 bullet 行
  - `comment-translations/` 内のエントリ（こちらが歴史的日本語キーの正本）

### コメント翻訳データの管理
- まだ日本語コメントが残るファイルに触るときは、ソースを英語化しつつ歴史的日本語を翻訳データとして残す **lazy migration ワークフロー** に従う:
  1. `docker exec -i dixlase-php php artisan dls:comment:extract --path=path/to/file.php` — 現在の日本語コメントを翻訳ファイルに **pending エントリ**（`'JP text' => ''`）として取り込む
  2. `docker exec -i dixlase-php php artisan dls:comment:translate --file=plugins/DixlaseCoreDevKit/resources/comment-translations/path/to/file.php` — Claude API で翻訳。各 pending エントリは自動的に `'EN canonical' => 'JP archive'` 形式に **rotate** され、`_review_status` で `machine`（未レビュー）にマークされる
  3. ソースの日本語コメントを **辞書の EN キー**（値ではなく）に完全一致でコピペ置換する。EN キーがソースに残る canonical で、JA リリースビルドが値の JP に逆置換する基準になる
  4. ソースファイルと翻訳ファイルを同じ commit でステージする
- 格納パス:
  - **コア**（`app/`, `config/` 等）: `plugins/DixlaseCoreDevKit/resources/comment-translations/`
  - **プラグイン**: `plugins/{PluginName}/resources/comment-translations/`
- 翻訳ファイルはソースパスをミラーする（例: `app/Http/Controllers/FooController.php` → `resources/comment-translations/app/Http/Controllers/FooController.php`）
- 辞書フォーマット（Phase 2 以降）: **英語キー**、JP アーカイブが値
  ```php
  return [
      'Get CSP directives.' => 'CSPディレクティブを取得',     // 翻訳済
      '残った日本語の文' => '',                                  // pending（JP キー、空値）

      '_review_status' => [
          'Get CSP directives.' => 'machine',                  // EN キーで状態管理
      ],
  ];
  ```
- 値が空文字列（`''`）= pending（この時点ではキーが JP）。`dls:comment:translate` が `'EN' => 'JP'` 形式に rotate する
- プロジェクト固有用語は `plugins/DixlaseCoreDevKit/resources/comment-translations/_glossary.php`（JP→EN マッピング）に格納する。Claude API のプロンプトに自動注入されるため、ファイル間で訳語が揺れない。Dixlase 固有用語が新たに登場したり AI 訳がブレた場合はここに追加する
- 各翻訳ファイルは `_review_status` メタデータブロックを持ち、エントリごとのレビュー状態（`untranslated` / `machine` / `reviewed` / `human`）を **EN canonical キー**で追跡できる。`dls:comment:status --filter=machine` で AI 翻訳されたが未レビューのエントリだけ抽出可能。レビューのまとめ実施は任意のタイミングで OK
- 翻訳ファイルが未作成の場合、extract コマンドが新規作成する。Phase 2 以前の旧 `'JP' => 'EN'` 形式は `dls:comment:rotate-keys` 一回限りコマンドで in-place マイグレーション可能（idempotent、pending エントリは触らない）

### ライセンスヘッダー
- 新規に作成するPHPクラスやBladeビューには、必ずファイル先頭にライセンスヘッダーを挿入する
- **コア**: AGPL v3 ライセンスヘッダーを使用する。参照: `plugins/DixlaseCoreDevKit/app/Console/Commands/LicenseHeaderCommand.php` または `plugins/DixlaseDevKit/license-templates/license-agpl.txt`
- **プラグイン・テーマ**: 各プラグイン・テーマが定めたライセンスヘッダーを使用する
- PHPファイルは PHPDoc ブロック形式（`/** ... */`）、Blade ファイルは Blade コメント形式（`{{-- ... --}}`）

### マイグレーションの編集方針

> リリースモデル: 初回公開リリースは **ベータ1**（バージョン `0.1.0`）として出荷し、**GA**
> がベータシリーズ（ベータ1 → … → GA）後の最初の本番リリース。マイグレーションロックは
> ベータ1 ではなく **GA** で適用する — 下の「フェーズ切替のゲート」を参照。

**GA 以降（本番運用フェーズ）**
- **既存マイグレーションファイルの編集は禁止**。テーブル変更は必ず新規マイグレーションファイルを追加する（`ALTER TABLE` 系）
- 理由: 監査証跡の保持、`php artisan migrate --force` による本番適用の安全性、ロールバック可能性、プラグイン間整合性の確保
- 新規マイグレーションファイルは Laravel 標準の `YYYY_MM_DD_HHMMSS_*` 形式を使用する
- 違反検出: `php artisan dls:migration:lint`（`database/migration-lock.json` と現在のハッシュを比較）

**ベータシリーズまで — ベータ1 → GA（現在の開発フェーズ）**
- 既存マイグレーションファイルの直接編集を許可する（短期間で頻繁にスキーマが変わるため）
- ファイル名プレフィックスは `0001_01_01_NNNNNN_*` を使用する（リリース時にこの形式のファイルは全て初期マイグレーションとして扱われる）
- スキーマ変更後、**開発者のローカル Dixlase Core dev checkout**（通常 `dixlase-dev-app`)は運用者が `php artisan migrate:fresh` で再構築してよい。**これは DEV 限定の便宜**であり、AI が自ら操作している checkout に限る話であって、他のコンテナには適用されない。
- **下流サイト**(site-local dev コンテナ `dixlase-brand-app` / `dixlase-demo-*-app` / `dixlase-docs-app` / `dixlase-authority-app` 等、ステージング、本番)**に対して `migrate:fresh` / `db:wipe` / `migrate:reset` を絶対に流してはならない — 対象コンテナで直接実行することも、その運用者 / セッション宛のタスク指示に書くことも、いずれも禁止**。これらのサイトでは `php artisan dls:migration:resync --prune`(dry-run が default、`--confirm` で適用。マイグレーション帳簿の行にのみ触れ、データテーブルには一切触れない)を使う。
- ベータ版はバージョン間でのインプレース・データ移行を **開発者のローカル dev checkout に対しては** 保証しない。当該 checkout に対しては運用者が `migrate:fresh` してよい。下流サイトは帳簿クリーンアップ経路で対処する。

**フェーズ切替のゲート**
- **GA** リリースタグを打つ直前に `php artisan dls:migration:lint --lock` を実行し、`database/migration-lock.json` をコミットする
- 以降のマイグレーション編集は CI の lint で検出されブロックされる

### データベース破壊的操作の禁止
- タスク実行中に `php artisan migrate:fresh` / `migrate:reset` / `db:wipe` を自動的に実行しない — 対象コンテナがどれであろうと、どのポリシー文書が緑信号に見えようと、例外なし。
- **他のセッション / コンテナ / 運用者宛のタスク指示に、破壊的コマンドを推奨・記載してはならない** — 非破壊的代替をすべて検証し尽くし、かつユーザーが該当対象に対して破壊的経路を明示的に承認していない限り。タスクドキュメントは送った瞬間に実行されうる — 自分がこれから実行するコマンドと同じ重みで扱うこと。
- **DB に触れるタスクドキュメントを書く前に、非破壊的な Artisan コマンドが存在しないか必ず確認する**。スキーマファイルのリネーム / マージ後のマイグレーション帳簿クリーンアップであれば、`php artisan dls:migration:resync --prune --confirm`(コマンド `--help` 参照)がこれに該当。`migrate:fresh` は、これや同種の代替案では解決できないと確認した後にのみ選択肢に入る。
- **記録済みインシデント(2026-07-26)**: AI セッションが「Beta 1 ポリシーは `migrate:fresh` を expect」の文言を全環境適用と誤読し、複数サイト rollout ドキュメントに `migrate:fresh` を推奨した。ドキュメントは下流サイトに送信される前に user が捕捉したものの、dev checkout のデータが失われた。詳細は `.backlog/destructive-db-command-incident-2026-07-26.ja.md` を参照。
- マイグレーションファイルの作成・編集は可。実際の反映はユーザーに委ねる。

### テスト実行時の安全装置
- **コンテナ名に `dev` または `test` が含まれていないコンテナに対して `php artisan test` や `vendor/bin/phpunit` を実行してはならない**(例: `dixlase-brand-app`、本番データを指すときの `dixlase-app`、`dixlase_keys-app` などは禁止)。テストは `RefreshDatabase` を使い、アクティブな接続に対して `migrate:fresh` を呼ぶため、本番データを持つコンテナで実行すると**全テーブルが DROP される**。
- 許可されるテスト用コンテナ: `dixlase-dev-app`(canonical な Core 開発環境)。プラグイン・テーマ・コアいずれのテスト検証もここで行う。専用の使い捨てテストコンテナを別途用意する場合は、名前に `test` を含め、本番ではない DB を使う。
- 任意のコンテナで `migrate:*` / `db:wipe` / `db:seed` / test 系コマンドを実行する前に、意図を明示し、コマンド中のコンテナ名を再確認する。
- `tests/TestCase` には MySQL/MariaDB/Postgres ガードが組み込まれており、`DB_CONNECTION` が禁止ドライバを指している場合に throw する。共有データや本番データに対してこのガードを `DLS_TESTS_ALLOW_NON_SQLITE=1` で迂回してはならない — 抜け穴は自分が管理する専用の使い捨てテスト DB に対してのみ使う。
- `phpunit.xml` の `<env>` オーバーライドは `force="true"` 属性が無いとプロセス環境に既に存在する値を上書きしない(つまり `.env` が勝つ)。新規にテスト用 env を追加する場合は必ず `force="true"` を付ける。
- `php artisan test <path>` よりも `--testsuite=<Name>` 経由を優先する。path-direct 実行はランナーによっては `<env>` 評価をバイパスする可能性があり、2026 年 5 月の事故で本番テーブルが DROP された経路がまさにこれだった。

### テストが作業ツリーの追跡ファイルに触れてはならない

テスト実行が壊しうるのはデータベースだけではない。ファイルシステムを扱う機能(バックアップ、リストア、インストール、インポート/エクスポート、キャッシュ生成)のテストは、**スイートが全て緑のまま追跡対象のリポジトリファイルを削除しうる**。

- **テストを実リポジトリのディレクトリに向けてはならない。** `base_path('custom')`、`base_path('plugins')`、`base_path('themes')`、`resource_path()` など、作業ツリー内のパスをテストフィクスチャの対象にしない。代わりに `storage/framework/testing/` 配下に使い捨てディレクトリを作り、`tearDown()` で削除する。
- テスト対象のコードが config からパスを解決する場合は、**`setUp()` で config を上書き**して実ディレクトリに到達できないようにする(例: `config(['custom.custom_files_dir' => 'storage/framework/testing/custom-'.uniqid()])`)。コード側がパスをハードコードしている場合は、テストを実ディレクトリに向けるのではなく、**コードを config 経由に修正する**。
- **`custom/README.md` と `custom/README.ja.md` は絶対に削除してはならない。** これらは追跡対象であり、`custom/` の上書き機構を説明する文書である。テスト実行がこれらを消してよい理由は一切ない。`README.laravel.md` をはじめとするリポジトリルートの追跡ファイルすべてに同じことが当てはまる。
- **テスト実行後および生成系コマンド実行後(`dls:plugin-api:generate` / `dls:readme:generate` / `dls:governance:generate` / `dls:claude:setup`)は必ず `git status` を確認する。** 自分が編集していない追跡ファイルが削除・変更されていたら、`git checkout -- <path>` で復元し、原因を調べてから作業を続ける。そうした削除を stage・commit してはならない。
- 特にリストア処理は危険である。リストアは通常**展開前に対象ディレクトリを一掃する**ため、アーカイブにたまたま含まれていない追跡ファイルは破壊される。`custom/README.md` が作業チェックアウトから繰り返し削除されながらスイートが緑のままだったのは、まさにこの経路である。修正内容は `CoreBackupService` / `CoreRestoreService` を参照(現在は `config('custom.custom_files_dir')` 経由で対象を解決する)。

### 管理画面レイアウトの保護領域
- `resources/views/layouts/admin.blade.php` のCSSクラス（Flexコンテナは上部余白なし、ヘッダー `pt-6 pb-6 px-8`、記事 `mt-5`、サイドバー `md:top-12`）は変更しない
- 管理バー（`h-12`）のオフセットとして調整済み — 全管理ページに影響する

### ブランチ作成・切替の確認
- **新規ブランチを作成する前に必ずユーザーに確認する** — `git checkout -b`、`git switch -c`、`git branch <new>` 等を勝手に実行しない
- 確認すべき内容: ブランチを切るかどうか、ブランチ名、ベースブランチ
- 既存ブランチへの切替（`git checkout <existing>`）も、未コミットの作業に影響する場合は確認する
- 例外: ユーザーが具体的なブランチ名つきで明示指示した場合（例:「`task-3-foo` ブランチを作って」）

### ブランチを切る前にローカルチェックアウトを同期する
- 長期に渡る開発チェックアウト（例: `html/` 下の `dixlase-core`、共有のマルチ開発者プラグイン/テーマリポジトリ）では、**機能ブランチを切る前に必ずリモートと同期する**
- ワーキングツリーが clean なことと「最新であること」は別物 — `git status` で `## main...origin/main` と出ても、それは「最後に fetch した時点の origin ポインタとローカルが一致する」という意味で、「今この瞬間の上流と一致する」という意味ではない
- ブランチを切る前の必須チェック:
  ```bash
  git fetch origin
  git log --oneline HEAD..origin/<base-branch> | head   # 出力なし = ブランチを切って OK
  git checkout -b <new-branch>
  ```
- `git log HEAD..origin/<base>` に未取得のコミットがあれば、まずローカルベースを fast-forward / rebase してからブランチを切ること
- この一手を飛ばすと、既にマージ済みの変更を二重で実装した PR を出してしまう事故の最大要因になる

### マージ / スカッシュコミットにプルリクエスト番号を残す
- 変更を pull request 経由で取り込む場合、**生成されるマージ / スカッシュコミットの件名に PR 番号（`#N`）を必ず含める** — `main` の履歴上のどのコミットからも、番号で元の PR を辿れるようにする。
  - GitHub の *Create a merge commit*（`Merge pull request #N from …`）と *Squash and merge*（`<件名> (#N)`）は既定で `#N` を含む。この既定を維持し、`#N` を削ったり件名から外して書き換えたりしない。
  - PR のブランチをローカルで `git merge <branch>` する場合は、メッセージに自分で番号を入れる — 件名末尾の `(#N)`、または本文の `PR #N` / `Closes #N` 行。

### コアのバージョン更新方針
- Dixlase コアのディスク上のバージョンは **2 つ**のファイルにあり、常に一致していなければならない: `VERSION` と `dixlase.json` の `version`
- 更新は必ず `php scripts/bump-version.php <x.y.z>` で両方同時に行う — 片方だけを手で書き換えない
- 2 つが食い違うと CI（`tests/Unit/VersionManifestConsistencyTest.php`）が失敗する
- コア更新パイプラインは両ファイルを適用し（`CoreSourceSnapshot::SOURCE_FILES`）、ロールバックでも両方が戻る。稼働中の食い違いは `VersionDriftService` が `manifest_drifted` として報告する
- プラグイン・テーマにも同種の組がある: マニフェスト（`plugin.json` / `theme.json`。コアはこちらを先に読む）と `composer.json`（プラグインはトップレベルの `version`、テーマは `extra.dixlase.version`）
- 拡張機能のバージョン更新は、その拡張機能のルートで `php scripts/bump-version.php <x.y.z>` を実行する — `package.json` があればそれも同時に揃える
- マニフェストと `composer.json` が食い違うと、拡張機能の `tests/Unit/VersionManifestConsistencyTest.php` で CI が失敗する（`package.json` はバージョンを読むコードがないため検査しない）
- `dls:make:plugin` / `dls:make:theme` が両ファイルを生成するので、新しい拡張機能は最初からガード付きで作られる
- プラグインの署名は `composer.json` と `package.json` も対象にしているため、署名済みプラグインはバージョン更新のたびに再署名する

### コア更新とテーマ更新（バンドルテーマは bootstrap-only）
- **コア更新はインストール済みテーマを一切変更しない。** `CoreUpdater::applyBundledThemes()` は、リリースマニフェスト（`.dixlase-release.json`、リリースノートの `[bundle-theme]` マーカーで生成）に宣言されたテーマを、**`themes/<slug>` がまだ存在しないときだけ**コピーする（リリースからの新規インストール）
- テーマが既にインストール済みなら完全にスキップする：ファイル上書きなし、`Theme.version` 変更なし、スナップショットなし — バンドル側が新しくても古くても同じ。バージョン比較は追加しないこと。テーマをいつ動かすかは運用者が決める
- テーマの変更は `dls:theme:update` / `dls:theme:rollback` のみで行う（独立・運用者制御）。コアとテーマのバージョンは独立している
- コア更新失敗時のロールバックは、その更新が bootstrap したテーマ（更新前に存在しなかったもの）を削除するだけで、既存テーマの復元や変更は一切行わない
- この経路のテストは `storage/framework/testing/` 配下の使い捨てツリーを使うこと（`tests/Unit/Services/Core/CoreUpdaterBundledThemesTest.php` 参照）。実際の `themes/` を指してはならない

### Git 調査ツール — `git log -S` はファイル内の "移動" を検出しない
- 「最近 X を変更した人がいるか」を調査する際、`git log -S '<term>'`（pickaxe）はその文字列の**出現回数が変わった**コミットしか検出しない
- 設定エントリが**同じファイル内で単に移動**した場合（例: SPDX 識別子が refused リストから accepted リストに移動）、`-S` には何も映らない — 出現回数は同じだから
- 代わりに以下を使う:
  - `git log -- <path>` — そのファイルの全変更履歴
  - `git log -G '<regex>'` — 差分に正規表現に合致する行の追加/削除があるコミット（移動も拾える）
  - `git log -p -- <path>` — 実差分を読む

### Git操作時のプラグイン・テーマ保護
- プラグイン・テーマは Core 内で**独立した git リポジトリ**。Core から見ると untracked
- **`git stash --include-untracked` や `git clean` を Core で実行しない** — プラグイン・テーマが削除される
- 復元: `for dir in plugins/*/; do [ -d "$dir.git" ] && (cd "$dir" && git checkout -- . 2>/dev/null); done`

### サブモジュール（プラグイン・テーマ）の gitlink 管理

#### 更新の正しい順序（必須）
1. プラグイン/テーマ内で変更 → commit
2. **プラグイン/テーマ側を remote に push**（ここを省略すると CI が到達不能コミットを参照して失敗する）
3. コアへ戻り `git add plugins/{Name}` で gitlink 更新
4. コア側を commit → push

#### 禁止事項
- プラグイン/テーマ側で **force-push / `git commit --amend` / `git rebase` によるコミット書き換え**
  （コアの gitlink が指すコミットが remote から消え、CI が `not our ref` エラーで fetch 失敗する。DixlaseSEO で実際に発生した不具合の原因）
- `.gitmodules` の手動編集（`.gitmodules` と gitlink の不整合の温床）
- 既存クローン済みの `plugins/X/` を直接 `git add` する（gitlink だけが作られて `.gitmodules` 未登録になる）

#### 正しい追加/削除コマンド
- 追加: `git submodule add <url> plugins/{Name}`（`.gitmodules` と gitlink を同時に整合させる）
- 削除: `git submodule deinit plugins/{Name} && git rm plugins/{Name}`

#### 開発中プラグイン・テーマの扱い
- CI に含めない開発中プラグイン・テーマは **`.gitmodules` にも gitlink にも登録しない**
- 各開発者がローカルで個別に `git clone` して配置する運用にする
- 安定版として公開できる段階になったら `git submodule add` で正式登録

#### リモート push 前の整合性チェック（推奨）
以下のコマンドで「gitlink が remote で到達可能か」「`.gitmodules` と gitlink が整合しているか」を確認：
```bash
# 1. .gitmodules と gitlink の整合チェック
diff \
  <(git ls-tree HEAD | awk '$2=="commit"{print $4}' | sort) \
  <(git config -f .gitmodules --get-regexp '^submodule\..*\.path$' | awk '{print $2}' | sort) \
  || echo "WARN: .gitmodules と gitlink が不整合"

# 2. 各 gitlink が remote で到達可能か
git submodule foreach --quiet \
  'git fetch --depth=1 origin $sha1 2>/dev/null || echo "WARN: $name の $sha1 が remote に未 push"'
```

### プラグイン・テーマを手動クローンした後の autoload 再生成
- 管理画面や `artisan plugin:install` ではなく **GitHub から `git clone` して**プラグイン・テーマを配置した場合は、動作前に自分で autoload を再生成する必要がある
- プラグインのクラス（`Plugins\{Name}\App\...`）は `composer.local.json` 経由でオートロードされる。これは `Plugins\{Name}\App\` → `plugins/{Name}/app` をマッピングし、`wikimedia/composer-merge-plugin` がルートの autoload にマージする。このファイルは自動生成で、素の `git clone` では **更新されない** ため、クラスが解決できないままになる
- 症状: ルートは正常に登録される（ルートファイルはディスクから走査されるため）が、プラグインのコントローラにディスパッチした瞬間に `BindingResolutionException: Target class [Plugins\...\SomeController] does not exist` が発生する
- 管理画面 / `plugin:install` / `plugin:delete` の経路では自動実行される（`ComposerLocalHelper::syncAutoload()`）。手動クローンでは実行されないので、Core ルート（`html/`）で自分で実行する:
  ```bash
  php scripts/sync-local-autoload.php && composer dump-autoload --no-scripts
  ```
  - `sync-local-autoload.php` は現在の `plugins/` + `themes/` から `composer.local.json` を書き直す（フレームワーク非依存・DB 不要）。`--no-scripts` は DB 接続を要する `package:discover` ポストフックをスキップする
- 注意: 素の `composer dump-autoload` は **2 回実行** が必要 — composer-merge-plugin は `pre-autoload-dump` フックが再生成する前の古い `composer.local.json` を読むため、1 回目では新規クローンしたプラグインを取りこぼす。上記のワンライナーは composer 起動前にファイルを書き直すことでこれを回避する
- `composer.local.json` と `vendor/` は gitignore 対象なので、この作業でコミットすべき差分は発生しない — ローカル環境専用の手順である

### CLAUDE.md 再生成
- 共有ルール（`resources/ai/shared-rules-*.md`）やコア固有ルールを編集後、`dls:claude:setup` を実行
- CLAUDE.md を直接編集しない — ソースは各ルールファイル

### プラグイン・テーマの作成はコマンドを使用
- 新規プラグイン作成時は必ず `dls:make:plugin` コマンドを使用する — ファイルを手動で作成しない
- 新規テーマ作成時は必ず `dls:make:theme` コマンドを使用する — ファイルを手動で作成しない
- コマンドが標準スキャフォールド（plugin.json, ServiceProvider, composer.json, config, routes, lang, tests, vite.config 等）を生成する
- コマンドに不備や不足機能がある場合は、手動で回避せずコマンド自体を修正する
- スキャフォールド生成後に、カスタムファイル（Services, Controllers, views 等）を追加する

### プラグイン権限宣言（plugin.json permissions）の同期
- **コア機能を新たに使用する場合は `plugin.json` の `permissions` を必ず同時に更新する**
  - 例: メール送信（`Mail::to()`）追加 → `mail.send` を `true` に
  - 例: `storage_path()` や `Storage::disk()` 使用 → `storage.own_directory` を `true` に
  - 例: マイグレーション追加 → `database.own_tables` を `true` に
  - 例: Artisanコマンド追加 → `system.register_commands` を `true` に
- 権限宣言とコード実態の不一致はプラグインスキャナーが検出し、健全性スコアが減点される

### プラグイン・テーマ間のデータ参照ルール
- 他のプラグインやテーマから**プラグイン内部を直接参照しない**（`\Plugins\PluginName\*` のモデル・サービス等）
- プラグインのデータへはコアのContract+DTO（`App\Contracts\PluginIntegration\*` + `App\DTO\PluginIntegration\*`）またはコアAPIを経由する
- プラグインは `plugin.capabilities` タグで機能を登録し、`PluginServiceResolver` 経由で解決する
- 必要なContractが存在しない場合は、先にコアにContractを作成し、プラグイン側で実装する
- セキュリティスキャン適合・疎結合・プラグイン未インストール時の安全なフォールバックを保証する

### Tailwind CSS v4
- 本プロジェクトは **Tailwind CSS v4** を使用し、CSSファースト設定（CSSファイル内の `@theme`, `@source`, `@variant`）を採用している
- `tailwind.config.js` は存在しない — 設定はすべて `resources/src/common/css/tailwind.css`（コア）または `resources/src/front/css/tailwind.css`（テーマ）に記述
- `@apply` は SCSS エントリファイルの `@reference "tailwindcss"` により使用可能だが、**新規コードではCSS変数の直接使用を推奨**:
  - `@apply text-gray-700 px-4` の代わりに `color: var(--color-gray-700); padding-inline: calc(var(--spacing) * 4);` を使用
- SCSS で `@apply` を使用する場合、エントリSCSSファイルのすべての `@use` 文の後に `@reference "tailwindcss"` を記述する必要がある
- ダークモードはクラスベース: `@variant dark (&:where(.dark, .dark *))`
- v4 Preflight で SVG 要素が `display: block` に変更された（v3からの変更点）— 必要に応じて `shrink-0` や明示的な flex 配置を使用する

### Blade コンポーネントの使用
- フォーム要素やUI部品はハードコードせず、コアの `resources/views/components/` のコンポーネントを使用する
  - **フォーム系**: `<x-form-text>`, `<x-form-textarea>`, `<x-form-select>`, `<x-form-toggle>`, `<x-form-radio-card-group>`, `<x-form-button>`, `<x-form-label>`, `<x-form-error>` 等
  - **UI系**: `<x-ui-modal>`, `<x-ui-notification>`, `<x-ui-status-badge>`, `<x-ui-pagination>` 等
  - **管理画面**: `<x-admin.save-button>`, `<x-admin.delete-button>`, `<x-admin.danger-zone>` 等
- **チェックボックス / トグル**: `<x-form-toggle>` を使用（真偽値入力全般）。インラインHTML不可
- **ラジオボタン**: `<x-form-radio-card-group>` を優先。Alpine.js バインディングは `xModel` プロパティ使用
- 新しいコンポーネントを作成する前に、再利用可能な既存コンポーネントを確認する

### イベントハンドラとCSP準拠
- インラインイベントハンドラ（`onclick`, `onchange`, `onsubmit`, `onpaste` 等）は**使用禁止** — CSP `script-src-attr 'none'` に違反する
- Alpine.js ディレクティブ（`@click`, `@change`, `@submit`, `@paste.prevent` 等）を使用する
- ペースト防止: `onpaste="return false;"` ではなく `@paste.prevent` を使用する
- 戻るボタン: `onclick="history.back()"` ではなく `@click="history.back()"` を使用する
- `<script>` タグには `@cspNonce` を付与する（`<script @cspNonce>`）

### Alpine.js — 厳格モード対応の書き方（`@alpinejs/csp` 想定）
- Dixlase は将来のメジャーリリースで Alpine バンドルを `@alpinejs/csp` に切替予定。新規コードはその時点での制約を先取りして書くこと。バンドル切替を機械的に済ませられるようにする。
- **`x-data="{...}"` リテラルを新規に書かない。** データオブジェクトは JS モジュールで `Alpine.data('name', () => ({ ... }))` 登録し、`x-data="name"` で参照する。
  - ページ固有データ: ページの JS エントリ隣（`resources/src/admin/{area}/js/{page}.js`）
  - クロスページ: `resources/src/common/js/alpine-data/{name}.js`、エリアブートストラッパから import
  - コア提供の共有登録（`accordion`、`toggle`、`tabs`、`modal`）が利用可能ならそれを優先する
- **ロジックを含む `x-init="...JS式..."` を書かない。** 登録データの `init()` メソッドにする。
- **ディレクティブ式中のオブジェクトリテラルを避ける。**
  - 悪い例: `:class="{ active: open }"`、`@click="$dispatch('foo', { bar: baz })"`
  - 良い例: `:class="classes()"`、`@click="emitFoo()"`（メソッドは登録データ側に）
- **ディレクティブ内のアロー関数・テンプレート文字列・複雑な連鎖式は不可。** メソッドでラップする。
- 既存のリテラル形式 `x-data` はファイルに触れた時に機械的に移行する。現状の数とエリア進捗は `php artisan dls:csp:scan-alpine` で確認できる。
- 本ルールはコア・プラグイン・テーマすべてに適用。CSP build 切替はエコシステム規模で実施する。

### Plugin API 登録と @api タグ
- コアでプラグインやテーマが使用する共有コンポーネント（Bladeコンポーネント、Contract、サービス等）を作成した場合、**必ず `PLUGIN-API.md` に登録する**
- ソースファイルのヘッダーに `@api` タグを付与し、Plugin API の対象であることを明示する:
  - PHPファイル: PHPDoc ブロック内に `@api`
  - Bladeファイル: ライセンスコメントブロック内に `@api`（`{{-- @api ... --}}`）
- `PLUGIN-API.md` に登録しないと、そのコンポーネントを使用するプラグイン・テーマがAGPLライセンス例外の対象外となる可能性がある
- **`PLUGIN-API.md` は生成物であり、直接編集しない**
  - 編集対象: `plugins/DixlaseCoreDevKit/resources/ai/plugin-api/template-{en,ja}.blade.php`
  - 翻訳キー: `plugins/DixlaseCoreDevKit/lang/{en,ja}/plugin-api.php`
  - 生成コマンド: `php artisan dls:plugin-api:generate --lang=en` および `--lang=ja`
- `PLUGIN-API.md` 更新後は `dls:claude:setup` を実行して CLAUDE.md を再生成する

### ドキュメントの配置
- デフォルト言語は英語。コア: `docs/`、プラグイン: `plugins/{Name}/docs/`
- 日本語版: `docs/ja/` に同じ構造でミラー
- **作業プラン**: Claude Codeの作業プランは `.claude/plans/` に配置する（gitignore対象）。`docs/` には置かない
- `docs/` は公式ドキュメント専用 — 一時的な作業プランと混在させない

### サンドボックステスト（Claude Code 作業用テスト）
- コーディング作業中にテストファイルを作成する場合は、`tests/Feature/` や `tests/Unit/` ではなく `tests/Sandbox/` に配置する
- **全リポジトリ**（コア・プラグイン・テーマ）共通 — 既存のCI/CDテストスイートを変更しない
- `tests/Sandbox/` は gitignore 対象であり、CI/CDテストスイートには含まれない
- ディレクトリ構成は標準テストと同じ: `tests/Sandbox/Feature/`, `tests/Sandbox/Unit/`
- サンドボックステストの実行: `php artisan test tests/Sandbox/` または `--filter=testMethodName`
- サンドボックステストを正式採用する場合は、レビュー後に `tests/Feature/` や `tests/Unit/` に移動する

### Artisan キャッシュコマンドの実行環境
- `php artisan config:cache`、`route:cache`、`view:cache` は**必ず Docker コンテナ内から実行する**
- ホスト側で実行するとパスがホスト側のパスでキャッシュされ、コンテナ内のパスと不一致になり実行時エラーが発生する
- 正しい実行方法: `docker exec <your-php-container> php /var/www/html/artisan config:cache`（コンテナ名・ベースパスは自分の環境に合わせて読み替える）
- GitHub Actions のデプロイワークフローでも同様にコンテナ内で実行すること

### View キャッシュクリア後の再生成
- タスク内で `php artisan view:clear` または `optimize:clear` を実行した場合は、**直後に `php artisan view:cache` を実行して再生成する**
- 理由: 開発環境（`npm run dev`）では Vite が `storage/framework/views/` 配下を監視しているため、ページ遷移中に Laravel が新しい Blade コンパイルファイルを書き出すと、Vite が `[vite] page reload` 信号を送信して navigation がキャンセルされる（「メニューを押すと現在ページが再表示される」現象の原因）
- `view:cache` で事前に全ビューをコンパイルしておけば、ページ遷移中の動的コンパイルが発生しない
- 適用対象: `php artisan view:clear`、`php artisan cache:clear`（内部で view も消える）、`php artisan optimize:clear`
- セット例: `docker exec <your-php-container> bash -c "php artisan view:clear && php artisan view:cache"`

<!-- DIXLASE_RULES_END -->
