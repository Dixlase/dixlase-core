<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Traits;

use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Common processing for two-factor authentication management
 * Can be used for both individual edit and profile edit
 */
trait ManagesTwoFaTrait
{
    /**
     * Delete Passkey (common)
     *
     * @param  Request  $request  Request
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     * @param  string  $credentialId  Credential ID
     * @param  string  $allDeletedMessageKey  Translation key for bulk deletion success message
     * @param  string  $notFoundMessageKey  Translation key for not found message
     * @param  string  $deletedMessageKey  Translation key for deletion success message
     * @param  string  $errorMessageKey  Translation key for error message
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
            // For bulk deletion
            if ($credentialId === 'all') {
                $deletedCount = $passkeyService->revokeAllCredentials($model);

                return response()->json([
                    'success' => true,
                    'message' => __($allDeletedMessageKey, ['count' => $deletedCount]),
                ]);
            }

            // For individual deletion
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
     * Delete recovery codes (common)
     *
     * @param  Request  $request  Request
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $errorMessageKey  Translation key for error message
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
     * Generate WebAuthn challenge for Passkey registration (common)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     * @param  string  $errorMessageKey  Translation key for error message
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
     * Register Passkey (common)
     *
     * @param  Request  $request  Request
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $errorMessageKey  Translation key for error message
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
     * Generate recovery codes (common)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $errorMessageKey  Translation key for error message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function generateRecoveryCodesForModel(
        $model,
        string $successMessageKey,
        string $errorMessageKey
    ) {
        $recoveryCodeService = app(TwoFaRecoveryCodeService::class);

        // Error if recovery codes already exist
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
     * Regenerate recovery codes (common)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $tooSoonMessageKey  Translation key for message when regeneration is too soon
     * @param  string  $errorMessageKey  Translation key for error message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function regenerateRecoveryCodesForModel(
        $model,
        string $successMessageKey,
        string $tooSoonMessageKey,
        string $errorMessageKey
    ) {
        $recoveryCodeService = app(TwoFaRecoveryCodeService::class);

        // Check if regeneration is possible
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
     * Clear recovery code session (common)
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
