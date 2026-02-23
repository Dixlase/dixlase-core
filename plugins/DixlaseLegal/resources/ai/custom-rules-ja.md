# DixlaseLegal プラグイン - カスタムルール

## コアレジストリとの連携

### LegalPageService（コア側）
- `app/Services/LegalPageService.php` がコアの法務ページレジストリ
- `dls_base_settings` テーブルに `legal_page_url:{slug}` キーで URL を保存
- `BaseSettingRepositoryInterface` 経由でアクセス（10分キャッシュ付き）
- プラグインの `config/admin/legal-pages.php` を自動読み込みしてマージ

### 設定オーバーライドの仕組み
- `plugins/DixlaseLegal/config/admin/legal-pages.php` でコアのページ種別をオーバーライド可能
- `required: true` を設定すると、そのページ種別が必須に昇格
- `required_by` 配列でどのプラグインが要求したかを追跡
- 独自のページ種別も追加可能

### コアのデフォルトページ種別（4種）
| slug | icon | required |
|------|------|----------|
| `privacy-policy` | `fas fa-shield-alt` | false |
| `terms-of-service` | `fas fa-file-contract` | false |
| `site-policy` | `fas fa-globe` | false |
| `cookie-policy` | `fas fa-cookie-bite` | false |

## PrivacyPolicyProviderInterface 実装方針

- `app/Contracts/PluginIntegration/PrivacyPolicyProviderInterface.php` を実装する
- `LegalPageService` を内部で使用して `privacy-policy` の URL を返す
- ServiceProvider で `PrivacyPolicyProviderInterface` にバインドする
- 他プラグイン（DixlaseInquiry 等）がこのインターフェース経由でプライバシーポリシー URL を取得

## 次セッションで実装すべき機能リスト

### 必須（MVP）
1. **管理画面 - 法務ページ URL 設定ページ**
   - `LegalPageService::getPageTypes()` で種別一覧を表示
   - 各ページ種別の URL 入力フォーム
   - `LegalPageService::setUrl()` で保存
   - ルート: `admin.legal.*`

2. **PrivacyPolicyProviderInterface の実装**
   - `DixlaseLegalPrivacyPolicyProvider` クラス作成
   - ServiceProvider でバインド

3. **ナビゲーション設定**
   - `config/admin/navigation.php` にメニュー項目追加
   - 翻訳ファイル（`lang/{en,ja}/admin/navigation.php`）

4. **ロール設定**
   - `config/admin/roles.php` に権限定義

### 拡張（将来）
- 法務ページのプレビュー機能
- 法務ページの公開/非公開切り替え
- 法務ページの更新通知
- 法務ページのバージョン管理

## このセッションの設計決定の要約

1. **コアにレジストリ、プラグインで管理**: `LegalPageService` はコアに配置し、管理UIはプラグインで実装
2. **dls_base_settings テーブルを使用**: 新規テーブルは作成せず、既存の設定テーブルを活用
3. **キープレフィックス**: `legal_page_url:` + slug（例: `legal_page_url:privacy-policy`）
4. **プラグインオーバーライド**: `config/admin/legal-pages.php` パターンで `required` 昇格を実現
5. **シングルトン登録**: `AppServiceProvider::register()` で `LegalPageService` をシングルトン化
6. **翻訳キーパターン**: `admin/settings/systems/legal-pages.{slug_underscore}.{name|description}`
