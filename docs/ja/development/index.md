# 開発ガイド
Dixlase を拡張・開発するための技術ドキュメントです。

## APIリファレンス

イベントシステム、翻訳、Webhook の仕様です。

- [APIリファレンス](api-reference/)

## 認証

ログイン識別子の検証とロックアウトメカニズムです。

- [認証](auth/)

## コンポーネント

UIコンポーネントの使い方ガイドとスタイリング規約です。

- [コンポーネント](components/)

## Issue

GitHub Issue の書き方・ラベルの付け方・閉じ方と、リリースとの結び付け方です。

- [Issue の書き方](issues.md)

## リビジョン

共通リビジョン API: Revisionable コントラクト、HasRevisions トレイト、
RevisionService、差分プレゼンター、Blade コンポーネントの解説。

- [リビジョン](revisions.md)

## メンバー

ロールベースアクセス制御とパーミッションシステムの設計です。

- [メンバー](members/)

## プラグイン

プラグインパーミッション基盤と開発ガイドラインです。

- [プラグイン](plugins/)

## セキュリティ

CSPコーディングルールとセキュリティ設定レジストリです。

- [セキュリティ](security/)

## システム

API署名、バックアップ、デプロイシステムです。

- [システム](system/)

## 二段階認証

2FAアーキテクチャとUIコンポーネントの仕様です。

- [二段階認証](two-factor/)

## その他

- [Action レイヤー](action-layer.md) - AbstractAction のライフサイクル（`authorize → validate → handle → audit → events`）、`ActionResult` メタデータスキーマ、AuditableTrait と Action の監査責務の使い分け
- [サプライチェーン防御データ層](supply-chain.md) - プラグイン / テーマ / コアのバージョン履歴テーブル、`^0.1` で凍結したスラッグキー（FK・CASCADE なし）保持ポリシー、変更フラグによるサプライチェーン攻撃検出
- [公開識別子の命名規約](naming.md) - API スコープ、permission キー、イベント名、Webhook event type、監査ログ action、プラグインケイパビリティ等の命名フォーマット
- [予約された拡張ポイント](extension-points.md) - ゼロトラストロードマップ Phase 1 で予約されたフック（SecretProvider、RiskEvaluator、PolicyEvaluator、auth.iap、auth.mtls）
- [キャッシュキー命名規約](cache-key-convention.md) - キャッシュキーの命名規約と Builder ヘルパー
- [SDK トレイト依存関係](sdk-trait-dependencies.md) - SDK トレイト依存分析
