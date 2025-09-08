<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Traits\TwoFactorTrait;
use App\Models\MemberSetting;
use App\Enums\TwoFactorMethod;

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
     * 二段階認証コードを生成してメール送信
     *
     * @param mixed $user ユーザーモデル
     * @param string $mailClass メールクラス名
     * @param int $expireMinutes 有効期限（分）
     * @param string $context コンテキスト（admin, user等）
     * @return string 生成されたコード
     */
    public function generateAndSendCode($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string
    {
        $code = $this->generateTwoFactorCode($user, $expireMinutes);

        // メール送信
        try {
            if ($mailClass === \App\Mail\TwoFactorLoginCodeMail::class) {
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
     * システム全体の二段階認証設定を取得
     *
     * @return array 設定配列
     */
    public function getSystemTwoFactorSettings(): array
    {
        return [
            'force_2fa' => (int) $this->getSettingValue('force_2fa', 0),
            'enabled_methods' => $this->getEnabledTwoFactorMethods(),
            'default_method' => (int) $this->getSettingValue('default_two_factor_method', TwoFactorMethod::EMAIL->value),
        ];
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
     * 二段階認証が有効かどうかを判定（システム設定とユーザー設定を考慮）
     *
     * @param mixed $user ユーザーモデル
     * @return bool 2FAが有効かどうか
     */
    public function isTwoFactorEnabled($user): bool
    {
        $systemSettings = $this->getSystemTwoFactorSettings();
        
        return $this->requiresTwoFactor(
            $user,
            $systemSettings['force_2fa'],
            $systemSettings['enabled_methods']
        );
    }

    /**
     * 使用する認証方法を決定
     *
     * @param mixed $user ユーザーモデル
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user): int
    {
        $systemSettings = $this->getSystemTwoFactorSettings();
        $userSettings = $this->getUserTwoFactorSettings($user);

        // ユーザーが個別に設定している場合
        if ($userSettings['method'] !== null && $userSettings['method'] !== TwoFactorMethod::USE_PROFILE_SETTING->value) {
            // ユーザーの設定が有効な方法に含まれているかチェック
            if (in_array($userSettings['method'], $systemSettings['enabled_methods'])) {
                return $userSettings['method'];
            }
        }

        // システムのデフォルト方法を使用
        return $systemSettings['default_method'];
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
