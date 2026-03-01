# 二段階認証（2FA）実践ガイド

このドキュメントは、Dixlaseの二段階認証システムをプラグインやカスタム実装で使用する際の実践的なガイドです。

> **📚 関連ドキュメント**
> - [2FAアーキテクチャ](../../development/two-factor/two-factor-authentication-architecture.md) - 技術仕様と詳細
> - [UIコンポーネント](../../development/two-factor/two-factor-ui-components.md) - フロントエンド実装

---

## クイックスタート

### 最小限の実装（5ステップ）

```php
// 1. TwoFaAuthenticationTraitを使用
class MyTwoFactorController extends Controller
{
    use TwoFaAuthenticationTrait;
    
    // 2. 必須メソッドを実装
    protected function getSettingModelClass(): string
    {
        return \App\Models\MemberSetting::class;
    }
    
    protected function getTwoFaService()
    {
        return app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => 'admin'
        ]);
    }
    
    protected function getTwoFaRoutePrefix(): string
    {
        return 'admin';
    }
    
    protected function getSessionPrefix(): string
    {
        return 'admin_two_fa';
    }
    
    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }
    
    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }
}

// 3. ルートを定義
Route::middleware('guest:member')->group(function () {
    Route::get('/two-fa/email', [MyTwoFactorController::class, 'showEmailChallenge'])
        ->name('admin.two-fa.email.show');
    Route::post('/two-fa/email/verify', [MyTwoFactorController::class, 'verifyEmail'])
        ->name('admin.two-fa.email.verify');
    Route::post('/two-fa/email/resend', [MyTwoFactorController::class, 'resendEmail'])
        ->name('admin.two-fa.email.resend');
});

// 4. ビューを作成
// resources/views/admin/two-fa/email-challenge.blade.php
@extends('layouts.auth')
@section('content')
    <x-two-factor-challenge 
        :action="route('admin.two-fa.email.verify')"
        :resend-action="route('admin.two-fa.email.resend')"
    />
@endsection

// 5. ログイン処理で2FAチェック
$helper = app(\App\Helpers\TwoFaHelper::class);
if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
    // 2FA認証画面にリダイレクト
    session(['admin_two_fa.id' => $user->id]);
    return redirect()->route('admin.two-fa.email.show');
}
```

---

## 実装パターン

### パターン1: TwoFaAuthenticationTraitを使用（推奨）

**メリット:**
- ✅ 認証フロー全体が実装済み
- ✅ セッション管理が自動
- ✅ ロックアウト対応済み
- ✅ 複数認証方法のサポート

**実装例:**

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\TwoFa\TwoFaAuthenticationTrait;

class AdminTwoFactorController extends Controller
{
    use TwoFaAuthenticationTrait;
    
    protected function getSettingModelClass(): string
    {
        return \App\Models\MemberSetting::class;
    }
    
