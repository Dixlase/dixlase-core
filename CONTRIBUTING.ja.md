# Dixlase へのコントリビューション

Dixlase にご関心をお寄せいただきありがとうございます。英語版は [CONTRIBUTING.md](./CONTRIBUTING.md) をご覧ください。

## 現在の受付状況 (v0.x)

Dixlase は初期開発期にあります。**外部からの Pull Request は現在受け付けていません。** コードコントリビューションの受付は、コントリビューターライセンス契約（[CLA](./CLA.ja.md)、策定中）の正式法務レビュー完了後に開始します。受付開始時に、本ドキュメントを PR ベースのコントリビューションガイドへ全面差し替えます。

**現在歓迎しているもの（[Issues](https://github.com/Dixlase/dixlase-core/issues) / [Discussions](https://github.com/Dixlase/dixlase-core/discussions) にて）:**

- バグ報告 — 問題の説明、再現手順、期待される動作と実際の動作、環境の詳細（OS、PHP バージョン、ブラウザ）
- 機能提案 — ユースケース、提案する動作、検討した代替案
- ドキュメント・翻訳の誤りの指摘
- 質問・フィードバック

**現在受け付けていないもの:** あらゆる Pull Request（コード・ドキュメント・翻訳）。

> バグ報告に含まれるコードスニペットは **参考情報としてのみ** 取り扱い、修正はメンテナーが独立して実装し直します。これは、CLA レビュー完了までデュアルライセンスモデル上必要な取り扱いです。

## セキュリティ脆弱性

**セキュリティ脆弱性を公開 Issue で報告しないでください。** [SECURITY.md](./SECURITY.md)（または [SECURITY.ja.md](./SECURITY.ja.md)）の手順に従ってください。

## プラグイン・テーマについて

Plugin API（[PLUGIN-API.md](./PLUGIN-API.md) 参照）を介して連携するプラグイン・テーマはコアリポジトリのスコープ外です。作者が完全な著作権を保持し、任意のライセンス（プロプライエタリを含む）で配布できます。PR 受付保留はコアリポジトリにのみ適用されます。

## 翻訳について

Dixlase は英語のソースコメントを正本として配布されます。各言語の辞書は `resources/comment-translations/{locale}/` にあり、`./convert-comments.sh ja` で適用できます（詳細は [`docs/development/comment-translation.md`](./docs/development/comment-translation.md)）。翻訳の問題は Issue でご報告ください — 辞書はソースと一緒にバージョン管理されているため、指摘いただいた修正はメンテナーが容易に反映できます。

## ご質問

GitHub で [Discussion](https://github.com/Dixlase/dixlase-core/discussions) を開くか、info@dixlase.org までメールでどうぞ。

PR 受付開始前であっても、皆様のバグ報告とフィードバックは Dixlase を改善する貴重な貢献です。
