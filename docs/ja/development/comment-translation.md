# コメント翻訳

Dixlase は **英語のソースコメントを正本** とし、各言語のコメントアーカイブを開発環境のソースに in-place で適用できる仕組みを備えています。本ドキュメントは仕組みの全体像、使い方、プラグイン/テーマ作者が同じパイプラインに自分の辞書を組み込む方法を説明します。

英語版: [`docs/development/comment-translation.md`](../../development/comment-translation.md)

---

## TL;DR

```bash
# 全ソースコメント (コア + プラグイン + テーマ) を日本語に変換
./convert-comments.sh ja

# 英語に戻す
./convert-comments.sh ja --reverse

# 利用可能な言語一覧
./convert-comments.sh --list
```

置換は AST 認識ベース（PHP コメントトークンのみ書き換え、文字列リテラルは触らない）で、冪等です（変換済みのソースに再適用しても何も起きない）。

---

## レイアウト

各「拡張機能」（コア、プラグイン、テーマ）は、ソースコードに対応する形で自分の言語別辞書を持ちます:

```
resources/comment-translations/{locale}/...                 # コア
plugins/{Name}/resources/comment-translations/{locale}/...  # プラグイン
themes/{Name}/resources/comment-translations/{locale}/...   # テーマ
```

言語ディレクトリ内の構造は、対応するソースのパスをミラーします。加えてメタデータファイルが 1 つ:

```
resources/comment-translations/ja/
├── _glossary.php                         # プロジェクト共通用語集
├── app/
│   ├── Console/Commands/AppUninstall.php
│   ├── Traits/LoginTrait.php
│   └── ...
├── bootstrap/app.php
├── config/csp/base.php
├── database/migrations/...
└── routes/admin.php
```

規約:

- **各 `*.php` ファイルは対応するソースファイルのパスをミラーします**。`ja/app/Models/Foo.php` の辞書は `app/Models/Foo.php` のコメントを翻訳します。
- **アンダースコアで始まるファイル名（`_glossary.php` など）はメタデータであり、辞書ではありません**。ファイルウォーカーがスキップします。将来のメタデータ種別（例: `_changelog.php`）でも同じ規約を使います。
- **言語ディレクトリ名は ISO 639-1 コード**（`ja`, `zh`, `ko`）に従います。`--locale=` 引数にそのまま渡せます。

---

## 辞書の書式

ソースファイル単位の辞書は、`'EN canonical' => '対象言語の翻訳'` のシンプルな PHP 配列です:

```php
<?php

return [
    'Apply translated comments to PHP source.' => 'PHP ソースに翻訳済みコメントを適用します。',
    'Two output modes:' => '2 つの出力モード:',
    '- copy mode (default)' => '- コピーモード (既定)',

    // ペンディングエントリ: 空文字 = 「JP は捕捉済みだが正本 EN がまだない」
    // ビルド時にはフィルタアウトされ、status --strict でカウントされる
    '本番環境専用の動作' => '',

    // メタデータブロック。キーは EN canonical（上記の辞書キーと一致）、
    // 値はレビュー状態のラベル
    '_review_status' => [
        'Apply translated comments to PHP source.' => 'human',
        'Two output modes:' => 'machine',
    ],
];
```

値の解釈:

| 値 | 意味 |
|---|---|
| 非空文字列 | 翻訳済み。`dls:comment:build` が言語版ソース生成に使用 |
| 空文字 `''` | ペンディング。キー側がまだ翻訳待ちの生のソーステキストとして扱われる。ビルド時には無視され、`dls:comment:status` がカウントする |

`_review_status` ブロックは翻訳配列に干渉せずに per-entry のレビュー状態を追跡します。許容値:

- `untranslated` — ペンディング、未翻訳
- `machine` — Claude API による自動翻訳、人間レビュー待ち
- `reviewed` — 人間が確認済み（編集なしで OK）
- `human` — 人間が手書き

`dls:comment:status --filter=machine` でレビュー要のエントリ一覧を出せます。

---

## `_glossary.php` ファイル

`{locale}/_glossary.php` は、プロジェクト固有の用語を翻訳プロンプトに注入します。`dls:comment:translate` が Claude API を呼ぶ時、入力バッチに登場する用語に対する用法ガイドを添付するので、AI がファイル間で違う訳をしてしまうのを防げます。

書式（ファイル別辞書と同じく EN canonical をキーに）:

```php
return [
    'Core' => 'コア',
    'plugin' => 'プラグイン',
    'admin panel' => '管理画面',  // "管理ダッシュボード" は使わない
    'member' => 'メンバー',        // エンドユーザー (システム概念には "user" を使う)
];
```

glossary はスリムに保つこと: Dixlase 固有用語、または AI のデフォルト翻訳がファイル間でブレやすい用語のみ。一般的なプログラミング用語を入れるとプロンプトが膨れるだけ。

言語別 glossary は独立しています — `ja/_glossary.php` と将来の `zh/_glossary.php` は同じ EN キーを共有しますが、それぞれの言語に合わせた訳語を持ちます。

---

## コマンド