    protected function getTwoFaService()
    {
        return app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => 'admin'
        ]);
    }
    
    protected function getTwoFaRoutePrefix(): string
    {
        return 'admin';
    }
    
    protected function getSessionPrefix(): string
    {
        return 'admin_two_fa';
    }
    
    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }
    
    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }
}
```

**ルート定義:**

```php
Route::middleware('guest:member')->prefix('admin')->name('admin.')->group(function () {
    // メール認証
    Route::get('/two-fa/email', [AdminTwoFactorController::class, 'showEmailChallenge'])
        ->name('two-fa.email.show');
    Route::post('/two-fa/email/verify', [AdminTwoFactorController::class, 'verifyEmail'])
        ->name('two-fa.email.verify');
    Route::post('/two-fa/email/resend', [AdminTwoFactorController::class, 'resendEmail'])
        ->name('two-fa.email.resend');
    
    // Passkey認証
    Route::get('/two-fa/passkey', [AdminTwoFactorController::class, 'showPasskeyChallenge'])
        ->name('two-fa.passkey.show');
    Route::post('/two-fa/passkey/challenge', [AdminTwoFactorController::class, 'getPasskeyChallenge'])
        ->name('two-fa.passkey.challenge');
    Route::post('/two-fa/passkey/verify', [AdminTwoFactorController::class, 'verifyPasskey'])
        ->name('two-fa.passkey.verify');
    
    // 回復コード認証
    Route::get('/two-fa/recovery', [AdminTwoFactorController::class, 'showRecoveryCodeChallenge'])
        ->name('two-fa.recovery.show');
    Route::post('/two-fa/recovery/verify', [AdminTwoFactorController::class, 'verifyRecoveryCode'])
        ->name('two-fa.recovery.verify');
});
```

---

### パターン2: TwoFaServiceを直接使用

**メリット:**
- ✅ より細かい制御が可能
- ✅ カスタムフローに対応

**実装例:**

```php
namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomTwoFactorController extends Controller
{
    protected $twoFaService;
    
    public function __construct()
    {
        $this->twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => \App\Models\MemberSetting::class,
            'context' => 'custom'
        ]);
    }
    
    public function showChallenge()
    {
        $userId = session('custom_two_fa.id');
        if (!$userId) {
            return redirect()->route('custom.login');
        }
        
        $user = \App\Models\Member::find($userId);
        
        // ロックアウトチェック
        $lockoutStatus = $this->twoFaService->checkLockout($user);
        if ($lockoutStatus['locked_out']) {
            return back()->withErrors([
                'code' => "ロックアウト中です。残り{$lockoutStatus['remaining_minutes']}分お待ちください。"
            ]);
        }
        
        // 利用可能な認証方法を取得
        $methods = $this->twoFaService->getAvailableMethods($user);
        
        return view('custom.two-fa.challenge', [
            'methods' => $methods,
            'remaining_attempts' => $lockoutStatus['remaining_attempts'] ?? null
        ]);
    }
    
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);
        
        $userId = session('custom_two_fa.id');
        if (!$userId) {
            return redirect()->route('custom.login');
        }
        
        $user = \App\Models\Member::find($userId);
        
        // ロックアウトチェック
        $lockoutStatus = $this->twoFaService->checkLockout($user);
        if ($lockoutStatus['locked_out']) {
            return back()->withErrors([
                'code' => "ロックアウト中です。"
            ]);
        }
        
        // コード検証
        if ($this->twoFaService->validate($user, $request->code)) {
            // 認証成功
            session()->forget('custom_two_fa');
            Auth::guard('member')->login($user);
            
            return redirect()->route('custom.dashboard');
        }
        
        // 認証失敗
        return back()->withErrors([
            'code' => '認証コードが正しくありません。'
        ]);
    }
}
```

---

### パターン3: 個別サービスを使用

**メリット:**
- ✅ 最も細かい制御が可能
- ✅ 特定機能のみ使用可能

**実装例:**

```php
namespace App\Http\Controllers\Custom;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomCodeController extends Controller
{
    public function sendCode(Request $request)
    {
        $user = $request->user();
        
        // コード生成 + メール送信
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);
        
