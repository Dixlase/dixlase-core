<?php

namespace App\Traits\TwoFa;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\TwoFaHelper;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;

/**
 * 二段階認証のプロフィール設定管理機能を提供するトレイト
 * 
 * Passkey設定、回復コード管理、デバイス管理など、
 * プロフィール画面で使用する二段階認証の設定管理機能を提供します。
 * 
 * 使用するコントローラーは以下の抽象メソッドを実装する必要があります：
 * - getSettingModelClass(): 設定モデルクラス名を返す
 * - getCurrentUser(): 現在のユーザーを返す
 */
trait TwoFaProfileManagementTrait
{
    /**
     * Passkeyを有効化
     */
    protected function enablePasskey($user)
    {
        try {
            $user->two_fa_passkey_enabled = true;
            $user->save();

            Log::info('[Profile] Passkey enabled', [
                'user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => __('two_fa.passkey.enabled_successfully'),
            ];
        } catch (\Exception $e) {
            Log::error('[Profile] Failed to enable Passkey', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('two_fa.passkey.enable_failed'),
            ];
        }
    }

    /**
     * Passkeyを無効化
     */
    protected function disablePasskey($user)
    {
        try {
            $user->two_fa_passkey_enabled = false;
            $user->save();

            Log::info('[Profile] Passkey disabled', [
                'user_id' => $user->id,
            ]);

            return [
                'success' => true,
                'message' => __('two_fa.passkey.disabled_successfully'),
            ];
        } catch (\Exception $e) {
            Log::error('[Profile] Failed to disable Passkey', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('two_fa.passkey.disable_failed'),
            ];
        }
    }

    /**
     * 回復コードを生成
     */
    protected function generateRecoveryCodes($user, bool $force = false)
    {
        try {
            $twoFactorHelper = app(TwoFaHelper::class);
            $codes = $twoFactorHelper->generateRecoveryCodes($user, $force);

            Log::info('[Profile] Recovery codes generated', [
                'user_id' => $user->id,
                'force' => $force,
                'count' => count($codes),
            ]);

            return [
                'success' => true,
                'codes' => $codes,
                'message' => __('two_fa.recovery_code.generated_successfully'),
            ];
        } catch (\Exception $e) {
            Log::error('[Profile] Failed to generate recovery codes', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('two_fa.recovery_code.generate_failed'),
            ];
        }
    }

    /**
     * Passkeyデバイスを削除
     */
    protected function deletePasskeyDevice($user, string $deviceId)
    {
        try {
            $passkeyService = app(TwoFaPasskeyService::class);
            $result = $passkeyService->deleteDevice($user, $deviceId);

            if ($result) {
                Log::info('[Profile] Passkey device deleted', [
                    'user_id' => $user->id,
                    'device_id' => $deviceId,
                ]);

                return [
                    'success' => true,
                    'message' => __('two_fa.passkey.device_deleted_successfully'),
                ];
            }

            return [
                'success' => false,
                'message' => __('two_fa.passkey.device_not_found'),
            ];
        } catch (\Exception $e) {
            Log::error('[Profile] Failed to delete Passkey device', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('two_fa.passkey.device_delete_failed'),
            ];
        }
    }

    /**
     * すべてのPasskeyデバイスを削除
     */
    protected function deleteAllPasskeyDevices($user)
    {
        try {
            $passkeyService = app(TwoFaPasskeyService::class);
            $count = $passkeyService->deleteAllDevices($user);

            Log::info('[Profile] All Passkey devices deleted', [
                'user_id' => $user->id,
                'count' => $count,
            ]);

            return [
                'success' => true,
                'count' => $count,
                'message' => __('two_fa.passkey.all_devices_deleted_successfully', ['count' => $count]),
            ];
        } catch (\Exception $e) {
            Log::error('[Profile] Failed to delete all Passkey devices', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('two_fa.passkey.devices_delete_failed'),
            ];
        }
    }

    /**
     * 回復コードの残数を取得
     */
    protected function getRemainingRecoveryCodesCount($user): int
    {
        $recoveryCodeService = app(TwoFaRecoveryCodeService::class);
        return $recoveryCodeService->getRemainingCount($user);
    }

    /**
     * 回復コードが存在するかチェック
     */
    protected function hasRecoveryCodes($user): bool
    {
        $recoveryCodeService = app(TwoFaRecoveryCodeService::class);
        return $recoveryCodeService->hasRecoveryCodes($user);
    }

    /**
     * Passkeyデバイスを取得
     */
    protected function getPasskeyDevices($user)
    {
        $passkeyService = app(TwoFaPasskeyService::class);
        return $passkeyService->getDevices($user);
    }

    /**
     * Passkeyデバイスが存在するかチェック
     */
    protected function hasPasskeyDevices($user): bool
    {
        $passkeyService = app(TwoFaPasskeyService::class);
        $devices = $passkeyService->getDevices($user);
        return !$devices->isEmpty();
    }

    /**
     * 二段階認証の設定を更新
     */
    protected function updateTwoFaSettings($user, array $settings)
    {
        try {
            // 二段階認証モードの更新
            if (isset($settings['two_fa_mode'])) {
                $user->two_fa_mode = $settings['two_fa_mode'];
            }

            // デフォルト認証方法の更新
            if (isset($settings['default_two_fa_method'])) {
                $user->default_two_fa_method = $settings['default_two_fa_method'];
            }

            // Passkey有効/無効の更新
            if (isset($settings['two_fa_passkey_enabled'])) {
                $user->two_fa_passkey_enabled = $settings['two_fa_passkey_enabled'];
            }

            $user->save();

            Log::info('[Profile] Two-factor authentication settings updated', [
                'user_id' => $user->id,
                'settings' => $settings,
            ]);

            return [
                'success' => true,
                'message' => __('two_fa.settings_updated_successfully'),
            ];
        } catch (\Exception $e) {
            Log::error('[Profile] Failed to update two-factor authentication settings', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => __('two_fa.settings_update_failed'),
            ];
        }
    }

    /**
     * 設定モデルクラス名を取得（継承先で実装）
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * 現在のユーザーを取得（継承先で実装）
     */
    abstract protected function getCurrentUser();
}
