# 二段階認証機能の使用方法

このドキュメントでは、Dixlaseの二段階認証システムを他のプラグインで再利用する方法について説明します。

## アーキテクチャ概要

Dixlaseの二段階認証システムは、以下のコンポーネントで構成されています：

### 共通コンポーネント

1. **TwoFactorTrait** (`app/Traits/TwoFactorTrait.php`)
   - 基本的な2FA操作を提供するトレイト
   - コード生成、検証、2FA必要性判定などの核となる機能

2. **TwoFactorHelper** (`app/Helpers/TwoFactorHelper.php`)
   - システム全体の2FA設定とヘルパー機能
   - システム設定取得、認証方法判定、トークンクリーンアップ

3. **EmailAuthenticationService** (`app/Services/EmailAuthenticationService.php`)
   - メール認証専用サービス
   - コード生成・送信、検証、再送信制限、統計情報取得

4. **DeviceAuthenticationService** (`app/Services/DeviceAuthenticationService.php`)
   - 信頼済みデバイス管理
   - デバイス登録、検証、チャレンジ・レスポンス認証

5. **BiometricAuthenticationService** (`app/Services/BiometricAuthenticationService.php`)
   - WebAuthn標準に基づく生体認証
   - Touch ID、Face ID、Windows Hello等をサポート

6. **汎用メールクラス** (`app/Mail/TwoFactorLoginCodeMail.php`)
   - コンテキスト別メール送信（admin, user, default）

### 実装サービス

1. **AdminTwoFactorService** (`app/Services/AdminTwoFactorService.php`)
   - 管理画面用の2FAサービス
   - 全認証方法をサポート

2. **UserTwoFactorService** (`app/Services/UserTwoFactorService.php`)
   - ユーザー管理プラグイン用のサンプル実装

## サポートされる認証方法

### 1. メール認証 (EMAIL)
- 6桁の数字コードをメール送信
- デフォルトの有効期限: 10分
- 使い切りコード（一度使用されると削除）

### 2. デバイス認証 (DEVICE)
- 信頼済みデバイスの登録・管理
- Cookieベースのデバイス識別
- デバイス固有のチャレンジ・レスポンス認証

### 3. 生体認証 (BIOMETRIC)
- WebAuthn標準に基づく実装
- Touch ID、Face ID、Windows Hello等に対応
- HTTPS接続が必要

## 基本的な使用方法

### 1. 既存システムとの連携

Dixlaseの二段階認証システムは、既存の`MemberSetting`システムと完全に連携しています：

- **グローバル設定**: `force_2fa`, `enabled_two_factor_methods`, `default_two_factor_method`
- **ユーザー設定**: `two_factor_mode`, `two_factor_method`（Memberモデルの属性）

### 2. TwoFactorTraitを使用する場合

独自の設定システムを持つプラグインの場合：

```php
<?php

namespace YourPlugin\Services;

use App\Traits\TwoFactorTrait;

class YourTwoFactorService
{
    use TwoFactorTrait;
    
    // 設定値を取得する実装（必須）
    protected function getSettingValue(string $key, $default = null)
    {
        // プラグイン独自の設定システムから値を取得
        return YourPluginSetting::get($key, $default);
    }
    
    public function generateCode($user)
    {
        return $this->generateTwoFactorCode($user);
    }
    
    public function validateCode($user, $code)
    {
        return $this->validateTwoFactorCode($user, $code);
    }
    
    public function isRequired($user)
    {
        $forceSetting = (int) $this->getSettingValue('force_2fa', 0);
        $enabledMethods = $this->getEnabledTwoFactorMethods();
        
        return $this->requiresTwoFactor($user, $forceSetting, $enabledMethods);
    }
}
```

### 2. EmailAuthenticationServiceを使用する場合

メール認証のみを使用する場合：

```php
<?php

namespace YourPlugin\Services;

use App\Services\EmailAuthenticationService;

class YourTwoFactorService
{
    protected EmailAuthenticationService $emailAuth;
    
    public function __construct(EmailAuthenticationService $emailAuth)
    {
        $this->emailAuth = $emailAuth;
    }
    
    public function generateAndSendCode($user)
    {
        return $this->emailAuth->generateAndSendCode($user, 'user');
    }
    
    public function validateCode($user, $code)
    {
        return $this->emailAuth->validateCode($user, $code);
    }
    
    public function resendCode($user)
    {
        return $this->emailAuth->resendCode($user, 'user', 1); // 1分制限
    }
}
```

### 4. 既存システムとの完全連携

Dixlaseの既存設定システムを使用する場合（推奨）：