        try {
            $code = $codeService->generateAndSend(
                $user,
                \App\Mail\TwoFaCodeMail::class,
                10, // 10分間有効
                'custom'
            );
            
            return response()->json([
                'success' => true,
                'message' => 'コードを送信しました'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'コード送信に失敗しました'
            ], 500);
        }
    }
    
    public function verifyCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);
        
        $user = $request->user();
        
        // 試行回数チェック
        $attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
        if ($attemptService->isLockedOut($user)) {
            $remainingTime = $attemptService->getRemainingLockoutTime($user);
            return response()->json([
                'success' => false,
                'message' => "ロックアウト中です。残り{$remainingTime}分"
            ], 429);
        }
        
        // コード検証
        $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);
        $success = $codeService->validate($user, $request->code);
        
        // 試行を記録
        $attemptService->recordAttempt($user, 'email', $success);
        
        if ($success) {
            return response()->json([
                'success' => true,
                'message' => '認証成功'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'コードが正しくありません'
        ], 400);
    }
}
```

---

## ログインフローへの統合

### 基本的な統合

```php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        // 認証情報の検証
        if (!Auth::guard('member')->attempt($credentials, $request->filled('remember'))) {
            return back()->withErrors([
                'email' => '認証情報が正しくありません。'
            ]);
        }
        
        $user = Auth::guard('member')->user();
        
        // 2FAが必要かチェック
        $helper = app(\App\Helpers\TwoFaHelper::class);
        if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
            // 一旦ログアウト
            Auth::guard('member')->logout();
            
            // セッションに情報を保存
            session([
                'admin_two_fa.id' => $user->id,
                'admin_two_fa.remember' => $request->filled('remember')
            ]);
            
            // コード生成 + メール送信
            $twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
                'settingModelClass' => \App\Models\MemberSetting::class,
                'context' => 'admin'
            ]);
            
            try {
                $twoFaService->generate($user);
            } catch (\Exception $e) {
                \Log::error('[Login] 2FA code generation failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                
                return back()->withErrors([
                    'email' => '2FA認証の準備に失敗しました。'
                ]);
            }
            
            // 2FA認証画面にリダイレクト
            $method = $twoFaService->getEffectiveAuthMethod($user);
            $route = match($method) {
                \App\Enums\TwoFaMethod::EMAIL->value => 'admin.two-fa.email.show',
                \App\Enums\TwoFaMethod::PASSKEY->value => 'admin.two-fa.passkey.show',
                default => 'admin.two-fa.email.show',
            };
            
            return redirect()->route($route);
        }
        
        // 2FA不要な場合はログイン完了
        $request->session()->regenerate();
        return redirect()->intended('admin/dashboard');
    }
}
```

---

## Passkey認証の実装

### 登録フロー

```php
namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PasskeyController extends Controller
{
    protected $passkeyService;
    
    public function __construct()
    {
        $this->passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);
    }
    
    // 登録チャレンジ生成
    public function registerOptions(Request $request)
    {
        $user = Auth::guard('member')->user();
        
        // HTTPS接続チェック
        if (!$this->passkeyService->isAvailable()) {
            return response()->json([
                'success' => false,
                'message' => 'HTTPS接続が必要です'
            ], 400);
        }
        
        try {
            $options = $this->passkeyService->generateRegistrationChallenge($user);
            
            return response()->json([
                'success' => true,
                'options' => $options
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] Registration challenge generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'チャレンジ生成に失敗しました'
            ], 500);
        }
    }
    
    // 登録処理
    public function register(Request $request)
    {
        $request->validate([
            'credential' => 'required|array',
            'device_name' => 'nullable|string|max:255'
        ]);
        
        $user = Auth::guard('member')->user();
        
        try {
            $credential = $this->passkeyService->registerCredential(
                $user,
                $request->input('credential'),
                $request->input('device_name')
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Passkeyを登録しました',
                'credential' => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'created_at' => $credential->created_at->format('Y-m-d H:i')
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] Registration failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Passkey登録に失敗しました'
            ], 500);
        }
    }
    
    // 削除処理
    public function revoke(Request $request, string $credentialId)
    {
        $user = Auth::guard('member')->user();
        
        if ($credentialId === 'all') {
            $count = $this->passkeyService->revokeAllCredentials($user);
            
            return response()->json([
                'success' => true,
                'message' => "{$count}個のPasskeyを削除しました"
            ]);
        }
        
        if ($this->passkeyService->revokeCredential($user, $credentialId)) {
            return response()->json([
                'success' => true,
                'message' => 'Passkeyを削除しました'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Passkeyが見つかりません'
        ], 404);
    }
}
```

### 認証フロー

```php
// TwoFaAuthenticationTraitを使用している場合は自動実装済み
// カスタム実装の場合:

