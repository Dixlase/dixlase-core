# 二段階認証システムの再利用ガイド

このドキュメントでは、Dixlaseの二段階認証システムをユーザー管理プラグインなどで再利用する方法について説明します。

## 概要

二段階認証システムは以下のコンポーネントに分離されており、再利用可能です：

- `TwoFactorTrait`: 基本的な2FA機能を提供するトレイト
- `TwoFactorHelper`: 設定管理とメール送信を担当するヘルパークラス
- `TwoFactorLoginCodeMail`: 汎用的なメールクラス
- メールテンプレート: コンテキスト別のテンプレート

## 基本的な使用方法

### 1. サービスクラスの作成

ユーザー管理プラグイン用のサービスクラスを作成します：

```php
<?php

namespace YourPlugin\Services;

use App\Helpers\TwoFactorHelper;
use App\Mail\TwoFactorLoginCodeMail;

class YourPluginTwoFactorService
{
    protected TwoFactorHelper $helper;

    public function __construct(TwoFactorHelper $helper)
    {
        $this->helper = $helper;
    }

    public function generate($user): string
    {
        return $this->helper->generateAndSendCode(
            $user,
            TwoFactorLoginCodeMail::class,
            null,
            'user' // または 'your_context'
        );
    }

    public function validate($user, string $inputCode): bool
    {
        return $this->helper->validateTwoFactorCode($user, $inputCode);
    }

    public function has($user): bool
    {
        return $this->helper->isTwoFactorEnabled($user);
    }
}
```

### 2. コントローラーでの使用

ログインコントローラーでの実装例：

```php
<?php

namespace YourPlugin\Controllers;

use YourPlugin\Services\YourPluginTwoFactorService;

class LoginController extends Controller
{
    public function store(Request $request)
    {
        // 通常の認証処理...
        
        $twoFactor = app(YourPluginTwoFactorService::class);
        if ($twoFactor->has($user)) {
            session([
                'login.id' => $user->getAuthIdentifier(),
                'login.remember' => $request->boolean('remember'),
            ]);

            $twoFactor->generate($user); // コード生成 + メール送信

            return redirect()->route('your-plugin.two-factor.login');
        }

        // 2FA不要なら即ログイン
        Auth::guard('your_guard')->login($user, $request->boolean('remember'));
        return redirect()->intended(route('your-plugin.dashboard'));
    }
}
```

### 3. 二段階認証確認画面

```php
public function twoFactorStore(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:6',
    ]);

    $userId = session('login.id');
    $user = YourUserModel::find($userId);

    $twoFactor = app(YourPluginTwoFactorService::class);
    
    if (!$twoFactor->validate($user, $request->code)) {
        return back()->withErrors(['code' => '認証コードが正しくありません。']);
    }

    // ログイン成功
    Auth::guard('your_guard')->login($user, session('login.remember'));
    session()->forget(['login.id', 'login.remember']);
    
    return redirect()->intended(route('your-plugin.dashboard'));
}
```

## カスタマイズ方法

### 1. 独自の設定システムを使用する場合

`TwoFactorTrait`を使用して独自のヘルパークラスを作成：

```php
<?php

namespace YourPlugin\Helpers;

use App\Traits\TwoFactorTrait;

class YourPluginTwoFactorHelper
{
    use TwoFactorTrait;

    protected function getSettingValue(string $key, $default = null)
    {
        // 独自の設定システムを使用
        return YourPluginSetting::getValue($key, $default);
    }
}
```

### 2. 独自のメールテンプレートを使用する場合

独自のメールクラスを作成：

```php
<?php

namespace YourPlugin\Mail;

use Illuminate\Mail\Mailable;

class YourPluginTwoFactorMail extends Mailable
{
    public string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'your-plugin::emails.two-factor-code',
        );
    }
}
```

### 3. 独自のコンテキスト用翻訳を追加

`lang/ja/mail.php`と`lang/en/mail.php`に翻訳を追加：

```php
'two_factor' => [
    'your_context' => [
        'subject' => '【:app_name】あなたのプラグイン二段階認証コード',
        'greeting' => 'こんにちは！',
        'message' => 'あなたのプラグインログインのための二段階認証コードをお送りします。',
        // ...
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
