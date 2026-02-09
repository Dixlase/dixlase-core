# パスワード辞書攻撃対策機能の使用方法

Dixlaseのパスワード辞書攻撃対策機能は、Have I Been Pwned APIを使用してパスワードが漏洩データベースに含まれていないかをチェックし、安全でないパスワードの使用を防ぐ機能です。

## 概要

この機能は以下のコンポーネントで構成されています：

- **PwnedPasswordTrait**: Have I Been Pwned API連携機能
- **PasswordSecurityHelper**: パスワード検証ヘルパークラス
- **NotPwnedPassword**: バリデーションルール
- **セキュリティ設定**: 管理画面での有効/無効切り替え

## 設定方法

### 1. セキュリティ設定での有効化

管理画面 > セキュリティ設定 > パスワード辞書攻撃対策設定で機能を有効にします。

```
辞書攻撃対策: 有効/無効
```

### 2. データベース設定

設定は `security_settings` テーブルの `pwned_password_check_enabled` キーで管理されます。

```php
// 設定の取得
$enabled = SecuritySetting::get('pwned_password_check_enabled', false);

// 設定の保存
SecuritySetting::set('pwned_password_check_enabled', true);
```

## 使用方法

### 1. バリデーションルールとして使用

```php
use App\Rules\NotPwnedPassword;

// フォームリクエストクラスで使用
public function rules(): array
{
    return [
        'password' => ['required', 'string', 'min:8', new NotPwnedPassword()],
    ];
}

// カスタム設定キーを使用する場合
public function rules(): array
{
    return [
        'password' => ['required', 'string', 'min:8', NotPwnedPassword::using('custom_setting_key')],
    ];
}
```

### 2. トレイトを使用した直接チェック

```php
use App\Traits\PwnedPasswordTrait;

class YourController extends Controller
{
    use PwnedPasswordTrait;

    public function checkPassword($password)
    {
        // パスワードが漏洩しているかチェック
        $result = $this->checkPwnedPassword($password);
        
        if ($result['is_pwned']) {
            // パスワードが漏洩している場合の処理
            return "このパスワードは {$result['count']} 回漏洩しています";
        }
        
        return "パスワードは安全です";
    }
}
```

### 3. ヘルパークラスを使用した包括的チェック

```php
use App\Helpers\PasswordSecurityHelper;

// 基本的なパスワード検証
$result = PasswordSecurityHelper::validatePassword($password);

if (!$result['is_valid']) {
    foreach ($result['errors'] as $error) {
        echo $error . "\n";
    }
}

// パスワード強度と辞書攻撃対策の統合チェック
$strengthRequirements = [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_symbol' => false,
];

$result = PasswordSecurityHelper::comprehensivePasswordCheck(
    $password, 
    $strengthRequirements
);

if (!$result['is_valid']) {
    // 強度エラー
    foreach ($result['strength_errors'] as $error) {
        echo "強度エラー: " . $error . "\n";
    }
    
    // セキュリティエラー（辞書攻撃対策）
    foreach ($result['security_errors'] as $error) {
        echo "セキュリティエラー: " . $error . "\n";
    }
}
```

## ユーザー管理プラグインでの使用

### 1. 独自設定システムを使用する場合

```php
use App\Traits\PwnedPasswordTrait;

class UserPasswordController extends Controller
{
    use PwnedPasswordTrait;

    public function validateUserPassword($password)
    {
        // 独自の設定キーを使用
        $safetyCheck = $this->validatePasswordSafety($password, 'user_pwned_password_check_enabled');
        
        if (!$safetyCheck['is_safe']) {
            throw new ValidationException($safetyCheck['message']);
        }
    }
}
```

### 2. バリデーションルールを使用する場合

```php
use App\Rules\NotPwnedPassword;

class UserRegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
                // ユーザー用の設定キーを指定
                NotPwnedPassword::using('user_pwned_password_check_enabled')
            ],
        ];
    }
}
```

## API仕様

### Have I Been Pwned API

