# Dixlase へのコントリビューション

Dixlase へのコントリビューションにご関心をお寄せいただきありがとうございます。英語版は [CONTRIBUTING.md](./CONTRIBUTING.md) をご覧ください。

## 現在のコントリビューション受付状況 (v0.x)

Dixlase は初期開発期にあります。**外部からの Pull Request は現在受け付けていません。** 外部からのコードコントリビューションは、コントリビューターライセンス契約 (CLA) の正式法務レビューが完了次第、再開予定です。

**現在歓迎しているコントリビューション:**

- [Issues](https://github.com/Dixlase/dixlase-core/issues) でのバグ報告
- [Discussions](https://github.com/Dixlase/dixlase-core/discussions) または Issues での機能提案
- ドキュメントの誤り・タイポの指摘 (Issues)
- 質問・フィードバック (Discussions)

**現在受け付けていないコントリビューション:**

- ソースコードの Pull Request (CLA 法務レビュー完了後に再開)
- ドキュメントの Pull Request (代わりに Issues での指摘をお願いします)
- 翻訳の Pull Request (CLA 法務レビュー完了後に再開)

将来の PR ベースのコントリビューションフローは [`CONTRIBUTING-FUTURE.md`](./CONTRIBUTING-FUTURE.md) に記載されています。当該文書は現時点では情報提供を目的としており、CLA 法務レビュー完了後に運用開始されます。

## バグ報告

バグを報告する際は、以下を含めてください:

- 問題の明確な説明
- 再現手順
- 期待される動作と実際の動作
- 環境の詳細(OS、PHP バージョン、ブラウザなど)
- 関連するログやエラーメッセージ

> **バグ報告に含まれるコードスニペットの取り扱い**: バグ修正のためにコードの提案を含めていただくことは歓迎しますが、それは **参考情報としてのみ取り扱われます**。メンテナーは独立して修正を実装し直しますので、提供いただいたスニペットがそのままコミットされることは通常ありません。これは、Dixlase のデュアルライセンスモデルの下では、CLA 法務レビュー完了までは外部からのコードコントリビューションを受け付けることができないためです。

## 機能提案

新機能を提案する際は、Issue を開いて以下を記載してください:

- 当該機能が対応するユースケースまたは課題
- 提案する動作または API
- 検討した代替案

大規模または設計重視の提案については、Issue を開く前に [GitHub Discussions](https://github.com/Dixlase/dixlase-core/discussions) でスレッドを始めることを推奨します。

## セキュリティ脆弱性の報告

**セキュリティ脆弱性を公開 Issue で報告しないでください。** 代わりに、[SECURITY.md](./SECURITY.md)(または [SECURITY.ja.md](./SECURITY.ja.md))の手順に従ってください。

## プラグイン・テーマについて

Dixlase の Plugin API([PLUGIN-API.md](./PLUGIN-API.md) 参照)を介して連携するプラグイン・テーマは、コアリポジトリのスコープ外です。これらの作者は完全な著作権を保持し、独自ライセンス(プロプライエタリを含みます)で配布できます。現在の PR 受付保留はあくまで Dixlase コアリポジトリにのみ適用されます。

## 翻訳について

Dixlase は **英語のソースコメントを正本** として配布されます。各言語のコメントは `resources/comment-translations/{locale}/`(プラグイン・テーマも同パス)に格納され、`./convert-comments.sh ja` で開発環境のソースを in-place で日本語に切り替えられます。アーキテクチャ全体は [`docs/development/comment-translation.md`](./docs/development/comment-translation.md) を参照してください。

翻訳に問題を見つけた場合は、PR ではなく Issue として報告してください。翻訳の Pull Request はコードの PR と同じく、CLA の法務レビュー完了後に受付を再開します。それまでの間、辞書ファイル(`resources/comment-translations/{locale}/...`)はソースと一緒にバージョン管理されているので、Issue で指摘いただいた修正はメンテナーが容易に再現・反映できます。

## ご質問

コントリビューションに関するご質問は、以下までお気軽にどうぞ:

- GitHub で [Discussion](https://github.com/Dixlase/dixlase-core/discussions) を開く
- office@exc-d.com までメール

PR 受付再開前であっても、皆様のバグ報告とフィードバックは Dixlase を改善するための貴重な貢献です。
