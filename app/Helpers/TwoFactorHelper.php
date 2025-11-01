<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Traits\TwoFactorTrait;
use App\Models\MemberSetting;
use App\Enums\TwoFactorMethod;
use App\Enums\TwoFactorMode;

class TwoFactorHelper
{
    use TwoFactorTrait;

    /**
     * 設定値を取得する（MemberSettingから）
     *
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed 設定値
     */
    protected function getSettingValue(string $key, $default = null)
    {
        return MemberSetting::getValue($key, $default);
    }

    /**
     * メール設定が完了しているかチェック
     *
     * @return bool メール設定が完了しているか
     */
    public function isMailConfigured(): bool
    {
        $mailer = config('mail.default');
        
        // メール設定が存在しない場合
        if (!$mailer) {
            return false;
        }
        
        // SMTPの場合、必須設定をチェック
        if ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $username = config('mail.mailers.smtp.username');
            
            if (empty($host) || empty($port)) {
                return false;
            }
        }
        
        // 送信元アドレスが設定されているかチェック
        $fromAddress = config('mail.from.address');
        if (empty($fromAddress) || $fromAddress === 'hello@example.com') {
            return false;
        }
        
        return true;
    }

    /**
     * 二段階認証コードを生成してメール送信
     *
     * @param mixed $user ユーザーモデル
     * @param string $mailClass メールクラス名
     * @param int $expireMinutes 有効期限（分）
     * @param string $context コンテキスト（admin, user等）
     * @return string 生成されたコード
     * @throws \Exception メール設定が未完了の場合
     */
    public function generateAndSendCode($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string
    {
        // メール設定チェック
        if (!$this->isMailConfigured()) {
            Log::error("[2FA] メール設定が未完了のため、二段階認証コードを送信できません");
            throw new \Exception(__('admin.two_factor.mail_not_configured'));
        }
        
        $code = $this->generateTwoFactorCode($user, $expireMinutes);

        // メール送信
        try {
            if ($mailClass === \App\Mail\MembersTwoFactorCodeMail::class) {
                // 汎用メールクラスの場合はコンテキストを渡す
                Mail::to($user->email)->send(new $mailClass($code, $context));
            } else {
                // 既存のメールクラスの場合は従来通り
                Mail::to($user->email)->send(new $mailClass($code));
            }
            Log::info("[2FA] コード送信成功: ユーザーID {$user->id}, コンテキスト: {$context}");
        } catch (\Exception $e) {
            Log::error("[2FA] コード送信失敗: ユーザーID {$user->id}, エラー: " . $e->getMessage());
            throw $e;
        }

        return $code;
    }

    /**
     * システム設定から二段階認証設定を取得
     *
     * @return array
     */
    public function getSystemTwoFactorSettings(): array
    {
        return [
            'force_2fa' => (int) \App\Models\MemberSetting::getValue('force_2fa', 0),
            'enabled_methods' => $this->getEnabledTwoFactorMethods(),
            'default_method' => (int) \App\Models\MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value),
        ];
    }

    /**
     * 有効な二段階認証方法を取得
     *
     * @return array
     */
    public function getEnabledTwoFactorMethods(): array
    {
        $enabledString = \App\Models\MemberSetting::getValue('enabled_two_factor_methods', (string)TwoFactorMethod::EMAIL->value);
        return $enabledString ? array_map('intval', explode(',', $enabledString)) : [TwoFactorMethod::EMAIL->value];
    }

    /**
     * ユーザーの二段階認証設定を取得
     *
     * @param mixed $user ユーザーモデル
     * @return array 設定配列
     */
    public function getUserTwoFactorSettings($user): array
    {
        return [
            'mode' => $user->two_factor_mode,
            'method' => $user->two_factor_method,
        ];
    }

    /**
     * 二段階認証が有効かどうかを判定
     *
     * @param mixed $user ユーザーモデル
     * @return bool
     */
    public function isTwoFactorEnabled($user): bool
    {
        // メール設定が未完了の場合は二段階認証を無効化
        if (!$this->isMailConfigured()) {
            Log::warning("[2FA] メール設定が未完了のため、二段階認証を無効化しています");
            return false;
        }
        
        $systemSettings = $this->getSystemTwoFactorSettings();
        $force2fa = $systemSettings['force_2fa'] ?? 0;
        
        // 全体設定: 0=Disabled（無効）, 1=Always（常に有効）, 2=UseProfileSetting（プロフィール設定に従う）
        
        // 無効の場合
        if ($force2fa === TwoFactorMode::Disabled->value) {
            return false;
        }
        
        // 全体設定で常に有効の場合
        if ($force2fa === TwoFactorMode::Always->value) {
            return true;
        }
        
        // プロフィール設定を使用する場合（force_2fa = UseProfileSetting）
        $userMode = $user->two_factor_mode;
        
        // TwoFactorMode Enumの場合
        if ($userMode instanceof \App\Enums\TwoFactorMode) {
            return $userMode->value > 0; // Disabled(0)以外は有効
        }
        
        // 整数値の場合
        return (int)$userMode > 0;
    }

    /**
     * 使用する認証方法を決定
     *
     * @param mixed $user ユーザーモデル
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user): int
    {
        $userMethod = $user->two_factor_method ?? null;
        $defaultMethod = (int) \App\Models\MemberSetting::getValue('default_two_factor_method', TwoFactorMethod::EMAIL->value);
        $enabledMethods = $this->getEnabledTwoFactorMethods();
        $globalTwoFactorMode = (int) \App\Models\MemberSetting::getValue('force_2fa', TwoFactorMode::Disabled->value);

        Log::info('[2FA] getEffectiveAuthMethod', [
            'user_id' => $user->id,
            'user_method' => $userMethod,
            'default_method' => $defaultMethod,
            'global_mode' => $globalTwoFactorMode,
            'enabled_methods' => $enabledMethods,
        ]);

        // グローバル設定が「プロフィール設定に従う」(3)以外の場合は、デフォルト認証方法を強制
        if ($globalTwoFactorMode !== TwoFactorMode::UseProfileSetting->value) {
            if (in_array($defaultMethod, $enabledMethods, true)) {
                Log::info('[2FA] Using global default method (forced)', ['method' => $defaultMethod, 'global_mode' => $globalTwoFactorMode]);
                return $defaultMethod;
            }
        }

        // グローバル設定が「プロフィール設定に従う」(3)の場合のみ、ユーザー設定を考慮
        if ($userMethod !== null && in_array((int)$userMethod, $enabledMethods, true)) {
            Log::info('[2FA] Using user method', ['method' => (int)$userMethod]);
            return (int)$userMethod;
        }

        // デフォルト方法が有効な場合はそれを使用
        if (in_array($defaultMethod, $enabledMethods, true)) {
            Log::info('[2FA] Using default method', ['method' => $defaultMethod]);
            return $defaultMethod;
        }

        // 有効な方法の最初のものを使用
        $fallbackMethod = !empty($enabledMethods) ? $enabledMethods[0] : TwoFactorMethod::EMAIL->value;
        Log::info('[2FA] Using fallback method', ['method' => $fallbackMethod]);
        return $fallbackMethod;
    }

    /**
     * 認証方法に応じたメールクラス名を取得
     *
     * @param int $method 認証方法
     * @param string $context コンテキスト（admin, user等）
     * @return string メールクラス名
     */
    public function getMailClassForMethod(int $method, string $context = 'admin'): string
    {
        $contextPrefix = ucfirst($context);
        
        return match ($method) {
            TwoFactorMethod::EMAIL->value => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
            TwoFactorMethod::DEVICE->value => "App\\Mail\\{$contextPrefix}TwoFactorDeviceVerificationMail",
            TwoFactorMethod::BIOMETRIC->value => "App\\Mail\\{$contextPrefix}TwoFactorBiometricMail",
            default => "App\\Mail\\{$contextPrefix}TwoFactorLoginCodeMail",
        };
    }

    /**
     * 二段階認証の統計情報を取得
     *
     * @return array 統計情報
     */
    public function getTwoFactorStats(): array
    {
        // 実装例：実際の統計取得ロジックを追加
        return [
            'total_users_with_2fa' => 0,
            'active_tokens' => 0,
            'failed_attempts_today' => 0,
        ];
    }

    /**
     * 期限切れトークンをクリーンアップ
     *
     * @return int 削除されたトークン数
     */
    public function cleanupExpiredTokens(): int
    {
        return \App\Models\MembersTwoFactorToken::where('expires_at', '<', now())->delete();
    }
}
