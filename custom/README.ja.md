# `custom/` — サイト固有のオーバーライド

For English, see [README.md](./README.md).

このディレクトリは **コア / プラグイン / テーマのソースを直接改変
することなく** Dixlase をカスタマイズするための正規の場所です。配布物に
施された暗号署名を保ったままサイト固有のロジックや見た目を上書きできます。

`dls:signer:sign --force` は `plugins/{Plugin}/` と `themes/{Theme}/`
配下のファイルしかハッシュ化しません。`custom/` 配下には絶対に触れない
ため、ここに置いたファイルは署名検査の対象外です。

## v0.1.0 時点で動作する範囲

| 対象 | Blade ビュー上書き | ロジック上書き (PHP) | 設定 / 言語ファイル上書き |
|---|---|---|---|
| **Core** | ⚠️ 特定 view namespace のみ(下記参照) | ✅ `Custom\App\` 経由で `custom/app/...` | ❌ 未実装(loader コードは存在するが現在は無効化) |
| **Plugin** | ✅ `custom/plugins/{Plugin}/resources/views/` | ❌ 未実装(バックログ参照) | ✅ `custom/plugins/{Plugin}/{config,lang}/` |
| **Theme** | ✅ `custom/themes/{Theme}/resources/views/` | ❌ 未実装(バックログ参照) | ✅ `custom/themes/{Theme}/{config,lang}/` |

**v0.1.0 における Core view 上書きの範囲:** `AppServiceProvider::boot`
が prepend している 4 つの `View::addNamespace` スロット — `admin::`,
`components::`, `layouts::`, `themes::` — のみが custom パスの
先頭挿入を受け付け、Blade ファイルを優先読み込みします。namespace
指定なしの汎用 view(例: `View::make('welcome')` で
`resources/views/welcome.blade.php` を返すもの)は現時点で
`custom/resources/views/` からは **上書きできません**。この dispatch
経路が使う loader entry は現在無効化されているためです。

**Core lang / config 上書き:** loader メソッド(`loadCustomLang` /
`loadCustomConfigs`)は `app/Traits/CustomFilesLoaderTrait.php` 内に
コードは存在しますが、`/* ... */` ブロックの中に置かれており呼ばれて
いません。有効化はコメント解除と呼び出し登録のみですが、merge と
replace のセマンティクスに関する意思決定を保留したまま v0.1.0 では
無効化されています。将来のリリースで有効化予定です。

プラグイン / テーマの **ロジック**(PHP クラス)上書きはロードマップに
入っており、設計と展開計画は `.backlog/custom-overrides-plugin-theme.ja.md`
を参照してください。リリースされるまで実際の差し替えは効きませんが、
**以下のパスレイアウトと PSR-4 名前空間は既に予約済み** です。今のうちに
これらの名前空間でコードを書いても autoload は通り、ローダーが入った時点
で自動的に差し替えが有効になります。リネーム作業やマイグレーションは
必要ありません。

## 予約済みレイアウト

以下のパスは公開契約の一部です。リネームされません。

```
custom/
├── README.md / README.ja.md      # このファイル(git 追跡対象)
├── app/                          # コアクラスのオーバーライド
│   └── Http/Controllers/…        # app/ のサブツリーをミラー
├── resources/
│   └── views/                    # コア Blade ビューのオーバーライド
├── lang/{locale}/                # コア翻訳のオーバーライド
├── config/                       # コア設定のオーバーライド
├── database/
│   ├── factories/                # カスタム factory(autoload 済み)
│   ├── migrations/               # ⚠️ 予約枠;v0.1.0 では Laravel migrator に登録されていない
│   └── seeders/                  # カスタム seeder(autoload 済み)
├── plugins/{Plugin}/             # プラグインごとのオーバーライド
│   ├── app/                      # ← 将来のロジック上書き用に予約(autoload 登録済み)
│   ├── resources/views/          # プラグインビュー上書き(現在動作)
│   ├── lang/{locale}/            # プラグイン翻訳上書き(現在動作)
│   └── config/                   # プラグイン設定マージ(現在動作)
├── themes/{Theme}/               # テーマごとのオーバーライド
│   ├── app/                      # ← 将来のロジック上書き用に予約(autoload 登録済み)
│   ├── resources/views/          # テーマビュー上書き(現在動作)
│   ├── lang/{locale}/            # テーマ翻訳上書き(現在動作)
│   └── config/                   # テーマ設定マージ(現在動作)
└── tests/                        # カスタムテストスイート
```

## 予約済み PSR-4 名前空間

`composer.local.json` は検出された全プラグイン / 全テーマに対して以下を
無条件に登録します。今すぐファイルを配置すれば即 autoload されます。

| 名前空間 | パス |
|---|---|
| `Custom\App\…` | `custom/app/…` |
| `Custom\Database\Factories\…` | `custom/database/factories/…` |
| `Custom\Database\Seeders\…` | `custom/database/seeders/…` |
| `Custom\Tests\…` | `custom/tests/…` |
| `Custom\Plugins\{Plugin}\App\…` | `custom/plugins/{Plugin}/app/…` |
| `Custom\Themes\{Theme}\App\…` | `custom/themes/{Theme}/app/…` |

## 例: コアコントローラの上書き(現在動作)

```php
// custom/app/Http/Controllers/CspReportController.php
namespace Custom\App\Http\Controllers;

