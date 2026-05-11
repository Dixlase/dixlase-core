# サプライチェーン防御データ層

> **状態（v0.1）:** バージョン履歴スキーマと保持ポリシーは `^0.1` で凍結。
> これらのレコードはサプライチェーン攻撃検出（署名鍵変更、所有者 ID
> 変更、ファイル整合性ドリフト）とフォレンジック調査の基盤であり、
> プラグイン・テーマ・コアのライフサイクルイベントを通じてサイレントな
> データ消失なしに保持される必要がある。

## なぜ存在するか

署名済みプラグイン・テーマは、上流リポジトリが publisher の鍵や author
の身元をサイレントに置き換えた版に in-place で差し替わる可能性がある。
改ざんログがないと、インシデント後に最も基本的なフォレンジック質問に
答えられない:

> 「プラグイン X の publisher はいつ変わったか、そして誰が更新を適用したか？」

バージョン履歴テーブルはこの質問に答えるために存在する。実運用上は
append-only — 行を編集・削除する管理 UI は存在しない — であり、
親レコードのアンインストール後も行は残存して監査証跡を維持する。

## テーブル

| テーブル                              | 用途                                                   | ステータス |
|--------------------------------------|-------------------------------------------------------|----------|
| `dls_plugin_version_history`         | プラグインごとの install / update / rollback 履歴。   | v0.1.0   |
| `dls_theme_version_history`          | テーマごとの install / update / rollback 履歴。       | v0.1.0 (A-5-A1) |
| `dls_core_version_history`           | コアごとの install / upgrade 履歴。                   | v0.1.0 (A-5-A2) |

各行が記録するもの:

- 対象識別子（プラグインスラッグ、テーマスラッグ、または "core"）。
- `old_*` / `new_*` の `version` / `signing_key_id` / `author_id` のスナップショット。
- 変更フラグ（`signing_key_changed`、`author_id_changed`）。「所有者が変わった
  ものを全部出して」用にインデックス付き。
- ファイル整合性カウンタ（`files_changed_count`、`lines_added`、
  `lines_removed`）。
- `installation_method`（`install` / `update` / `rollback`）。
- `installed_from_url`、`applied_by_id`（適用した member）、`applied_at`。

## 保持ポリシー（`^0.1` で凍結）

**決定:** バージョン履歴行は対象の**スラッグ**（文字列列）をキーとする。
親テーブルへの外部キーではない。親アンインストール時に**行は決して
削除されない**。対象列に **`ON DELETE CASCADE` は付けない**。

具体的には:

- `plugin_version_history.plugin_slug`（文字列） — `plugins` から行が削除
  されても保持される。
- `theme_version_history.theme_slug`（文字列） — 同じ。
- `core_version_history` — 削除されることはない。コアは現実には
  「アンインストール」されないが、同じ append-only ルールが適用される。

### なぜ外部キーを使わないか

`ON DELETE CASCADE` 付きの外部キーは、攻撃者（あるいは慌てた運用者）が
「プラグイン X をアンインストール」をクリックした瞬間にフォレンジック
データを消失させる。`ON DELETE RESTRICT` は履歴が存在する限り正規の
アンインストールをブロックし、運用者を DB レベルの回避策へ追いやる。

スラッグを単なる文字列として保持することで、両方の問題を回避でき、
また同じスラッグでアンインストール → 再インストールされた場合に
レコードを有用なまま保てる（同じスラッグ・同じサプライチェーン origin
→ 履歴連続）。

### 運用者への含意

- 同じスラッグで再インストールされたプラグインは過去の履歴を引き継ぐ。
  これは意図的 — サプライチェーン履歴の連続性は「白紙からやり直す」
  よりも価値が高い。
- **同じスラッグだが異なる `author_id` / `publisher_key_id`** で再インストール
  されたプラグインは、次回更新時に `signing_key_changed` /
  `author_id_changed` 行として表面化する。これは継続的にインストールされた
  プラグインの上流が乗っ取られたのと同じシグナル。
- プラグインを「本当に」忘れたい場合（例: インストールした管理者の GDPR
  right-to-be-forgotten）、運用者は `applied_by_id` 列に対して Privacy
  データ削除フローを実行する。これは opt-in で監査ログ付き。
  スラッグ自体は個人データではない。

### `^0.1` で明示的に**やらない**こと

- バージョン履歴テーブルへの `ON DELETE CASCADE` 付与。
- バージョン履歴行への soft-delete / `uninstalled_at` 列追加（`plugins` に対する
  orphan 行クエリで同等のニーズをスキーマ変更なしで満たせる）。
- 個別の履歴行を purge する管理 UI。

これらをリリース後に緩和することは、フォレンジックポリシーの破壊的変更
であり、`PLUGIN-API.md` の deprecation ポリシーに従う必要がある。

## 参照

- `.backlog/supply-chain-defense-deferred.md` — Phase 1 の設計ノート。
- `app/Models/PluginVersionHistory.php` — Eloquent モデル。
- `app/Services/Plugin/PluginHealthScorer::evaluateSupplyChainMetadata()`
  — `author_id` / `publisher_key_id` 欠落のヘルスチェック。
- `docs/development/plugins/` — プラグイン作者向け `author_id` /
  `publisher_key_id` 宣言ガイダンス。
