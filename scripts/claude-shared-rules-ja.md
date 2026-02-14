## Dixlase Coding Rules (Shared)

### View Logic Separation
- Bladeテンプレート内で `\App\Enums\*`、`\App\Helpers\*`、`\App\Services\*`、`\App\Models\*` のクラスを直接参照しない
- Enum値・ラベル・オプション配列、ヘルパー結果、サービス結果はすべてコントローラーで準備し、ビュー変数として渡す
- Bladeコンポーネントは必要なデータをpropsとして受け取る（親ビュー/コントローラーからの注入）
- 例外: 全画面共有パーシャル（sidebar等）でのHelper呼び出しは許容する
- **`@php` ブロックはadmin Bladeビューで禁止**（sidebar等の全画面共有パーシャルを除く）
  - ビジネスロジック、データ変換、設定配列の構築はコントローラーまたはPresenterで行う
  - 許容される例外: インライン式（`{{ $var ?? 'default' }}`）、`old()` ヘルパー、単純な変数代入

### Translation Key Scoping
- `lang/*/admin/navigation.php` はサイドバー専用の翻訳ファイル。サイドバー以外のビューから参照しない
- 各ビューは自身の翻訳ファイル（例: `lang/*/admin/settings/base/index.php`）に翻訳キーを定義する
- コンポーネントは `lang/*/components/<component-name>.php` に翻訳キーを定義する

### No Inline Scripts / Styles
- **Bladeビュー内に `<script>` タグや `<style>` タグでインラインコードを書かない**
- JS・CSS は外部ファイルに分離し、`resources/src/` 配下に配置する
- ディレクトリ構成は機能・セクション別:
  - `resources/src/admin/` — 管理画面
  - `resources/src/auth/` — 認証ページ
  - `resources/src/common/` — 共通（全ページ共有）
  - `resources/src/components/` — 再利用コンポーネント
  - `resources/src/front/` — フロント（公開側）
- JS は `js/` サブディレクトリ、SCSS は `scss/` サブディレクトリに配置
- ファイル名はkebab-case（例: `form-color.js`、`ui-modal.scss`）
- 既存のインラインスクリプトは移行途中のためすぐに修正不要だが、**新規作成・修正時は必ず外部ファイル化すること**