public function getPasskeyChallenge(Request $request)
{
    $userId = session('admin_two_fa.id');
    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'セッションが無効です'
        ], 401);
    }
    
    $user = \App\Models\Member::find($userId);
    
    try {
        $options = $this->passkeyService->generateAuthenticationChallenge($user);
        
        return response()->json([
            'success' => true,
            'options' => $options
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'チャレンジ生成に失敗しました'
        ], 500);
    }
}

public function verifyPasskey(Request $request)
{
    $request->validate([
        'credential' => 'required|array'
    ]);
    
    $userId = session('admin_two_fa.id');
    if (!$userId) {
        return response()->json([
            'success' => false,
            'message' => 'セッションが無効です'
        ], 401);
    }
    
    $user = \App\Models\Member::find($userId);
    
    // 試行回数チェック
    $attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
    if ($attemptService->isLockedOut($user)) {
        return response()->json([
            'success' => false,
            'message' => 'ロックアウト中です'
        ], 429);
    }
    
    // 認証検証
    $success = $this->passkeyService->verifyAssertion($user, $request->input('credential'));
    
    // 試行を記録
    $attemptService->recordAttempt($user, 'passkey', $success);
    
    if ($success) {
        // 認証成功
        $remember = session('admin_two_fa.remember', false);
        session()->forget('admin_two_fa');
        
        Auth::guard('member')->login($user, $remember);
        
        return response()->json([
            'success' => true,
            'redirect' => route('admin.dashboard')
        ]);
    }
    
    return response()->json([
        'success' => false,
        'message' => '認証に失敗しました'
    ], 400);
}
```

---

## 回復コードの実装

### 生成・表示

```php
namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecoveryCodeController extends Controller
{
    protected $recoveryCodeService;
    
    public function __construct()
    {
        $this->recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
    }
    
    // 生成
    public function generate(Request $request)
    {
        $user = Auth::guard('member')->user();
        
        // 既に存在する場合は再生成として扱う
        if ($this->recoveryCodeService->hasRecoveryCodes($user)) {
            return $this->regenerate($request);
        }
        
        try {
            $codes = $this->recoveryCodeService->generate($user);
            
            // フォーマット
            $formattedCodes = array_map(function($code) {
                return $this->recoveryCodeService->formatCode($code);
            }, $codes);
            
            return response()->json([
                'success' => true,
                'codes' => $formattedCodes,
                'message' => '回復コードを生成しました'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '回復コード生成に失敗しました'
            ], 500);
        }
    }
    
    // 再生成
    public function regenerate(Request $request)
    {
        $user = Auth::guard('member')->user();
        
        // 再生成可能かチェック
        if (!$this->recoveryCodeService->canRegenerate($user)) {
            $nextTime = $this->recoveryCodeService->getNextRegenerateTime($user);
            
            return response()->json([
                'success' => false,
                'message' => "次回生成可能時刻: {$nextTime->format('Y-m-d H:i')}",
                'next_time' => $nextTime->format('Y-m-d H:i')
            ], 429);
        }
        
        try {
            $codes = $this->recoveryCodeService->generate($user);
            
            $formattedCodes = array_map(function($code) {
                return $this->recoveryCodeService->formatCode($code);
            }, $codes);
            
            return response()->json([
                'success' => true,
                'codes' => $formattedCodes,
                'message' => '回復コードを再生成しました'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '回復コード再生成に失敗しました'
            ], 500);
        }
    }
}
```

### 認証での使用

```php
// TwoFaAuthenticationTraitを使用している場合は自動実装済み
// カスタム実装の場合:

