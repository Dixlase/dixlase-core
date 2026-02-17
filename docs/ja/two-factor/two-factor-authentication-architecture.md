# 二段階認証（2FA）アーキテクチャドキュメント

## 概要

Dixlaseの二段階認証システムは、複数のサービス、トレイト、ヘルパーで構成されており、それぞれが明確な責任を持っています。このドキュメントでは、各コンポーネントの役割と使用方法を説明します。

---

## アーキテクチャ図

```
┌─────────────────────────────────────────────────────────────┐
│                     コントローラー層                          │
│  (AdminProfileController, LoginController, etc.)            │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ↓
┌─────────────────────────────────────────────────────────────┐
│                  TwoFaAuthenticationTrait                    │
│              (2FA認証フローの共通実装)                        │
└─────────────────────┬───────────────────────────────────────┘
                      │
                      ↓
┌─────────────────────────────────────────────────────────────┐
│                      TwoFaService                            │
│              (各サービスのオーケストレーション)                │
└─────┬───────┬───────┬───────┬───────┬─────────────────────┘
      │       │       │       │       │
      ↓       ↓       ↓       ↓       ↓
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│TwoFaCode │ │TwoFaPass │ │TwoFaReco │ │TwoFaAtte │ │TwoFaHelp │
│Service   │ │keyService│ │veryCode  │ │mptService│ │er        │
│          │ │          │ │Service   │ │          │ │          │
└──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘
     │             │             │             │             │
     └─────────────┴─────────────┴─────────────┴─────────────┘
                              │
                              ↓
                    ┌──────────────────┐
                    │TwoFaUtilityTrait │
                    │  (共通ユーティリティ)│
                    └──────────────────┘
```

---

## コンポーネント一覧

### 1. **TwoFaCodeService**（コード生成・検証）

**責任:**
- メール認証コードの生成
- コードのDB保存
- メール送信
- コード検証
- 期限切れコードのクリーンアップ

**主要メソッド:**
```php
// コード生成（DB保存のみ）
public function generate($user, int $expireMinutes = null): string

// コード生成 + メール送信
public function generateAndSend($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string

// コード検証
public function validate($user, string $inputCode): bool

// 有効なコードが存在するかチェック
public function hasValidCode($user): bool

// 残り有効時間を取得
public function getRemainingTime($user): ?int

// すべてのコードを無効化
public function revokeAll($user): int

// 期限切れコードをクリーンアップ
public function cleanupExpired(): int
```

**使用例:**
```php
$codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);

// コード生成 + メール送信
$code = $codeService->generateAndSend(
    $user, 
    \App\Mail\TwoFaCodeMail::class, 
    10, // 10分間有効
    'admin'
);

// コード検証
if ($codeService->validate($user, $inputCode)) {
    // 認証成功
}
```

**特徴:**
- ✅ メール設定チェック機能（TwoFaHelperを使用）
- ✅ 汎用メールクラス対応
- ✅ コンテキスト別メール送信（admin, user等）

---

### 2. **TwoFaRecoveryCodeService**（回復コード管理）

**責任:**
- 回復コードの生成
- 回復コードの検証
- 使用済みコードの管理
- 再生成制限のチェック

**主要メソッド:**
```php
// 回復コード生成（5個）
public function generate(TwoFaInterface $user): array

// 回復コード検証
public function validate(TwoFaInterface $user, string $code): bool

// 残りの有効な回復コード数を取得
public function getRemainingCount(TwoFaInterface $user): int

// 再生成可能かチェック（24時間制限）
public function canRegenerate(TwoFaInterface $user): bool

// 次回再生成可能な日時を取得
public function getNextRegenerateTime(TwoFaInterface $user): ?Carbon

// 回復コードが存在するかチェック
public function hasRecoveryCodes(TwoFaInterface $user): bool

// 回復コードをフォーマット（表示用）
public function formatCode(string $code): string

// すべての回復コードを無効化
public function revokeAll(TwoFaInterface $user): int
```

