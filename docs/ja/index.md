# Dixlase ドキュメント

> **[English version](../index.md)**

Dixlase CMS のドキュメントへようこそ。このドキュメントでは、Dixlase プラットフォームのアーキテクチャ、機能、開発ガイドを紹介します。

## 全体設定

### 基本設定

- [管理モード仕様](settings/base/admin-mode-specification.md) - 管理モード機能の詳細と動作

### セキュリティ設定

- [CAPTCHA 使い方](settings/security/captcha-usage.md) - CAPTCHA 統合ガイド
- [CSP ガイド](settings/security/csp-guide.md) - Content Security Policy 完全ガイド
- [ファイル整合性チェック](settings/security/file-integrity-check.md) - コアファイル改ざん検出
- [ログイン通知システム](settings/security/login-notification-system.md) - ログイン通知アーキテクチャ
- [パスワード辞書攻撃対策](settings/security/password-dictionary-attack-protection.md) - Have I Been Pwned 連携
- [セーフモードガイド](settings/security/safe-mode-guide.md) - クラッシュリカバリー用マルチレベルセーフモード
- [2FA ガイド](settings/security/two-factor-authentication-guide.md) - 二段階認証実装ガイド

### システム設定

- [データベースクリーンアップ](settings/system/database-cleanup.md) - データベースメンテナンスとクリーンアップ

## API

- [イベント API](api-reference/events.md) - イベントシステム仕様
- [翻訳 API](api-reference/translation.md) - 翻訳システム仕様
- [Webhook](api-reference/webhooks.md) - Webhook 連携ガイド

## 認証

- [識別子チェック](auth/identifier-check-usage.md) - ログイン識別子の検証
- [ログインロックアウト](auth/login-lockout-usage.md) - ブルートフォース攻撃対策のログインロックアウト

## コンポーネント

- [管理画面ページネーション](components/admin-pagination.md) - ページネーションコンポーネントの使い方
- [コンポーネントスタイリングガイド](components/components-styling-guide.md) - コンポーネントのスタイリング規約
- [コンテンツファイルストレージ](components/content-file-storage.md) - ファイルベースのコンテンツ保存ガイド
- [メディアセレクター](components/media-selector.md) - メディアセレクターモーダルの使い方
- [保存ボタン](components/save-button-usage.md) - 保存ボタンとモーダルのバリエーション

## メンバー

- [RBAC パーミッション](members/rbac-permissions.md) - ロールベースアクセス制御モデル
- [ロール・パーミッションシステム](members/role-permission-system.md) - パーミッション設定システムの設計

## プラグイン

- [パーミッションガイドライン](plugins/permission-guidelines.md) - プラグインパーミッション基盤ガイドライン

## セキュリティ

- [Alpine.js CSP コーディングルール](security/alpine-csp-coding-rules.md) - CSP 互換の Alpine.js パターン
- [CAPTCHA コマンド](security/captcha-commands.md) - CAPTCHA 管理 CLI コマンド
- [緊急ロックダウン](security/emergency-lockdown.md) - 緊急ロックダウンシステム
- [セキュリティ設定レジストリ](security/security-settings-registry.md) - 統合セキュリティ設定管理

## システム

- [API 署名仕様](system/api-signature-spec.md) - HMAC-SHA256 API 署名仕様
- [監査ログ整合性](system/audit-log-integrity.md) - ハッシュチェーンによる改ざん検出
- [監査ログ使い方](system/audit-log-usage.md) - 監査ログ API ガイド
- [バックアップ](system/backup.md) - バックアップシステムコマンド
- [デプロイ](system/deployment.md) - マルチステージデプロイシステム

## 二段階認証

- [2FA アーキテクチャ](two-factor/two-factor-authentication-architecture.md) - 技術仕様
- [2FA UI コンポーネント](two-factor/two-factor-ui-components.md) - フロントエンドコンポーネント
