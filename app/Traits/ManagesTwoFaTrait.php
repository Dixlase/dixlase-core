<?php

namespace App\Traits;

use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 二段階認証管理の共通処理
 * 個別編集とプロフィール編集の両方で使用可能
 */
trait ManagesTwoFaTrait
{
    /**
     * Passkeyを削除（共通）
     *
     * @param  Request  $request  リクエスト
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     * @param  string  $credentialId  認証情報ID
     * @param  string  $allDeletedMessageKey  一括削除成功メッセージの翻訳キー
     * @param  string  $notFoundMessageKey  見つからないメッセージの翻訳キー
     * @param  string  $deletedMessageKey  削除成功メッセージの翻訳キー
     * @param  string  $errorMessageKey  エラーメッセージの翻訳キー
     * @return \Illuminate\Http\JsonResponse
     */
    protected function revokePasskeyForModel(
        Request $request,
        $model,
        string $credentialId,
        string $allDeletedMessageKey,
        string $notFoundMessageKey,
        string $deletedMessageKey,
        string $errorMessageKey
    ) {
        $passkeyService = new TwoFaPasskeyService();

        try {
            // 一括削除の場合
            if ($credentialId === 'all') {
                $deletedCount = $passkeyService->revokeAllCredentials($model);

                return response()->json([
                    'success' => true,
                    'message' => __($allDeletedMessageKey, ['count' => $deletedCount]),
                ]);
            }

            // 個別削除の場合
            $deleted = $passkeyService->revokeCredential($model, $credentialId);

            if (! $deleted) {
                return response()->json([
                    'success' => false,
                    'message' => __($notFoundMessageKey),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __($deletedMessageKey),
            ]);
        } catch (\Exception $e) {
            Log::error('[Passkey Delete] Exception caught', [
                'model_id' => $model->id,
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __($errorMessageKey),
            ], 500);
        }
    }

    /**
     * 回復コードを削除（共通）
     *
     * @param  Request  $request  リクエスト
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $errorMessageKey  エラーメッセージの翻訳キー
     * @return \Illuminate\Http\JsonResponse
     */
    protected function revokeRecoveryCodesForModel(
        Request $request,
        $model,
        string $successMessageKey,
        string $errorMessageKey
    ) {
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();

        try {
            $deletedCount = $twoFaRecoveryCodeService->revokeAll($model);

            return response()->json([
                'success' => true,
                'message' => __($successMessageKey, ['count' => $deletedCount]),
            ]);
        } catch (\Exception $e) {
            Log::error('[Recovery Code Delete] Exception caught', [
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __($errorMessageKey),
            ], 500);
        }
    }

    /**
     * Passkey登録用のWebAuthnチャレンジを生成（共通）
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     * @param  string  $errorMessageKey  エラーメッセージの翻訳キー
     * @return \Illuminate\Http\JsonResponse
     */
    protected function generatePasskeyRegistrationOptions(
        $model,
        string $errorMessageKey
    ) {
        $twoFaPasskeyService = new TwoFaPasskeyService();

        try {
            $options = $twoFaPasskeyService->generateRegistrationChallenge($model);

            return response()->json([
                'success' => true,
                'options' => $options,
            ]);
        } catch (\Exception $e) {
            Log::error('[Passkey] Registration challenge generation error', [
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __($errorMessageKey),
            ], 500);
        }
    }

    /**
     * Passkeyを登録（共通）
     *
     * @param  Request  $request  リクエスト
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $errorMessageKey  エラーメッセージの翻訳キー
     * @return \Illuminate\Http\JsonResponse
     */
    protected function registerPasskeyForModel(
        Request $request,
        $model,
        string $successMessageKey,
        string $errorMessageKey
    ) {
        $request->validate([
            'credential' => 'required|array',
            'credential.id' => 'required|string',
            'credential.rawId' => 'required|string',
            'credential.response' => 'required|array',
            'credential.type' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $twoFaPasskeyService = new TwoFaPasskeyService();

        try {
            $credential = $twoFaPasskeyService->registerCredential(
                $model,
                $request->input('credential'),
                $request->input('device_name')
            );

            return response()->json([
                'success' => true,
                'message' => __($successMessageKey),
                'credential' => [
                    'id' => $credential->id,
                    'name' => $credential->name,
                    'created_at' => $credential->created_at->format('Y-m-d H:i'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[Passkey] Registration error', [
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __($errorMessageKey),
            ], 500);
        }
    }

    /**
     * 回復コードを生成（共通）
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $errorMessageKey  エラーメッセージの翻訳キー
     * @return \Illuminate\Http\JsonResponse
     */
    protected function generateRecoveryCodesForModel(
        $model,
        string $successMessageKey,
        string $errorMessageKey
    ) {
        $recoveryCodeService = app(TwoFaRecoveryCodeService::class);

        // 既に回復コードが存在する場合はエラー
        if ($recoveryCodeService->hasRecoveryCodes($model)) {
            return response()->json([
                'success' => false,
                'message' => __('two_fa.recovery_codes.already_exists'),
            ], 400);
        }

        try {
            $codes = $recoveryCodeService->generate($model);

            return response()->json([
                'success' => true,
                'codes' => $codes,
                'message' => __($successMessageKey),
            ]);
        } catch (\Exception $e) {
            Log::error('[Recovery Code] Generation error', [
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __($errorMessageKey),
            ], 500);
        }
    }

    /**
     * 回復コードを再生成（共通）
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  モデル
     * @param  string  $successMessageKey  成功メッセージの翻訳キー
     * @param  string  $tooSoonMessageKey  再生成が早すぎる場合のメッセージ翻訳キー
     * @param  string  $errorMessageKey  エラーメッセージの翻訳キー
     * @return \Illuminate\Http\JsonResponse
     */
    protected function regenerateRecoveryCodesForModel(
        $model,
        string $successMessageKey,
        string $tooSoonMessageKey,
        string $errorMessageKey
    ) {
        $recoveryCodeService = app(TwoFaRecoveryCodeService::class);

        // 再生成可能かチェック
        if (! $recoveryCodeService->canRegenerate($model)) {
            $nextTime = $recoveryCodeService->getNextRegenerateTime($model);

            return response()->json([
                'success' => false,
                'message' => __($tooSoonMessageKey, [
                    'time' => $nextTime->format('Y-m-d H:i'),
                ]),
                'next_time' => $nextTime->format('Y-m-d H:i'),
            ], 429);
        }

        try {
            $codes = $recoveryCodeService->generate($model);

            return response()->json([
                'success' => true,
                'codes' => $codes,
                'message' => __($successMessageKey),
            ]);
        } catch (\Exception $e) {
            Log::error('[Recovery Code] Regeneration error', [
                'model_id' => $model->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __($errorMessageKey),
            ], 500);
        }
    }

    /**
     * 回復コードセッションをクリア（共通）
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function clearRecoveryCodesSessionData()
    {
        session()->forget('auto_generated_recovery_codes');

        return response()->json([
            'success' => true,
        ]);
    }
}