**使用例:**
```php
$recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);

// 回復コード生成
$codes = $recoveryCodeService->generate($user);
// 例: ['1234567890123456789', '9876543210987654321', ...]

// フォーマット表示
foreach ($codes as $code) {
    echo $recoveryCodeService->formatCode($code);
    // 出力: 12345-67890-12345-67890
}

// 回復コード検証
if ($recoveryCodeService->validate($user, $inputCode)) {
    // 認証成功（使用済みとしてマーク）
}

// 再生成可能かチェック
if ($recoveryCodeService->canRegenerate($user)) {
    $newCodes = $recoveryCodeService->generate($user);
}
```

**特徴:**
- ✅ 20桁の回復コード（5桁×4ブロック）
- ✅ ハッシュ化して保存
- ✅ 使用済みコードの自動マーク
- ✅ 24時間の再生成制限
- ✅ 設定モデルクラスをコンストラクタで受け取る（柔軟性）

**設定項目:**
- `two_fa_recovery_codes_count`: 生成個数（1-5個、デフォルト5個）
- `two_fa_recovery_code_regenerate_interval`: 再生成制限時間（時間、デフォルト24時間）

---

### 3. **TwoFaPasskeyService**（Passkey認証）

**責任:**
- WebAuthn標準に基づく生体認証
- Passkey認証情報の登録・削除
- 認証チャレンジの生成
- 認証の検証
- 信頼済みデバイスの管理

**主要メソッド:**
```php
// Passkeyが利用可能かチェック（HTTPS必須）
public function isAvailable(): bool

// 認証情報を持っているかチェック
public function hasCredentials(TwoFaInterface $user): bool

// 認証情報を登録
public function registerCredential(TwoFaInterface $user, array $credentialData, string $deviceName = null)

// 認証を検証
public function verifyAssertion(TwoFaInterface $user, array $assertionData): bool

// 認証情報一覧を取得
public function getCredentials(TwoFaInterface $user)

// 認証情報を削除
public function revokeCredential(TwoFaInterface $user, string $credentialId): bool

// すべての認証情報を削除
public function revokeAllCredentials(TwoFaInterface $user): int

// 登録チャレンジを生成
public function generateRegistrationChallenge(TwoFaInterface $user): array

// 認証チャレンジを生成
public function generateAuthenticationChallenge(TwoFaInterface $user): array

// 信頼済みデバイスかチェック
public function isTrustedDevice(TwoFaInterface $user): bool

// デバイスを削除
public function revokeDevice(TwoFaInterface $user, int $deviceId): bool

// すべてのデバイスを削除
public function revokeAllTrustedDevices(Member $member): int

// デバイス一覧を取得
public function getTrustedDevices(TwoFaInterface $user)
```

**使用例:**
```php
$passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);

// Passkey利用可能かチェック
if (!$passkeyService->isAvailable()) {
    // HTTPS接続が必要
    throw new \Exception('HTTPS connection required');
}

// 登録チャレンジ生成
$options = $passkeyService->generateRegistrationChallenge($user);
// フロントエンドに渡す

// 認証情報を登録
$credential = $passkeyService->registerCredential(
    $user,
    $credentialData,
    'iPhone Touch ID'
);

// 認証チャレンジ生成
$options = $passkeyService->generateAuthenticationChallenge($user);

// 認証検証
if ($passkeyService->verifyAssertion($user, $assertionData)) {
    // 認証成功
}
```

**特徴:**
- ✅ WebAuthn標準準拠
- ✅ Touch ID、Face ID、Windows Hello対応
- ✅ HTTPS接続必須
- ✅ 信頼済みデバイス管理
- ✅ デバイス名の自動生成

**対応デバイス:**
- iPhone/iPad: Touch ID, Face ID
- Mac: Touch ID
- Android: 指紋認証
- Windows: Windows Hello

---

### 4. **TwoFaAttemptService**（試行回数管理）

