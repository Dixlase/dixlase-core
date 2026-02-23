<?php

namespace App\Services\TwoFa;

use App\Enums\AuthenticationMode;
use App\Models\SecuritySetting;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 二段階認証の状態判定サービス
 *
 * 全体設定とプロフィール設定を考慮した二段階認証の実際の状態を判定します。
 */
class TwoFaStatusService
{
    /**
     * 実際の二段階認証モードを取得
     *
     * @param  \App\Models\Member  $user
     * @return int 実際の二段階認証モード
     */
    public function getActualTwoFaMode($user): int
    {
        // 全体設定を取得
        $twoFaForceMode = (int) SecuritySetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
        $profileTwoFaMode = is_int($user->two_fa_mode) ? $user->two_fa_mode : $user->two_fa_mode->value;

        // 実際の二段階認証モードを判定
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value) {
            // プロフィール設定に従う場合はプロフィールの値を使用
            return $profileTwoFaMode;
        } else {
            // それ以外は全体設定を使用
            return $twoFaForceMode;
        }
    }

    /**
     * 二段階認証が有効かどうかを判定
     *
     * @param  \App\Models\Member  $user
     * @return bool 二段階認証が有効な場合true
     */
    public function isTwoFaEnabled($user): bool
    {
        $actualTwoFaMode = $this->getActualTwoFaMode($user);

        return $actualTwoFaMode === AuthenticationMode::Always->value ||
                $actualTwoFaMode === AuthenticationMode::DifferentDevice->value;
    }

    /**
     * 実際のパスキー有効状態を取得
     *
     * @param  \App\Models\Member  $user
     * @return bool パスキーが有効な場合true
     */
    public function isPasskeyEnabled($user): bool
    {
        // 全体設定のパスキーモードを取得
        $twoFaPasskeyMode = (int) SecuritySetting::getValue('two_fa_passkey_mode', '2');

        // 実際のパスキー有効状態を判定
        if ($twoFaPasskeyMode === 0) {
            // 全体設定で無効
            return false;
        } elseif ($twoFaPasskeyMode === 1) {
            // 全体設定で有効
            return true;
        } else {
            // プロフィール設定に従う
            return $user->two_fa_passkey_enabled ?? true;
        }
    }

    /**
     * 回復コードの自動生成が必要かどうかを判定
     *
     * @param  \App\Models\Member  $user
     * @return bool 回復コードの自動生成が必要な場合true
     */
    public function shouldGenerateRecoveryCodes($user, TwoFaRecoveryCodeService $recoveryCodeService): bool
    {
        return $this->isTwoFaEnabled($user) && ! $recoveryCodeService->hasRecoveryCodes($user);
    }

    /**
     * パスキー登録促進モーダルを表示すべきかどうかを判定
     *
     * @param  \App\Models\Member  $user
     * @return bool パスキー登録促進モーダルを表示すべき場合true
     */
    public function shouldPromptPasskeyRegistration($user, TwoFaPasskeyService $passkeyService): bool
    {
        // ユーザーがモーダルを非表示にしている場合は表示しない
        if ($user->passkey_prompt_dismissed ?? false) {
            return false;
        }

        if (! $this->isTwoFaEnabled($user)) {
            return false;
        }

        if (! $this->isPasskeyEnabled($user)) {
            return false;
        }

        $passkeyDevices = $passkeyService->getDevices($user);

        return $passkeyDevices->isEmpty();
    }

    /**
     * 全体設定の二段階認証モードを取得
     *
     * @return int 全体設定の二段階認証モード
     */
    public function getGlobalTwoFaMode(): int
    {
        return (int) SecuritySetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
    }

    /**
     * 全体設定のパスキーモードを取得
     *
     * @return int 全体設定のパスキーモード（0=無効、1=有効、2=プロフィールに従う）
     */
    public function getGlobalPasskeyMode(): int
    {
        return (int) SecuritySetting::getValue('two_fa_passkey_mode', '2');
    }

    /**
     * パスキーが全体設定で有効かどうかを判定（プロフィール設定を考慮しない）
     *
     * @return bool パスキーが全体設定で有効な場合true
     */
    public function isPasskeyEnabledGlobally(): bool
    {
        return $this->getGlobalPasskeyMode() > 0;
    }
}