```php
<?php

namespace YourPlugin\Services;

use App\Helpers\TwoFactorHelper;
use App\Services\EmailAuthenticationService;
use App\Services\DeviceAuthenticationService;
use App\Services\BiometricAuthenticationService;

class YourTwoFactorService
{
    protected TwoFactorHelper $helper;
    protected EmailAuthenticationService $emailAuth;
    protected DeviceAuthenticationService $deviceAuth;
    protected BiometricAuthenticationService $biometricAuth;
    
    public function __construct(
        TwoFactorHelper $helper,
        EmailAuthenticationService $emailAuth,
        DeviceAuthenticationService $deviceAuth,
        BiometricAuthenticationService $biometricAuth
    ) {
        $this->helper = $helper;
        $this->emailAuth = $emailAuth;
        $this->deviceAuth = $deviceAuth;
        $this->biometricAuth = $biometricAuth;
    }
    
    // 二段階認証が必要かどうかを判定（既存設定を使用）
    public function isRequired($user): bool
    {
        return $this->helper->isTwoFactorEnabled($user);
    }
    
    // 使用可能な認証方法を取得（既存設定を使用）
    public function getAvailableMethods(): array
    {
        return $this->helper->getEnabledTwoFactorMethods();
    }
    
    // 有効な認証方法を決定（既存設定を使用）
    public function getEffectiveMethod($user): int
    {
        return $this->helper->getEffectiveAuthMethod($user);
    }
    
    // メール認証コードを生成・送信
    public function generateAndSendCode($user)
    {
        return $this->emailAuth->generateAndSendCode($user, 'user');
    }
    
    // コード検証
    public function validateCode($user, $code)
    {
        return $this->emailAuth->validateCode($user, $code);
    }
    
    // システム設定を取得
    public function getSystemSettings(): array
    {
        return $this->helper->getSystemTwoFactorSettings();
    }
}
```

### 5. TwoFactorHelperを使用する場合

システム設定を共有する場合：

```php
<?php

namespace YourPlugin\Services;

use App\Helpers\TwoFactorHelper;
use App\Services\EmailAuthenticationService;

class YourTwoFactorService
{
    protected TwoFactorHelper $helper;
    protected EmailAuthenticationService $emailAuth;
    
    public function __construct(
        TwoFactorHelper $helper,
        EmailAuthenticationService $emailAuth
    ) {
        $this->helper = $helper;
        $this->emailAuth = $emailAuth;
    }
    
    public function generateAndSendCode($user)
    {
        return $this->emailAuth->generateAndSendCode($user, 'user');
    }
    
    public function validateCode($user, $code)
    {
        return $this->emailAuth->validateCode($user, $code);
    }
    
    public function isEnabled($user)
    {
        return $this->helper->isTwoFactorEnabled($user);
    }
}
```

### 4. 複数認証方法対応の完全実装

```php
<?php

namespace YourPlugin\Services;

use App\Helpers\TwoFactorHelper;
use App\Services\EmailAuthenticationService;
use App\Services\DeviceAuthenticationService;
use App\Services\BiometricAuthenticationService;
use App\Enums\TwoFactorMethod;

class YourAdvancedTwoFactorService
{
    protected TwoFactorHelper $helper;
    protected EmailAuthenticationService $emailAuth;
    protected DeviceAuthenticationService $deviceAuth;
    protected BiometricAuthenticationService $biometricAuth;

    public function __construct(
        TwoFactorHelper $helper,
        EmailAuthenticationService $emailAuth,
        DeviceAuthenticationService $deviceAuth,
        BiometricAuthenticationService $biometricAuth
    ) {
        $this->helper = $helper;
        $this->emailAuth = $emailAuth;
        $this->deviceAuth = $deviceAuth;
        $this->biometricAuth = $biometricAuth;
    }

    public function generate($user, int $method = null)
    {
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user);
        
        return match ($effectiveMethod) {
            TwoFactorMethod::EMAIL->value => $this->generateEmailCode($user),
            TwoFactorMethod::DEVICE->value => $this->generateDeviceChallenge($user),
            TwoFactorMethod::BIOMETRIC->value => $this->generateBiometricChallenge($user),
            default => $this->generateEmailCode($user),
        };
    }

    public function validate($user, $input, int $method = null): bool
    {
        $effectiveMethod = $method ?? $this->helper->getEffectiveAuthMethod($user);
        
        return match ($effectiveMethod) {
            TwoFactorMethod::EMAIL->value => $this->emailAuth->validateCode($user, $input),
            TwoFactorMethod::DEVICE->value => $this->deviceAuth->verifyDeviceChallenge($input),
            TwoFactorMethod::BIOMETRIC->value => $this->biometricAuth->verifyAssertion($user, $input),
            default => $this->emailAuth->validateCode($user, $input),
        };
    }

    private function generateEmailCode($user): string
    {
        return $this->emailAuth->generateAndSendCode($user, 'user');
    }

    private function generateDeviceChallenge($user): array
    {
        return $this->deviceAuth->generateChallenge($user);
    }

    private function generateBiometricChallenge($user): array
    {
        return $this->biometricAuth->generateChallenge($user);
    }
}
```