**責任:**
- 2FA試行の記録
- ロックアウト状態の管理
- 試行回数制限のチェック
- 残り試行回数の取得

**主要メソッド:**
```php
// 試行を記録
public function recordAttempt(TwoFaInterface $user, string $attemptType, bool $success): void

// ロックアウト状態かチェック
public function isLockedOut(TwoFaInterface $user): bool

// ロックアウト解除までの残り時間（分）を取得
public function getRemainingLockoutTime(TwoFaInterface $user): ?int

// 試行回数制限に達しているかチェック
public function hasReachedMaxAttempts(TwoFaInterface $user): bool

// 残りの試行可能回数を取得
public function getRemainingAttempts(TwoFaInterface $user): int

// 成功時の処理
public function handleSuccess(TwoFaInterface $user): void

// ロックアウト通知が有効かチェック
public function isLockoutNotificationEnabled(): bool
```

**使用例:**
```php
$attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);

// ロックアウトチェック
if ($attemptService->isLockedOut($user)) {
    $remainingTime = $attemptService->getRemainingLockoutTime($user);
    throw new \Exception("ロックアウト中。残り{$remainingTime}分");
}

// 試行を記録
$success = $codeService->validate($user, $inputCode);
$attemptService->recordAttempt($user, 'email', $success);

// 残り試行回数を取得
$remaining = $attemptService->getRemainingAttempts($user);
```

**特徴:**
- ✅ IP アドレス・User Agent の記録
- ✅ 試行タイプ別の記録（email, passkey, recovery_code）
- ✅ 自動ロックアウト
- ✅ 設定モデルクラスをコンストラクタで受け取る（柔軟性）

**設定項目:**
- `two_fa_max_attempts`: 最大試行回数（デフォルト5回）
- `two_fa_attempt_window`: 試行制限の時間枠（分、デフォルト15分）
- `two_fa_lockout_duration`: ロックアウト時間（分、デフォルト30分）
- `two_fa_lockout_notification_enabled`: ロックアウト通知の有効化（デフォルトtrue）

---

### 5. **TwoFaService**（オーケストレーション）

**責任:**
- 各サービスを組み合わせて使用
- 認証方法の自動判定
- 認証フローの統一インターフェース提供

**主要メソッド:**
```php
// 2FAコードを生成してメール送信（認証方法を自動判定）
public function generate($user, int $method = null)

// 2FAを検証（認証方法を自動判定）
public function validate($user, $input, int $method = null): bool

// 2FAが必要かどうかを判定
public function has($member): bool

// 異なる環境からのアクセスかどうかを判定
public function isDifferentEnvironment($member): bool

// システム設定を取得
public function getSystemSettings(): array

// 使用する認証方法を取得
public function getEffectiveAuthMethod($user): int

// 利用可能な認証方法を取得
public function getAvailableMethods($user): array

// 回復コードを検証
public function validateRecoveryCode($user, string $code): bool

// ロックアウト状態をチェック
public function checkLockout($user): array
```

**使用例:**
```php
$twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
    'settingModelClass' => \App\Models\MemberSetting::class,
    'context' => 'admin'
]);

// 2FAが必要かチェック
if ($twoFaService->has($user)) {
    // コード生成（認証方法を自動判定）
    $result = $twoFaService->generate($user);
    
    // メール認証の場合: string（コード）
    // Passkey認証の場合: array（チャレンジ）
}

// 検証（認証方法を自動判定）
if ($twoFaService->validate($user, $input)) {
    // 認証成功
}

// ロックアウトチェック
$lockoutStatus = $twoFaService->checkLockout($user);
if ($lockoutStatus['locked_out']) {
    // ロックアウト中
}
```

**特徴:**
- ✅ 各サービスのオーケストレーション
- ✅ 認証方法の自動判定
- ✅ 統一されたインターフェース
- ✅ コンテキスト別の動作（admin, user等）

---

### 6. **TwoFaHelper**（ヘルパー）

