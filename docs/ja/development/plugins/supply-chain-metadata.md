# プラグイン・テーマ作者向けサプライチェーンメタデータ

> **必須:** Dixlase 向けに配布する全プラグイン・テーマは、`plugin.json` /
> `theme.json` で `author_id` と `authority_key_id` を宣言すること。
> 各フィールドが欠落していると**ヘルススコアから -3 点減点**され、
> hijack 検出フローが機能しない。

## なぜ必要か

署名済み拡張は、上流リポジトリが publisher の身元をサイレントに置き換えた版に
差し替わる可能性がある。バージョン履歴層（[サプライチェーン防御データ層](../supply-chain.md)
を参照）は、各更新の `author_id` / `signing_key_id` を直前のインストール値と比較し、
差異があれば `signing_key_changed` / `author_id_changed` フラグを立てる。これが
運用者に hijack を伝えるシグナル。

これらのフィールドが無いと比較が成立せず、検出が動かない（比較対象となる
「以前の正規所有者」が無いため）。ヘルススコアでは
`missing_author_id` / `missing_authority_key_id` として **-3 点ずつ**減点される。

## 必須フィールド

### `author_id`

公開組織の安定 ID。同じ組織が出すすべての拡張のすべてのリリースで同一の値を使う。
慣例的には組織名を kebab-case で:

```json
{
    "author": "exc-D inc.",
    "author_id": "exc-d-inc"
}
```

- **安定性:** 更新でこの値を変えると毎回 `author_id_changed` フラグが立つ
  ため、意図的な所有権移譲時にのみ変更する。
- **スコープ:** プラグイン・テーマ。コア拡張も第三者拡張も同様に宣言する必要がある。

### `authority_key_id`

リリースに署名している authority サーバ（`authority.dixlase.net` または自前バンドル）
上の公開鍵エントリの識別子。検証側はファイル内容を巡回せずにこの ID から
公開鍵を引く:

```json
{
    "authority_key_id": "dixlase-authority-2026"
}
```

- **形式:** 小文字 kebab-case で年セグメント付きが慣例（例:
  `dixlase-authority-2026`）。年セグメントは年次ローテーションを明示する目的。
- **ローテーション:** 署名鍵をローテーションする際は、新しい `authority_key_id`
  で新公開鍵を公開し、各 in-tree 拡張の `plugin.json` / `theme.json` を次の
  リリースで更新する。更新時に `signing_key_changed` フラグが立つが、これは
  **意図的ローテーションの想定シグナル**。

## 最小例（`plugin.json`）

```json
{
    "name": "Dixlase Pages",
    "slug": "dixlase-pages",
    "version": "0.1.0",
    "author": "exc-D inc.",
    "author_id": "exc-d-inc",
    "authority_key_id": "dixlase-authority-2026",
    "license": "GPL-3.0-or-later",
    "requires": {
        "dixlase": "^0.1.0"
    }
}
```

同じフィールドが `theme.json` にも適用される。テーマもサプライチェーン攻撃の
対象であり、同じスコアリング規則に従う。

## 宣言の検証

拡張を Dixlase 環境にインストールしたら、プラグイン監査を実行してヘルススコアを確認:

```bash
docker exec dixlase-php php artisan dls:plugin:audit --calculate-health
```

欠落フィールドは issues として列挙される。実装の正本は
`PluginHealthScorer::evaluateSupplyChainMetadata()` メソッド。

これらの列が導入される前（v0.1.0 以前）に seed された環境で動かす場合は、
ディスク上の manifest からデータベースを埋める:

```bash
docker exec dixlase-php php artisan dls:plugin:backfill-supply-chain
```

idempotent で、現在 `null` の列にのみ書き込むため、複数回実行しても安全。

## 参照

- [サプライチェーン防御データ層](../supply-chain.md) — スキーマと `^0.1`
  で凍結したスラッグキー保持ポリシー。
- [プラグインヘルススコア規則](health-scoring-rules.md) — `missing_author_id`
  / `missing_authority_key_id` を含む全減点表。
- [公開識別子の命名規約](../naming.md) — `authority_key_id` に適用される命名規則。
