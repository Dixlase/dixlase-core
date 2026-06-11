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
| **Core** | ✅ `custom/resources/views/...` | ✅ `Custom\App\` 経由で `custom/app/...` | ✅ `custom/lang/` / `custom/config/` |
| **Plugin** | ✅ `custom/plugins/{Plugin}/resources/views/` | ❌ 未実装(バックログ参照) | ✅ `custom/plugins/{Plugin}/{config,lang}/` |
| **Theme** | ✅ `custom/themes/{Theme}/resources/views/` | ❌ 未実装(バックログ参照) | 一部のみ |

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
│   ├── factories/                # カスタム factory
│   ├── migrations/               # カスタムマイグレーション
│   └── seeders/                  # カスタム seeder
├── plugins/{Plugin}/             # プラグインごとのオーバーライド
│   ├── app/                      # ← v0.2 のロジック上書き用に予約
│   ├── resources/views/          # プラグインビュー上書き(現在動作)
│   ├── lang/{locale}/            # プラグイン翻訳上書き
│   └── config/                   # プラグイン設定マージ
├── themes/{Theme}/               # テーマごとのオーバーライド
│   ├── app/                      # ← v0.2 のロジック上書き用に予約
│   ├── resources/views/          # テーマビュー上書き(現在動作)
│   ├── lang/{locale}/            # テーマ翻訳上書き
│   └── config/                   # テーマ設定マージ
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
loader は対応するパスに置かれた Blade ファイルを優先して読み込みます。

プラグインとテーマの **ロジック**(PHP クラス)上書きはまだ結線されて
いませんが、名前空間とパスは予約済みです。`Custom\Plugins\{Plugin}\App\…`
配下に書いたコードは autoload は通りますが、まだ元のプラグインクラスに
バインドはされません。状況はバックログを参照してください。

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
  到達可能な場合は §13 のソース開示義務が発生する可能性があります。GPL
  ライセンスのプラグイン・テーマについては、§6 の配布時義務が発生します。

**プラグイン・テーマ例外条項(`LICENSE-EXCEPTIONS`)は、機構の如何を
問わず `custom/` 配下のオーバーライドを対象外としています。** 例外
条項が適用されるのは `plugins/` または `themes/` 配下に配置され、
`PluginLoaderTrait` / `ThemeLoaderTrait` を通じて読み込まれるプラグイン
およびテーマのみです。`custom/` に置かれたサブクラスは変更された
Dixlase プログラムの一部とみなされ、AGPL の対象となります。

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