**責任:**
- システム設定の取得
- 2FA有効/無効の判定
- 認証方法の判定
- メール設定のチェック
- ルート名の取得

**主要メソッド:**
```php
// メール設定が完了しているかチェック
public function isMailConfigured(): bool

// コード生成 + メール送信（TwoFaCodeServiceへの委譲）
public function generateAndSendCode($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string

// 2FA設定を取得
public function getTwoFaSettings(?string $settingModelClass = null): array

// 有効な2FA認証方法を取得
public function getEnabledTwoFaMethods(?string $settingModelClass = null): array

// メンバーが利用可能な2FA認証方法を取得
public function getAvailableTwoFaMethodsForMember($member, ?string $settingModelClass = null): array

// 2FAが有効かチェック
public function isTwoFaEnabled($user, ?string $settingModelClass = null): bool

// 異なる環境からのアクセスかチェック
public function isDifferentEnvironment($user): bool

// 使用する認証方法を取得
public function getEffectiveAuthMethod($user, ?string $settingModelClass = null): int

// 認証方法に応じたメールクラスを取得
public function getMailClassForMethod(int $method, string $context = 'admin'): string

// 2FA統計情報を取得
public function getTwoFaStats(): array

// 期限切れトークンをクリーンアップ
public function cleanupExpiredTokens(): int
```

**使用例:**
```php
$helper = app(\App\Helpers\TwoFaHelper::class);

// メール設定チェック
if (!$helper->isMailConfigured()) {
    // メール設定が未完了
}

// 2FAが有効かチェック
if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
    // 2FA有効
}

// 利用可能な認証方法を取得
$methods = $helper->getAvailableTwoFaMethodsForMember($user);
// 例: [TwoFaMethod::EMAIL->value, TwoFaMethod::PASSKEY->value]

// 使用する認証方法を取得
$method = $helper->getEffectiveAuthMethod($user);
```

**特徴:**
- ✅ 設定取得の一元化
- ✅ 判定ロジックの共通化
- ✅ サービスへの委譲
- ✅ 設定モデルクラスの柔軟な指定

---

### 7. **TwoFaUtilityTrait**（ユーティリティトレイト）

**責任:**
- サービスへの委譲メソッド提供
- 共通判定ロジック提供
- 設定取得の抽象化

**主要メソッド:**
```php
// 2FAコードを生成（TwoFaCodeServiceへの委譲）
public function generateTwoFaCode($user, int $expireMinutes = null): string

// 2FAコードを検証（TwoFaCodeServiceへの委譲）
public function validateTwoFaCode($user, string $inputCode): bool

// 有効な認証方法を取得
public function getEnabledTwoFaMethods(string $settingsKey = 'enabled_two_fa_methods', array $defaultMethods = null): array

// 2FAが必要かどうかを判定
public function requiresTwoFa($user, int $forceSetting, array $enabledMethods): bool

// 有効な2FAモードを取得
protected function getEffectiveTwoFaMode($user, int $forceSetting): int

// メンバーの個人設定をチェック
protected function checkMemberSetting($user): int

// 信頼済みデバイスからのアクセスかチェック
protected function isFromTrustedDevice($user): bool

// 設定値を取得（継承先で実装）
abstract protected function getSettingValue(string $key, $default = null);
```

**使用例:**
```php
class TwoFaHelper
{
    use TwoFaUtilityTrait;
    
    protected function getSettingValue(string $key, $default = null)
    {
        return MemberSetting::getValue($key, $default);
    }
}

// トレイトのメソッドを使用
$code = $this->generateTwoFaCode($user, 10);
$isValid = $this->validateTwoFaCode($user, $inputCode);
```

**特徴:**
- ✅ サービスへの薄いラッパー
- ✅ 判定ロジックの共通化
- ✅ 設定取得の抽象化
- ✅ 再利用性の向上

---

### 8. **TwoFaAuthenticationTrait**（認証フロートレイト）