class CspReportController extends \App\Http\Controllers\CspReportController
{
    public function report(\Illuminate\Http\Request $request)
    {
        // 独自のロジック
        return parent::report($request);
    }
}
```

アプリケーション起動時に `App\Traits\CustomFilesLoaderTrait` が
`custom/app/Http/Controllers/` をスキャンし、このクラスを検知して
コアクラスを Custom 側のサブクラスにサービスコンテナで bind します。
以降の controller dispatch や `app()->make(...)` は Custom 側を返します。

## プラグイン / テーマの上書きについて

プラグインとテーマの **ビュー** 上書きは現在動作します。namespaced view
loader は対応するパスに置かれた Blade ファイルを優先して読み込みます
(`PluginLoaderTrait::loadPluginViews`, `ThemeLoaderTrait::loadThemeViews`)。

プラグインとテーマの **config / lang** 上書きも現在動作します。
`PluginLoaderTrait::loadPluginConfigs` / `loadPluginTranslations` と
`ThemeLoaderTrait::loadThemeConfig` / `loadThemeTranslations` が
`custom/{plugins,themes}/{Name}/{config,lang}/` パスを本体より先に
prepend するので、言語ファイルも config キーも上書き値が優先されます。

プラグインとテーマの **ロジック**(PHP クラス)上書きはロードマップに
入っていますが、v0.1.0 ではまだ結線されていません。設計は Core と同じ
パターン(サブクラス + サービスコンテナ bind)を想定しています:
`Custom\Plugins\{Plugin}\App\…` 配下に置いたクラスが元のプラグイン
クラスを継承し、コンテナ bind により将来の `app()->make(...)` 参照や
controller dispatch が custom 側のサブクラスにルーティングされます。
名前空間とパスは **すでに予約済み** です — `composer.local.json` は
検出された全プラグイン / 全テーマに対して `Custom\Plugins\{P}\App\` と
`Custom\Themes\{T}\App\` を登録しており、今のうちにコードを書いても
autoload は通ります。ローダーが入った時点で自動的に切り替わります。
リネームやマイグレーションは不要です。設計と展開計画は
`.backlog/custom-overrides-plugin-theme.ja.md` を参照してください。

プラグインとテーマの **マイグレーション** は意図的に `custom/` の
対象外です。プラグイン / テーマのマイグレーションは `PluginMigrator` /
`ThemeMigrator`(プラグイン / テーマインストールコマンド経由で起動)を
通り、専用の `dls_plugin_migrations` / `dls_theme_migrations` ledger で
管理されます。`custom/plugins/{Plugin}/database/migrations/` にファイルを
置いても何も起こりません。

## ライセンス上の注意

`custom/` は署名整合性を保ちますが、**ライセンスが無効になる領域では
ありません**。2 つの方法はライセンス上の扱いが大きく異なります:

- **サブクラス + コンテナ bind(上で文書化したパターン)** は public API を
  介して元クラスを利用しているだけで、元のソースは 1 行もコピーしません。
  「API の利用」であって派生作品の創作にはあたらない解釈が主流であり、
  本機構が支援する中で最もリスクの低い道です。Free Software Foundation
  は文脈によっては厳格な立場を取るので絶対の法的確実性ではありませんが、
  当機構が提供できる最も安全な経路です。

- **元ソースファイル**(コア、プラグイン、テーマ)を `custom/` に
  **コピーして編集** する行為は、GPLv3 / AGPLv3 §0 の "modify" 定義に
  該当します。コピーされたファイルは引き続き元のライセンスで縛られ、
  AGPL ライセンスのコアファイルについては、サイトがネットワーク経由で
  到達可能な場合は §13 のソース開示義務が発生する可能性があります。
  プラグインやテーマについてはそのプラグインまたはテーマ自身のライセンス
  条項がコピーされたファイルに適用されます — GPL ライセンスの場合は
  §6 の配布時開示義務が発生します。

**プラグイン・テーマ例外条項(`LICENSE-EXCEPTIONS`)は、機構の如何を
問わず `custom/` 配下のオーバーライドを対象外としています。** 例外
条項が適用されるのは `plugins/` または `themes/` 配下に配置され、
`PluginLoaderTrait` / `ThemeLoaderTrait` を通じて読み込まれるプラグイン
およびテーマのみです。`custom/` 配下のオーバーライドファイルには
上書き対象の上流ライセンスが引き続き適用され、AGPL 第 13 条の発火
有無は Dixlase コアが改変されているかどうかで決まります:

| 上書き対象 | オーバーライドファイルのライセンス | AGPL §13 発火 | 実務的な意味 |
|---|---|---|---|
| **Dixlase コア**(`custom/app/...`、`custom/resources/views/...`) | AGPL(AGPL コアの派生) | はい — コアが改変されている | 動作中サイトのネットワーク利用者全員(匿名公衆含む)へのソース開示義務 |
| **プラグイン**(`custom/plugins/{Plugin}/...`) | そのプラグイン自身のライセンス — オーバーライドはそのプラグインの派生作品です(公式 Dixlase プラグインは GPL、サードパーティ製プラグインは作成者が選択した任意のライセンス) | いいえ — コアソースは無改変 | そのプラグイン自身のライセンスがオーバーライドに適用される。GPL プラグインの場合は GPL §6 が配布相手にのみ適用、ネットワーク利用者への義務は無し。許諾的ライセンスや商用ライセンスの場合はそれぞれの条項に従う |
| **テーマ**(`custom/themes/{Theme}/...`) | そのテーマ自身のライセンス — プラグインと同じく作成者が選択 | いいえ — 同上 | 同上、テーマ自身のライセンスに従う |

プラグイン・テーマ行は Dixlase のデュアルライセンス設計が
意図したポイントです:エージェンシーがクライアント案件でサイトを
構築する際、Dixlase コアのソースファイルを変更しない限り、
`custom/` 内でプロプライエタリ水準のプラグイン/テーマカスタマイズ
を行っても、そのカスタマイズが一般公衆への開示を強制することは
ありません。`app/` 配下、`resources/views/`(コアレイアウト)、
`config/`、その他コアパスのファイルを 1 つでも編集した瞬間、AGPL
§13 が動作中の改変コアに発火し、そのプログラムについて公衆開示
義務が生じます。

これは法律相談に代わるものではありません。「コアを改変しない限り
§13 は発火しない」という解釈は GPL/AGPL §0 の標準的な
"mere aggregation" 解釈に依拠しており、Dixlase が著作権者として
採用する立場です。Free Software Foundation は文脈によってはより
厳格な立場を取ります。リスクが高い運用であれば、必ず法務専門家に
確認してください。

プロプライエタリな上書きを行いたい場合の正規ルートは:

1. `plugins/` 配下にプロプライエタリなプラグインを書き、Plugin API
   のみを通じて Dixlase と連携させる(例外条項が想定している、最も
   クリーンな経路)
2. 商用ライセンス(`LICENSE-COMMERCIAL`)を取得して、その契約条項に
   従う

これは法律相談に代わるものではありません。各管轄・各運用形態における
具体的な影響は、必ず法務専門家に確認してください。

## 補足

- このディレクトリの中身は `README.md` / `README.ja.md` を除き
  gitignore されています。カスタマイズはサイトローカルな性質を持ちます。
- 署名はこのディレクトリに絶対触れません。`custom/` 配下の変更でプラグイン
  やテーマの署名が破壊されることはありません。
- テスト用フィクスチャや使い捨ての PoC コードは `custom/` を汚さず
  `tests/Sandbox/` に置いてください。