## メールテンプレートのカスタマイズ

### 1. 独自メールクラスを作成

```php
<?php

namespace YourPlugin\Mail;

use Illuminate\Mail\Mailable;

class YourTwoFactorCodeMail extends Mailable
{
    public string $code;
    
    public function __construct(string $code)
    {
        $this->code = $code;
    }
    
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Plugin 2FA Code',
        );
    }
    
    public function content(): Content
    {
        return new Content(
            markdown: 'your-plugin::emails.two-factor-code',
        );
    }
}
```

### 2. 汎用メールクラスを使用

```php
// コンテキスト別テンプレートを使用
$code = $this->helper->generateAndSendCode(
    $user,
    TwoFactorLoginCodeMail::class,
    null,
    'your_plugin' // resources/views/emails/two-factor-your_plugin-code.blade.php
);
```

## デバイス認証の実装

### 信頼済みデバイスの登録

```php
// ログイン成功後にデバイスを信頼済みとして登録
public function registerTrustedDevice($user, string $deviceName = null): string
{
    return $this->deviceAuth->registerTrustedDevice($user, $deviceName);
}

// 信頼済みデバイスかどうかを確認
public function isTrustedDevice($user): bool
{
    return $this->deviceAuth->isTrustedDevice($user);
}

// 信頼済みデバイス一覧を取得
public function getTrustedDevices($user)
{
    return $this->deviceAuth->getTrustedDevices($user);
}

// 信頼済みデバイスを削除
public function revokeTrustedDevice($user, string $token = null): int
{
    return $this->deviceAuth->revokeTrustedDevice($user, $token);
}
```

## 生体認証の実装

### WebAuthn認証情報の管理

```php
// 生体認証が利用可能かどうかを確認
public function isBiometricAvailable(): bool
{
    return $this->biometricAuth->isAvailable();
}

// 認証情報を登録
public function registerBiometric($user, array $credentialData, string $deviceName = null)
{
    return $this->biometricAuth->registerCredential($user, $credentialData, $deviceName);
}

// 生体認証情報一覧を取得
public function getBiometricCredentials($user)
{
    return $this->biometricAuth->getCredentials($user);
}

// 認証情報を削除
public function revokeBiometric($user, string $credentialId): bool
{
    return $this->biometricAuth->revokeCredential($user, $credentialId);
}
```

## 翻訳キーの追加

### lang/ja/mail.php
```php
'two_factor' => [
    'your_plugin' => [
        'subject' => '【:app_name】プラグイン二段階認証コード',
        'title' => 'プラグイン二段階認証コード',
        'greeting' => 'こんにちは！',
        'message' => 'プラグインログインのための二段階認証コードをお送りします。',
        'code_label' => '認証コード',
        'instructions' => 'このコードをログイン画面で入力してください。',
        'expire_notice' => 'コードの有効期限は :minutes 分間です。',
        'security_notice' => 'このログインに心当たりがない場合は、すぐにパスワードを変更してください。',
        'thanks' => 'よろしくお願いいたします',
    ],
],
```

## データベース要件

二段階認証システムは以下のテーブルを使用します：

- `members_two_factor_tokens`: 認証コードの保存
- `members_settings`: システム設定（force_2fa, enabled_two_factor_methods等）
- `members_trusted_devices`: 信頼済みデバイス（オプション）

ユーザーモデルには以下のカラムが必要です：
- `two_factor_mode`: ユーザーの2FA設定
- `two_factor_method`: ユーザーの認証方法設定

## 利用可能なメソッド

### TwoFactorHelper

- `generateTwoFactorCode($user, $expireMinutes)`: コード生成
- `validateTwoFactorCode($user, $inputCode)`: コード検証
- `isTwoFactorEnabled($user)`: 2FA有効判定
- `getSystemTwoFactorSettings()`: システム設定取得
- `getEffectiveAuthMethod($user)`: 有効な認証方法取得

### TwoFactorTrait

- `requiresTwoFactor($user, $forceSetting, $enabledMethods)`: 2FA必要判定
- `isDifferentEnvironment($user)`: 異なる環境判定
- `isFromTrustedDevice($user)`: 信頼済みデバイス判定

## 注意事項

1. **設定の互換性**: 既存のMemberSetting設定システムを使用する場合は、TwoFactorHelperをそのまま使用できます。
2. **メール送信**: メール送信にはLaravelのMail機能を使用するため、メール設定が必要です。
3. **セキュリティ**: 認証コードは使い切りで、有効期限があります（デフォルト10分）。
4. **ログ**: 全ての2FA操作はログに記録されます。

## サンプル実装

完全なサンプル実装は`app/Services/UserTwoFactorService.php`を参照してください。