**責任:**
- 2FA認証フローの共通実装
- メール認証フォームの表示
- Passkey認証フォームの表示
- 回復コード入力フォームの表示
- 認証検証処理
- 認証成功後のログイン処理

**主要メソッド:**
```php
// メール認証フォームを表示
protected function showEmailForm(Request $request)

// Passkey認証フォームを表示
protected function showPasskeyForm(Request $request)

// 回復コード入力画面を表示
public function showRecoveryCodeForm(Request $request)

// メール認証チャレンジ画面を表示
public function showEmailChallenge(Request $request)

// メール認証コードを検証
public function verifyEmail(Request $request)

// メール認証コードを再送信
public function resendEmail(Request $request)

// Passkey認証チャレンジ画面を表示
public function showPasskeyChallenge(Request $request)

// Passkeyチャレンジを取得
public function getPasskeyChallenge(Request $request)

// Passkey認証を検証
public function verifyPasskey(Request $request)

// 回復コードを検証
public function verifyRecoveryCode(Request $request)

// 認証成功後のログイン処理
protected function completeAuthentication($user, Request $request)

// セッションからユーザーを取得
protected function getUserFromSession()

// 利用可能な認証方法を取得
protected function getAvailableMethods(int $currentMethod = null): array
```

**使用例:**
```php
class AdminTwoFactorController extends AdminLoggedInController
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

**特徴:**
- ✅ 2FA認証フローの完全実装
- ✅ 複数認証方法のサポート
- ✅ セッション管理
- ✅ ロックアウト対応
- ✅ 再利用可能な設計

**必須実装メソッド:**
- `getSettingModelClass()`: 設定モデルクラス名を返す
- `getTwoFaService()`: TwoFaServiceインスタンスを返す
- `getTwoFaRoutePrefix()`: ルート名のプレフィックスを返す
- `getSessionPrefix()`: セッションキーのプレフィックスを返す
- `getDashboardRoute()`: ダッシュボードのルート名を返す
- `getLoginRoute()`: ログインのルート名を返す

---

## 認証フロー

### メール認証フロー

```
1. ログイン試行
   ↓
2. TwoFaService::has() で2FAが必要かチェック
   ↓
3. TwoFaService::generate() でコード生成 + メール送信
   ↓
4. メール認証画面を表示
   ↓
5. ユーザーがコードを入力
   ↓
6. TwoFaService::validate() でコード検証
   ↓
7. TwoFaAttemptService::recordAttempt() で試行を記録
   ↓
8. 認証成功 → ログイン完了
```

### Passkey認証フロー

```
1. ログイン試行
   ↓
2. TwoFaService::has() で2FAが必要かチェック
   ↓
3. TwoFaPasskeyService::generateAuthenticationChallenge() でチャレンジ生成
   ↓
4. Passkey認証画面を表示
   ↓
5. ユーザーが生体認証
   ↓
6. TwoFaPasskeyService::verifyAssertion() で認証検証
   ↓
7. TwoFaAttemptService::recordAttempt() で試行を記録
   ↓
8. 認証成功 → ログイン完了
```

### 回復コード認証フロー

```
1. メール/Passkey認証に失敗
   ↓
2. 回復コード入力画面を表示
   ↓
3. ユーザーが回復コードを入力
   ↓
4. TwoFaRecoveryCodeService::validate() で検証
   ↓
5. TwoFaAttemptService::recordAttempt() で試行を記録
   ↓
6. 認証成功 → ログイン完了
   （使用済みコードは自動的に無効化）