パイプラインは Artisan コマンド 4 つで構成。コアに 2 つ（ユーザー向け）、DixlaseCoreDevKit プラグインに 2 つ（開発者向け）:

### `dls:comment:build`（コア）

各言語の翻訳をソースに適用。

```bash
# コピーモード: 翻訳版を dist/{locale} に出力、正本 EN は触らない
php artisan dls:comment:build --locale=ja --output=dist/ja

# in-place モード: ソースファイルを直接書き換え
php artisan dls:comment:build --locale=ja --in-place

# 逆変換: in-place 変換を戻す（locale → EN）
php artisan dls:comment:build --locale=ja --in-place --reverse

# プラグイン・テーマも対象に（既定はオフ）
php artisan dls:comment:build --locale=ja --in-place --include-plugins --include-themes

# プレビューのみ
php artisan dls:comment:build --locale=ja --in-place --dry-run
```

置換は AST 認識ベース: PHP コメントトークン（`T_COMMENT`, `T_DOC_COMMENT`）のみ書き換えられます。同じテキストが文字列リテラルにあっても触りません。

### `dls:comment:status`（コア）

翻訳進捗を表示。

```bash
# ディレクトリ別サマリー + レビュー状態の内訳
php artisan dls:comment:status

# strict モード: pending エントリがあれば exit 1（リリース CI で使用）
php artisan dls:comment:status --strict

# 個別エントリのフィルタ
php artisan dls:comment:status --filter=machine
php artisan dls:comment:status --filter=untranslated --limit=20
php artisan dls:comment:status --filter=unreviewed   # alias: machine + 状態未設定の翻訳済み
```

### `dls:comment:extract`（DevKit）

PHP ソースから日本語コメントをスキャンして辞書に pending エントリ（`'JP' => ''`）として追加。新しい言語を作るときに既存 JP ソースから種を播くのに使います。コアソースは既に正本英語化されているので、日常運用では不要。

```bash
php artisan dls:comment:extract --path=app/Traits
```

### `dls:comment:translate`（DevKit）

pending エントリを Claude API に送信し、辞書を `'EN' => 'JP'` 形式にローテーション。`.env` に `ANTHROPIC_API_KEY` が必要。

```bash
php artisan dls:comment:translate
php artisan dls:comment:translate --file=resources/comment-translations/ja/app/Models/Foo.php
php artisan dls:comment:translate --force   # 翻訳済みエントリも再翻訳
```

---

## `convert-comments.sh` ラッパー

`./convert-comments.sh` は `dls:comment:build --in-place` の薄いホスト側 bash ラッパーです。非開発者ユーザーが Artisan・Docker・コンテナ名を知らなくて済むように用意されています。

```bash
./convert-comments.sh ja                      # EN → JA
./convert-comments.sh ja --reverse            # JA → EN
./convert-comments.sh ja --dry-run            # プレビューのみ
./convert-comments.sh ja --strict             # pending あれば変換拒否
./convert-comments.sh --list                  # 利用可能な言語一覧
./convert-comments.sh ja --no-plugins         # コアのみ
./convert-comments.sh ja --no-themes          # コアのみ（--no-plugins と併用可）
./convert-comments.sh ja --path=app/Models    # スキャンを指定サブパスに限定
```

既定: `--include-plugins` と `--include-themes` は ON です。スクリプトは Docker の存在確認・PHP コンテナの起動確認をした上で `docker exec <container> php artisan dls:comment:build ...` を呼びます。

`DIXLASE_PHP_CONTAINER` 環境変数でコンテナ名を上書きできます:

```bash
DIXLASE_PHP_CONTAINER=my-php convert-comments.sh ja
```

Windows 用の `convert-comments.bat` も同梱されています。

---

## プラグイン / テーマで翻訳辞書を作る

プラグインやテーマは独自の言語別辞書を同梱できます。`--include-plugins` / `--include-themes` を渡せばパイプラインが自動で拾います（`convert-comments.sh` の既定で ON）。

### 手順

1. **対応する言語を決める**。最低限、自分のコメントの源言語に合わせて 1 つ用意。

2. **辞書ディレクトリを作成**（コアと同じ相対パス）:

   ```
   plugins/MyPlugin/resources/comment-translations/ja/
   ├── _glossary.php
   └── app/
       └── Models/
           └── Foo.php
   ```

3. **(任意) `_glossary.php`** にプラグイン固有用語を追加:

   ```php
   <?php

   return [
       'webhook' => 'ウェブフック',
       'payload' => 'ペイロード',
   ];
   ```

   プラグイン glossary はコア glossary を補完します。ビルド時に両方が結合されます。

4. **コメントを翻訳したい各ソースファイルに対して、ソースパスをミラーした辞書ファイル** を作成:

   ```
   plugins/MyPlugin/app/Models/Foo.php
   ↓
   plugins/MyPlugin/resources/comment-translations/ja/app/Models/Foo.php
   ```

   書式:

   ```php
   <?php

   return [
       'Webhook delivery state.' => 'Webhook 配送状態。',
       'Retry on failure.' => '失敗時に再試行する。',
   ];
   ```