public function verifyRecoveryCode(Request $request)
{
    $request->validate([
        'recovery_code' => 'required|string'
    ]);
    
    $userId = session('admin_two_fa.id');
    if (!$userId) {
        return redirect()->route('admin.login');
    }
    
    $user = \App\Models\Member::find($userId);
    
    // 試行回数チェック
    $attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
    if ($attemptService->isLockedOut($user)) {
        return back()->withErrors([
            'recovery_code' => 'ロックアウト中です'
        ]);
    }
    
    // 回復コード検証
    $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
    $success = $recoveryCodeService->validate($user, $request->recovery_code);
    
    // 試行を記録
    $attemptService->recordAttempt($user, 'recovery_code', $success);
    
    if ($success) {
        // 認証成功
        $remember = session('admin_two_fa.remember', false);
        session()->forget('admin_two_fa');
        
        Auth::guard('member')->login($user, $remember);
        
        // 残りの回復コード数を確認
        $remaining = $recoveryCodeService->getRemainingCount($user);
        if ($remaining <= 2) {
            session()->flash('warning', "回復コードの残りが{$remaining}個です。新しいコードを生成してください。");
        }
        
        return redirect()->route('admin.dashboard');
    }
    
    return back()->withErrors([
        'recovery_code' => '回復コードが正しくありません'
    ]);
}
```

---

## プラグイン開発での使用

### ユーザープラグインでの実装例

```php
namespace Plugins\DixlaseUsers\App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\TwoFa\TwoFaAuthenticationTrait;

class DixlaseUsersTwoFactorController extends Controller
{
    use TwoFaAuthenticationTrait;
    
    protected function getSettingModelClass(): string
    {
        // ユーザープラグインの設定モデルを使用
        return \Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting::class;
    }
    
    protected function getTwoFaService()
    {
        return app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => 'user' // コンテキストを'user'に
        ]);
    }
    
    protected function getTwoFaRoutePrefix(): string
    {
        return 'users-plugin.two-fa';
    }
    
    protected function getSessionPrefix(): string
    {
        return 'user_two_fa';
    }
    
    protected function getDashboardRoute(): string
    {
        return 'users-plugin.dashboard';
    }
    
    protected function getLoginRoute(): string
    {
        return 'users-plugin.login';
    }
}
```

**ルート定義（プラグイン内）:**

```php
// plugins/DixlaseUsers/routes/web.php

Route::middleware('guest:dixlase_users_user')->prefix('user')->name('users-plugin.')->group(function () {
    Route::prefix('two-fa')->name('two-fa.')->group(function () {
        // メール認証
        Route::get('/email', [DixlaseUsersTwoFactorController::class, 'showEmailChallenge'])
            ->name('email.show');
        Route::post('/email/verify', [DixlaseUsersTwoFactorController::class, 'verifyEmail'])
            ->name('email.verify');
        Route::post('/email/resend', [DixlaseUsersTwoFactorController::class, 'resendEmail'])
            ->name('email.resend');
        
        // Passkey認証
        Route::get('/passkey', [DixlaseUsersTwoFactorController::class, 'showPasskeyChallenge'])
            ->name('passkey.show');
        Route::post('/passkey/challenge', [DixlaseUsersTwoFactorController::class, 'getPasskeyChallenge'])
            ->name('passkey.challenge');
        Route::post('/passkey/verify', [DixlaseUsersTwoFactorController::class, 'verifyPasskey'])
            ->name('passkey.verify');
        
        // 回復コード認証
        Route::get('/recovery', [DixlaseUsersTwoFactorController::class, 'showRecoveryCodeChallenge'])
            ->name('recovery.show');
        Route::post('/recovery/verify', [DixlaseUsersTwoFactorController::class, 'verifyRecoveryCode'])
            ->name('recovery.verify');
    });
});
```

---

## トラブルシューティング

### メール送信エラー

**症状**: コード生成時にメール送信エラーが発生

**原因と解決策:**

1. **メール設定が未完了**
   ```php
   $helper = app(\App\Helpers\TwoFaHelper::class);
   if (!$helper->isMailConfigured()) {
       // メール設定を完了してください
   }
   ```

2. **メールクラスが見つからない**
   ```php
   // 正しいメールクラスを指定
   $codeService->generateAndSend(
       $user,
       \App\Mail\TwoFaCodeMail::class, // ← 存在するクラスを指定
       10,
       'admin'
   );
   ```

3. **ログを確認**
   ```bash
   tail -f storage/logs/laravel.log | grep "\[2FA\]"
   ```

---

### Passkey認証エラー

**症状**: Passkey認証が利用できない

**原因と解決策:**

1. **HTTPS接続が必要**
   ```php
   $passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);
   if (!$passkeyService->isAvailable()) {
       // HTTPS接続が必要です
   }
   ```

2. **ブラウザが対応していない**
   - Chrome 67+
   - Firefox 60+
   - Safari 13+
   - Edge 18+

3. **デバイスが対応していない**
   - Touch ID/Face ID搭載デバイス
   - Windows Hello対応PC
   - FIDO2対応セキュリティキー

---

### ロックアウト問題

**症状**: ユーザーがロックアウトされている

**解決策:**

```php
// 手動でロックアウトを解除（管理者のみ）
$user = \App\Models\Member::find($userId);
$user->twoFaAttempts()
    ->where('success', false)
    ->where('created_at', '>=', \Carbon\Carbon::now()->subMinutes(30))
    ->delete();