```

---

## 設定項目一覧

### システム設定（MemberSetting）

| 設定キー | 説明 | デフォルト値 |
|---------|------|------------|
| `two_fa_force_mode` | 2FA強制モード（0: 無効, 1: プロフィール設定, 2: 常に有効） | 0 |
| `enabled_two_fa_methods` | 有効な認証方法（JSON配列） | `["email"]` |
| `two_fa_code_expire_minutes` | コード有効期限（分） | 10 |
| `two_fa_recovery_codes_count` | 回復コード生成個数 | 5 |
| `two_fa_recovery_code_regenerate_interval` | 回復コード再生成制限（時間） | 24 |
| `two_fa_max_attempts` | 最大試行回数 | 5 |
| `two_fa_attempt_window` | 試行制限の時間枠（分） | 15 |
| `two_fa_lockout_duration` | ロックアウト時間（分） | 30 |
| `two_fa_lockout_notification_enabled` | ロックアウト通知の有効化 | true |

### ユーザー設定（Member）

| カラム | 説明 | デフォルト値 |
|--------|------|------------|
| `two_fa_mode` | 個人の2FA設定（0: 無効, 2: 常に有効） | 0 |
| `two_fa_default_method` | デフォルト認証方法 | null |

---

## 使用例

### 基本的な使用方法

```php
// 1. 2FAが必要かチェック
$helper = app(\App\Helpers\TwoFaHelper::class);
if ($helper->isTwoFaEnabled($user, \App\Models\MemberSetting::class)) {
    // 2. コード生成 + メール送信
    $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);
    $code = $codeService->generateAndSend(
        $user,
        \App\Mail\TwoFaCodeMail::class,
        10,
        'admin'
    );
    
    // 3. セッションにユーザーIDを保存
    session(['admin_two_fa.id' => $user->id]);
    
    // 4. 2FA認証画面にリダイレクト
    return redirect()->route('admin.two-fa.email.show');
}

// 2FAが不要な場合はログイン完了
Auth::guard('member')->login($user);
return redirect()->route('admin.dashboard');
```

### TwoFaServiceを使った統一的な方法

```php
// TwoFaServiceインスタンスを作成
$twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [
    'settingModelClass' => \App\Models\MemberSetting::class,
    'context' => 'admin'
]);

// 2FAが必要かチェック
if ($twoFaService->has($user)) {
    // コード生成（認証方法を自動判定）
    $result = $twoFaService->generate($user);
    
    // セッションにユーザーIDを保存
    session(['admin_two_fa.id' => $user->id]);
    
    // 認証方法に応じたルートにリダイレクト
    $method = $twoFaService->getEffectiveAuthMethod($user);
    $route = match($method) {
        TwoFaMethod::EMAIL->value => 'admin.two-fa.email.show',
        TwoFaMethod::PASSKEY->value => 'admin.two-fa.passkey.show',
        default => 'admin.two-fa.email.show',
    };
    
    return redirect()->route($route);
}
```

### 回復コードの生成と表示

```php
$recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);

// 再生成可能かチェック
if (!$recoveryCodeService->canRegenerate($user)) {
    $nextTime = $recoveryCodeService->getNextRegenerateTime($user);
    return response()->json([
        'success' => false,
        'message' => "次回生成可能時刻: {$nextTime->format('Y-m-d H:i')}"
    ], 429);
}

// 回復コード生成
$codes = $recoveryCodeService->generate($user);

// フォーマットして表示
$formattedCodes = array_map(function($code) use ($recoveryCodeService) {
    return $recoveryCodeService->formatCode($code);
}, $codes);

return view('admin.profile.recovery-codes', [
    'codes' => $formattedCodes
]);
```

### Passkey登録

```php
$passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);

// HTTPS接続チェック
if (!$passkeyService->isAvailable()) {
    return response()->json([
        'success' => false,
        'message' => 'HTTPS接続が必要です'
    ], 400);
}

// 登録チャレンジ生成
$options = $passkeyService->generateRegistrationChallenge($user);

return response()->json([
    'success' => true,
    'options' => $options
]);