5. **ローカル検証** — `./convert-comments.sh ja --include-plugins --include-themes` を実行し、`git status` でプラグインのソースが更新されていることを確認。

6. **Round-trip 確認** — `--reverse` 付きで実行して `git status` が再びクリーンになることを確認。クリーンにならない場合は、タイポ（検索置換が見つからない）または many-to-one マッピング（戻す時に曖昧）が原因です。

### 作成のコツ

- **コメントテキストを正確に一致させる**。トークン単位の whole-string マッチです。末尾のピリオド、スマートクォート、コメント内の前後の空白も全て含まれます。
- **同じ辞書内で 1-EN-対-多-JP のマッピングを避ける**。逆変換が決定的にできません。
- **PHPDoc の複数行コメント**は物理行ごとに別エントリとして登録。EN を re-flow すると JA とずれるので、エントリを行単位で揃えるのがベスト。
- **ランタイムの翻訳文字列は対象外**。ユーザーに見える文字列は `lang/{locale}/...`（Laravel 翻訳機構）に置きます。

### 翻訳しないもの

- ライセンスヘッダー（`// This file is part of...`）— 言語間で安定、慣習的に英語のまま
- セクション区切り（`// ====`, `// ---`）
- 1 文字コメントや trivial なマーカー

---

## 新しい言語を追加する

コアに `zh`（中国語）を追加する例:

1. `resources/comment-translations/zh/_glossary.php` を作成。EN キーに対する ZH の訳語。
2. （任意）DevKit パイプラインで正本 EN ソースから辞書を播種し、AI に ZH 翻訳を依頼:

   ```bash
   # translate コマンドは現状 JP-keyed pending エントリを前提としているため、
   # EN から新言語への forward translation はディレクション中立な glossary
   # 基盤を再利用しつつ、コマンド側の小修正が必要かもしれません。
   ```

3. `./convert-comments.sh zh --dry-run` で確認。
4. PR を提出 — 辞書は同じリポジトリにあるので、ソース変更と並んでレビューされます。

`dls:comment:extract` / `:translate` パイプラインは元々、JP→EN スイープ（正本 EN を作るため）用に設計されました。JA 以外の言語への forward translation は同じディレクション中立な glossary 基盤を使えますが、ワークフローによってはコマンド側の小修正が要るかもしれません。新しい言語サポートで困ったら follow-up として追跡してください。

---

## CI による強制

| トリガー | ワークフロー | モード | 効果 |
|---|---|---|---|
| PR / push | `.github/workflows/test.yml`（job: Comment Translation Coverage） | warning | カバレッジを job ログに表示。pending があっても PR は失敗させない |
| `v*` タグ | `.github/workflows/laravel-ci.yml`（step: Comment translation coverage） | strict | pending があればリリース ZIP ビルドが失敗 |

つまり PR には作業中の翻訳が混じっても OK ですが、タグ付けされたリリースは翻訳ギャップを抱えたまま出荷できません。

---

## リリース

Dixlase Core のリリースは **英語ソースコメントのみ** で出荷されます。辞書はソースツリーの一部（任意のタグから JA ビルドを再現可能）ですが、リリース tarball には事前変換版は含まれません。

ユーザーが想定する 2 つの経路:

1. **`DixlaseDockerInstaller` 経由のエンドユーザー**: インストーラーの `setup.sh` が言語選択を尋ね、Core を clone した後に `convert-comments.sh ja` を自動実行。ユーザーは変換ステップを意識しない。

2. **直接 clone**: `git clone` 後に `./convert-comments.sh ja` を実行（日本語環境にしたい場合）。

どちらにしても、正本のコミット履歴は英語のままで、言語版は派生・可逆の成果物です。

---

## アーキテクチャメモ

- **サービスクラス** は `plugins/DixlaseCoreDevKit/app/Services/CommentTranslation/` に存在します（パイプラインが動くには DevKit のインストールが必要）:
  - `TranslationFileService` — 辞書の読み書き / マージ。`setLocale()` / `getLocale()` で言語状態を制御。
  - `CommentBuilderService` — 置換実行。`applyToContent()`（token-AST 認識）または `applyExtension()`（拡張機能単位のウォーカー）。
  - `ClaudeTranslationService` — `dls:comment:translate` で使う Claude API クライアント。
  - `CommentExtractorService` — PHP-Parser ベースのコメント抽出。

- **`App\Services\CommentTranslation\ExtensionDictionaryLocator`**（コア）が指定言語の辞書ルート一覧を Core / プラグイン / テーマ横断で返します。`dls:comment:build` と `dls:comment:status` の両方が利用。

- **ストレージパス解決**: `TranslationFileService::__construct()` は `core-dev.comment_translation.storage_path`（既定 `resources/comment-translations`）と active locale（既定は `core-dev.comment_translation.default_locale`、既定値 `ja`）を config から読んで結合します。

- **冪等性**: 置換は完全一致ルックアップ。コメントを言語版にした時点で EN canonical はソースから消えるので、もう一度 forward を実行しても何も置換されません。EN への reverse も同様。