```

---

### セッションエラー

**症状**: 2FA認証画面で「セッションが無効です」エラー

**原因と解決策:**

1. **セッションキーが正しくない**
   ```php
   // ログイン時
   session(['admin_two_fa.id' => $user->id]);
   
   // 2FA認証時
   $userId = session('admin_two_fa.id'); // ← 同じキーを使用
   ```

2. **セッションが期限切れ**
   ```php
   // config/session.php
   'lifetime' => 120, // セッション有効期限（分）
   ```

---

## ベストプラクティス

### 1. エラーハンドリング

```php
try {
    $code = $codeService->generateAndSend($user, ...);
} catch (\Exception $e) {
    \Log::error('[2FA] Code generation failed', [
        'user_id' => $user->id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    return response()->json([
        'success' => false,
        'message' => 'コード生成に失敗しました'
    ], 500);
}
```

### 2. ログ記録

```php
\Log::info('[2FA] Authentication attempt', [
    'user_id' => $user->id,
    'method' => 'email',
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent()
]);
```

### 3. セキュリティ

```php
// 必ずロックアウトチェックを実装
$attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);
if ($attemptService->isLockedOut($user)) {
    // ロックアウト中の処理
}

// 試行を必ず記録
$attemptService->recordAttempt($user, 'email', $success);
```

### 4. ユーザーエクスペリエンス

```php
// 残り試行回数を表示
$remaining = $attemptService->getRemainingAttempts($user);
if ($remaining <= 3) {
    session()->flash('warning', "残り{$remaining}回の試行が可能です。");
}

// 回復コードの残数を警告
$recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
$remaining = $recoveryCodeService->getRemainingCount($user);
if ($remaining <= 2) {
    session()->flash('warning', "回復コードの残りが{$remaining}個です。");
}
```

---

## まとめ

Dixlaseの2FAシステムは、以下の特徴を持っています：

✅ **簡単な実装**: TwoFaAuthenticationTraitで5ステップで実装可能
✅ **柔軟な設計**: 個別サービスを使った細かい制御も可能
✅ **プラグイン対応**: 設定モデルクラスを柔軟に指定可能
✅ **複数認証方法**: メール、Passkey、回復コードをサポート
✅ **セキュリティ**: ロックアウト、試行回数制限、期限切れ管理
✅ **拡張性**: 新しい認証方法の追加が容易

このガイドに従って実装することで、安全で使いやすい2FA機能を簡単に追加できます。

---

## 関連ドキュメント

- [2FAアーキテクチャ](../../development/two-factor/two-factor-authentication-architecture.md) - 技術仕様と詳細
- [UIコンポーネント](../../development/two-factor/two-factor-ui-components.md) - フロントエンド実装