// フロントエンドで認証情報を取得後、登録
$credential = $passkeyService->registerCredential(
    $user,
    $request->input('credential'),
    $request->input('device_name', 'My Device')
);
```

---

## ベストプラクティス

### 1. **サービスの選択**

- **単一機能が必要な場合**: 直接サービスを使用
  ```php
  $codeService = app(\App\Services\TwoFa\TwoFaCodeService::class);
  $code = $codeService->generate($user);
  ```

- **統合的な2FA機能が必要な場合**: TwoFaServiceを使用
  ```php
  $twoFaService = app(\App\Services\TwoFa\TwoFaService::class, [...]);
  $result = $twoFaService->generate($user);
  ```

- **設定取得や判定が必要な場合**: TwoFaHelperを使用
  ```php
  $helper = app(\App\Helpers\TwoFaHelper::class);
  if ($helper->isTwoFaEnabled($user)) { ... }
  ```

### 2. **認証フローの実装**

- **コントローラーで認証フローを実装する場合**: TwoFaAuthenticationTraitを使用
  ```php
  class MyTwoFactorController extends Controller
  {
      use TwoFaAuthenticationTrait;
      
      // 必須メソッドを実装
  }
  ```

### 3. **エラーハンドリング**

```php
try {
    $code = $codeService->generateAndSend($user, ...);
} catch (\Exception $e) {
    Log::error('[2FA] Code generation failed', [
        'user_id' => $user->id,
        'error' => $e->getMessage()
    ]);
    
    return response()->json([
        'success' => false,
        'message' => 'コード生成に失敗しました'
    ], 500);
}
```

### 4. **ロックアウト対応**

```php
$attemptService = app(\App\Services\TwoFa\TwoFaAttemptService::class);

// 検証前にロックアウトチェック
if ($attemptService->isLockedOut($user)) {
    $remainingTime = $attemptService->getRemainingLockoutTime($user);
    
    return response()->json([
        'success' => false,
        'message' => "ロックアウト中です。残り{$remainingTime}分お待ちください。"
    ], 429);
}

// 検証
$success = $codeService->validate($user, $inputCode);

// 試行を記録
$attemptService->recordAttempt($user, 'email', $success);
```

### 5. **設定モデルクラスの指定**

```php
// 管理画面の場合
$service = new TwoFaRecoveryCodeService(\App\Models\MemberSetting::class);

// ユーザープラグインの場合
$service = new TwoFaRecoveryCodeService(\Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting::class);
```

---

## トラブルシューティング

### メール送信エラー

**問題**: コード生成時にメール送信エラーが発生する

**解決策**:
1. メール設定を確認
   ```php
   $helper = app(\App\Helpers\TwoFaHelper::class);
   if (!$helper->isMailConfigured()) {
       // メール設定が未完了
   }
   ```

2. ログを確認
   ```bash
   tail -f storage/logs/laravel.log | grep "\[2FA\]"
   ```

### Passkey認証エラー

**問題**: Passkey認証が利用できない

**解決策**:
1. HTTPS接続を確認
   ```php
   $passkeyService = app(\App\Services\TwoFa\TwoFaPasskeyService::class);
   if (!$passkeyService->isAvailable()) {
       // HTTPS接続が必要
   }
   ```

2. ブラウザの対応状況を確認
   - Chrome 67+
   - Firefox 60+
   - Safari 13+
   - Edge 18+

### ロックアウト解除

**問題**: ユーザーがロックアウトされている

**解決策**:
```php
// 手動でロックアウトを解除（管理者のみ）
$user->twoFaAttempts()
    ->where('success', false)
    ->where('created_at', '>=', Carbon::now()->subMinutes(30))
    ->delete();
```

---

## まとめ

Dixlaseの2FAシステムは、以下の特徴を持っています：

✅ **明確な責任分離**: 各コンポーネントが明確な役割を持つ
✅ **高い再利用性**: トレイトとサービスで共通機能を提供
✅ **柔軟な設定**: 設定モデルクラスを柔軟に指定可能
✅ **複数認証方法**: メール、Passkey、回復コードをサポート
✅ **セキュリティ**: ロックアウト、試行回数制限、期限切れ管理
✅ **拡張性**: 新しい認証方法の追加が容易

このアーキテクチャにより、管理画面とユーザープラグインで2FA機能を共有しながら、それぞれのコンテキストに応じた動作を実現しています。