この機能は Have I Been Pwned API v3 を使用します：

- **エンドポイント**: `https://api.pwnedpasswords.com/range/{hash_prefix}`
- **方式**: k-Anonymity（パスワードの完全なハッシュは送信されません）
- **プライバシー**: パスワード自体は送信されず、SHA-1ハッシュの最初の5文字のみが使用されます

### セキュリティ

1. **プライバシー保護**: パスワード自体は外部に送信されません
2. **k-Anonymity**: ハッシュの一部のみを送信してプライバシーを保護
3. **エラーハンドリング**: APIエラー時はパスワードを許可（デフォルト）
4. **タイムアウト**: 10秒のタイムアウト設定

## 設定オプション

### NotPwnedPasswordルール

```php
// デフォルト設定
new NotPwnedPassword()

// カスタム設定キー
new NotPwnedPassword('custom_setting_key')

// APIエラー時にバリデーションを失敗させる
new NotPwnedPassword('pwned_password_check_enabled', false)

// 静的ファクトリーメソッド
NotPwnedPassword::using('custom_key', false)
```

### PwnedPasswordTrait メソッド

```php
// パスワードチェック
$result = $this->checkPwnedPassword($password);
// 戻り値: ['is_pwned' => bool, 'count' => int, 'error' => string|null]

// 設定確認
$enabled = $this->isPwnedPasswordCheckEnabled($settingKey);

// 安全性チェック
$result = $this->validatePasswordSafety($password, $settingKey);
// 戻り値: ['is_safe' => bool, 'message' => string, 'pwned_info' => array]
```

## エラーハンドリング

### APIエラー

APIエラーが発生した場合、デフォルトではパスワードは許可されます：

```php
// APIエラー時の動作を変更
$rule = new NotPwnedPassword('pwned_password_check_enabled', false); // エラー時に失敗
```

### ログ出力

エラーは自動的にログに記録されます：

```php
// 警告レベル（API失敗時）
Log::warning('Have I Been Pwned API request failed', $context);

// エラーレベル（例外発生時）
Log::error('Pwned password check failed', $context);
```

## 翻訳

### 日本語 (lang/ja/validation.php)

```php
'pwned_password_found' => 'このパスワードは過去に :count 回データ漏洩で発見されており、安全ではありません。別のパスワードを選択してください。',
'pwned_password_api_error' => 'パスワード安全性チェック中にエラーが発生しましたが、パスワードは受け入れられました。',
```

### 英語 (lang/en/validation.php)

```php
'pwned_password_found' => 'This password has been found :count times in data breaches and is not safe. Please choose a different password.',
'pwned_password_api_error' => 'An error occurred while checking password safety, but the password has been accepted.',
```

## 統合済み箇所

この機能は以下の箇所で既に統合されています：

1. **メンバー作成・編集** (`AdminSettingsMemberStoreRequest`)
2. **プロフィール設定でのパスワード変更** (`AdminProfileController`)

## パフォーマンス考慮事項

1. **API呼び出し**: 外部APIを呼び出すため、ネットワーク遅延が発生する可能性があります
2. **タイムアウト**: 10秒のタイムアウトが設定されています
3. **キャッシュ**: 現在キャッシュは実装されていませんが、必要に応じて追加可能です

## トラブルシューティング

### よくある問題

1. **API接続エラー**: ネットワーク接続を確認してください
2. **設定が反映されない**: ブラウザのキャッシュをクリアしてください
3. **バリデーションが動作しない**: 設定が有効になっているか確認してください

### デバッグ

```php
// デバッグ情報の取得
$helper = new PasswordSecurityHelper();
$settings = $helper->getPwnedPasswordSettings();
var_dump($settings);
```

## 今後の拡張

1. **キャッシュ機能**: チェック結果のキャッシュ
2. **統計機能**: 漏洩パスワード検出の統計
3. **カスタムAPI**: 独自の漏洩パスワードデータベース対応
4. **バッチ処理**: 既存パスワードの一括チェック
