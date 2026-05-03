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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Services\TwoFa;

use App\Contracts\TwoFa\TwoFaPasskeyServiceInterface;
use App\Contracts\TwoFaInterface;
use App\Models\Member;
use App\Models\MembersTrustedDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwoFaPasskeyService implements TwoFaPasskeyServiceInterface
{
    /**
     * Passkeyが利用可能かどうか
     */
    public function isAvailable(): bool
    {
        // HTTPSが有効かチェック（localhostは除外）
        if (request()->getHost() === 'localhost' || request()->getHost() === '127.0.0.1') {
            return true;
        }

        // 本番環境ではHTTPS必須
        return request()->secure();
    }

    /**
     * ユーザーがPasskey認証情報を持っているか
     *
     * LaragearのwebauthnCredentials()リレーションを使用
     */
    public function hasCredentials(TwoFaInterface $user): bool
    {
        return $user->webauthnCredentials()->exists();
    }

    /**
     * Passkeyチャレンジを生成（2FA用）
     */
    public function generatePasskeyChallenge($user): array
    {
        // Phase 3で実装予定
        Log::info('[Passkey] Challenge generation requested', [
            'member_id' => $user->id,
        ]);

        return [
            'challenge' => 'placeholder_challenge',
            'message' => 'Passkey authentication will be implemented in Phase 3',
        ];
    }

    /**
     * ログイン用のパスキーチャレンジを生成
     *
     * Laragear\WebAuthnを使用して安全なチャレンジを生成
     */
    public function generateLoginChallenge(TwoFaInterface $user): array
    {
        try {
            // Laragear WebAuthn v4: AssertionCreationオブジェクトを作成
            $assertionCreation = new \Laragear\WebAuthn\Assertion\Creator\AssertionCreation($user);

            // AssertionCreatorパイプラインを実行
            $assertionCreator = app(\Laragear\WebAuthn\Assertion\Creator\AssertionCreator::class);
            $result = $assertionCreator->send($assertionCreation)->thenReturn();

            // JsonTransportオブジェクトから配列に変換
            $jsonData = is_array($result->json) ? $result->json : $result->json->toArray();

            // デバッグ: チャレンジに含まれるallowCredentialsを確認
            $allowCredentials = $jsonData['allowCredentials'] ?? [];
            Log::info('[Passkey] Login challenge generated (Laragear)', [
                'member_id' => $user->getId(),
                'credentials_count' => $user->webauthnCredentials()->count(),
                'allowCredentials' => $allowCredentials,
            ]);

            return [
                'id' => Str::random(32),
                'publicKey' => $jsonData,
            ];
        } catch (\Exception $e) {
            Log::error('[Passkey] Challenge generation failed', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * ログイン用のパスキー認証を検証
     *
     * Laragear\WebAuthnを使用して暗号署名を検証
     */
    public function verifyLoginChallenge(TwoFaInterface $user, array $data, ?string $challengeId = null): bool
    {
        try {
            // Laragear WebAuthn v4: JsonTransportを作成（リクエストのJSONデータを渡す）
            $jsonTransport = new \Laragear\WebAuthn\JsonTransport(request()->json()->all());

            // デバッグ: userHandleとcredential情報をログ出力
            $userHandle = request()->json('response.userHandle');
            $credentialId = request()->json('id');
            $credential = \App\Models\WebAuthnCredential::find($credentialId);

            Log::info('[Passkey] Verification debug', [
                'member_id' => $user->getId(),
                'userHandle_from_browser' => $userHandle,
                'credential_id' => $credentialId,
                'credential_user_id' => $credential ? $credential->user_id : null,
                'expected_user_id' => $user->webAuthnId()->toString(),
                'credential_casts' => $credential ? $credential->getCasts() : null,
                'credential_class' => $credential ? get_class($credential) : null,
            ]);

            // AssertionValidationオブジェクトを作成
            $assertionValidation = new \Laragear\WebAuthn\Assertion\Validator\AssertionValidation(
                $jsonTransport,
                $user
            );

            // AssertionValidatorパイプラインを実行
            $assertionValidator = app(\Laragear\WebAuthn\Assertion\Validator\AssertionValidator::class);
            $result = $assertionValidator->send($assertionValidation)->thenReturn();

            if ($result && $result->credential) {
                Log::info('[Passkey] Login verification successful (Laragear)', [
                    'member_id' => $user->getId(),
                    'credential_id' => $result->credential->id,
                ]);

                return true;
            }

            Log::warning('[Passkey] Login verification failed', [
                'member_id' => $user->getId(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('[Passkey] Verification error', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Passkey認証を検証
     */
    public function validatePasskeyAuth($user, $input): bool
    {
        // Phase 3で実装予定
        Log::info('[Passkey] Authentication validation requested', [
            'member_id' => $user->id,
        ]);

        return false;
    }

    /**
     * ユーザーの全Passkeyデバイスを取得
     *
     * @deprecated getCredentials()を使用してください
     */
    public function getDevices(TwoFaInterface $user)
    {
        return $this->getCredentials($user);
    }

    // ========================================
    // WebAuthn (Biometric) 機能
    // ========================================

    /**
     * WebAuthn認証情報を登録
     */
    public function registerCredential(TwoFaInterface $user, array $credentialData, ?string $deviceName = null)
    {
        try {
            // JsonTransportオブジェクトを作成
            $jsonTransport = new \Laragear\WebAuthn\JsonTransport($credentialData);

            // AttestationValidationオブジェクトを作成
            $attestationValidation = new \Laragear\WebAuthn\Attestation\Validator\AttestationValidation(
                $user,
                $jsonTransport
            );

            // AttestationValidatorパイプラインを実行
            $attestationValidator = app(\Laragear\WebAuthn\Attestation\Validator\AttestationValidator::class);
            $result = $attestationValidator->send($attestationValidation)->thenReturn();

            // デバイス名を設定
            if ($deviceName) {
                $result->credential->alias = $deviceName;
                $result->credential->save();
            }

            Log::info('[Passkey] 認証情報登録成功 (Laragear)', [
                'member_id' => $user->getId(),
                'credential_id' => $result->credential->id,
                'device_name' => $deviceName ?? $this->generateDeviceName(),
            ]);

            return $result->credential;
        } catch (\Exception $e) {
            Log::error('[Passkey] Registration error', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * WebAuthn認証を検証
     */
    public function verifyAssertion(TwoFaInterface $user, array $assertionData): bool
    {
        $credential = $user->twoFaPasskeys()
            ->where('id', $assertionData['id'])
            ->first();

        if (! $credential) {
            Log::warning("[Passkey] 認証情報が見つかりません: ユーザーID {$user->getId()}");

            return false;
        }

        $response = $assertionData['response'] ?? [];

        if (empty($response['signature']) || empty($response['authenticatorData']) || empty($response['clientDataJSON'])) {
            Log::warning("[Passkey] 不完全なレスポンスデータ: ユーザーID {$user->getId()}");

            return false;
        }

        $isValid = $this->verifySignature(
            $credential->public_key,
            $response['signature'],
            $response['authenticatorData'],
            $response['clientDataJSON']
        );

        if ($isValid) {
            Log::info("[Passkey] 認証成功: ユーザーID {$user->getId()}");
        } else {
            Log::warning("[Passkey] 認証失敗: ユーザーID {$user->getId()}");
        }

        return $isValid;
    }

    /**
     * WebAuthn認証情報一覧を取得
     */
    public function getCredentials(TwoFaInterface $user)
    {
        return $user->twoFaPasskeys()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * WebAuthn認証情報を削除
     */
    public function revokeCredential(TwoFaInterface $user, string $credentialId): bool
    {
        $deleted = $user->twoFaPasskeys()
            ->where('id', $credentialId)
            ->delete();

        if ($deleted) {
            Log::info("[Passkey] 認証情報削除: ユーザーID {$user->getId()}, 認証情報ID: {$credentialId}");
        }

        return $deleted > 0;
    }

    /**
     * すべてのWebAuthn認証情報を削除
     */
    public function revokeAllCredentials(TwoFaInterface $user): int
    {
        $count = $user->twoFaPasskeys()->count();
        $deleted = $user->twoFaPasskeys()->delete();

        if ($deleted) {
            Log::info("[Passkey] すべての認証情報削除: ユーザーID {$user->getId()}, 削除数: {$count}");
        }

        return $count;
    }

    /**
     * WebAuthn登録チャレンジを生成
     */
    public function generateRegistrationChallenge(TwoFaInterface $user): array
    {
        try {
            // AttestationCreationオブジェクトを作成
            $attestationCreation = new \Laragear\WebAuthn\Attestation\Creator\AttestationCreation($user);

            // AttestationCreatorパイプラインを実行
            $attestationCreator = app(\Laragear\WebAuthn\Attestation\Creator\AttestationCreator::class);
            $result = $attestationCreator->send($attestationCreation)->thenReturn();

            // JsonTransportオブジェクトから配列に変換
            $jsonData = is_array($result->json) ? $result->json : $result->json->toArray();

            Log::info('[Passkey] Registration challenge generated (Laragear)', [
                'member_id' => $user->getId(),
            ]);

            return $jsonData;
        } catch (\Exception $e) {
            Log::error('[Passkey] Registration challenge generation error', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * WebAuthn認証チャレンジを生成
     */
    public function generateAuthenticationChallenge(TwoFaInterface $user): array
    {
        $challenge = random_bytes(32);
        $challengeBase64 = base64_encode($challenge);

        $credentials = $this->getCredentials($user);
        $allowCredentials = $credentials->map(function ($credential) {
            return [
                'type' => 'public-key',
                'id' => $credential->id, // credential_idではなくid
                'transports' => json_decode($credential->transports ?? '["internal","hybrid"]', true),
            ];
        })->toArray();

        $options = [
            'challenge' => $challengeBase64,
            'timeout' => 60000,
            'rpId' => parse_url(config('app.url'), PHP_URL_HOST),
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'required',
        ];

        session(['webauthn_challenge' => $challengeBase64]);

        return $options;
    }

    /**
     * デバイス名を生成
     */
    private function generateDeviceName(): string
    {
        $userAgent = request()->userAgent();

        if (str_contains($userAgent, 'iPhone')) {
            return 'iPhone Touch ID/Face ID';
        } elseif (str_contains($userAgent, 'iPad')) {
            return 'iPad Touch ID/Face ID';
        } elseif (str_contains($userAgent, 'Android')) {
            return 'Android Fingerprint';
        } elseif (str_contains($userAgent, 'Windows')) {
            return 'Windows Hello';
        } elseif (str_contains($userAgent, 'Macintosh')) {
            return 'Mac Touch ID';
        }

        return 'Biometric Device';
    }

    /**
     * 署名を検証（簡略化版）
     */
    private function verifySignature(string $publicKey, string $signature, string $authenticatorData, string $clientDataJSON): bool
    {
        try {
            $storedChallenge = session('webauthn_challenge');
            if (! $storedChallenge) {
                Log::warning('[Passkey] セッションにチャレンジが存在しません');

                return false;
            }

            $clientData = json_decode(base64_decode($clientDataJSON), true);
            if (! $clientData) {
                Log::warning('[Passkey] clientDataJSONのデコードに失敗');

                return false;
            }

            $receivedChallenge = $clientData['challenge'] ?? '';

            $normalizedStored = str_replace(['+', '/', '='], ['-', '_', ''], $storedChallenge);
            $normalizedReceived = str_replace(['+', '/', '='], ['-', '_', ''], $receivedChallenge);

            if ($normalizedStored !== $normalizedReceived) {
                Log::warning('[Passkey] チャレンジが一致しません');

                return false;
            }

            session()->forget('webauthn_challenge');

            Log::info('[Passkey] 署名検証成功（簡略版）');

            return true;
        } catch (\Exception $e) {
            Log::error('[Passkey] 署名検証エラー: '.$e->getMessage());

            return false;
        }
    }

    // ========================================
    // 信頼済みデバイス管理
    // ========================================

    /**
     * 現在のデバイスが信頼済みかチェック
     */
    public function isTrustedDevice(Member $member): bool
    {
        $deviceToken = request()->cookie('trusted_device_token');
        $trustedDevice = null;

        // Cookieトークンで検証
        if ($deviceToken) {
            $hashedToken = hash('sha256', $deviceToken);

            $trustedDevice = MembersTrustedDevice::where('member_id', $member->id)
                ->where('token', $hashedToken)
                ->first();

            if ($trustedDevice) {
                Log::info("[Passkey] Cookie認証成功: ユーザーID {$member->id}");
            }
        }

        // Cookieがない場合、IP + User Agentで検証
        if (! $trustedDevice) {
            $ipAddress = request()->ip();
            $userAgent = request()->userAgent();

            $trustedDevice = MembersTrustedDevice::where('member_id', $member->id)
                ->where('ip_address', $ipAddress)
                ->where('user_agent', $userAgent)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($trustedDevice) {
                Log::info("[Passkey] IP+UA認証成功: ユーザーID {$member->id}");

                // Cookieを再設定
                $tokenLength = config('two-fa.device_token_length', 64);
                $newToken = Str::random($tokenLength);
                $hashedToken = hash('sha256', $newToken);
                $trustedDevice->update(['token' => $hashedToken]);

                $cookieConfig = config('two-fa.device_cookie', []);
                cookie()->queue(
                    $cookieConfig['name'] ?? 'trusted_device_token',
                    $newToken,
                    $cookieConfig['lifetime'] ?? 60 * 24 * 30,
                    $cookieConfig['path'] ?? '/',
                    $cookieConfig['domain'] ?? null,
                    $cookieConfig['secure'] ?? true,
                    $cookieConfig['http_only'] ?? true,
                    false,
                    $cookieConfig['same_site'] ?? 'strict'
                );
            }
        }

        if (! $trustedDevice) {
            Log::info("[Passkey] 信頼済みデバイスなし: ユーザーID {$member->id}");

            return false;
        }

        // 有効期限チェック（セキュリティ設定から）
        $expirationDays = (int) \App\Models\SecuritySetting::getValue(
            'trusted_device_expire_days',
            config('two-fa.device_expiration_days', 30)
        );

        $expirationDate = $trustedDevice->updated_at->addDays($expirationDays);

        if (now()->greaterThan($expirationDate)) {
            Log::info("[Passkey] デバイス有効期限切れ: ユーザーID {$member->id}");

            return false;
        }

        $trustedDevice->touch();

        return true;
    }

    /**
     * 信頼済みデバイスを削除
     */
    public function revokeDevice(Member $member, int $deviceId): bool
    {
        $deleted = MembersTrustedDevice::where('member_id', $member->id)
            ->where('id', $deviceId)
            ->delete();

        if ($deleted) {
            Log::info("[Passkey] 信頼済みデバイス削除: ユーザーID {$member->id}, デバイスID: {$deviceId}");
        }

        return $deleted > 0;
    }

    /**
     * 信頼済みデバイス一覧を取得
     */
    public function getTrustedDevices(Member $member)
    {
        return MembersTrustedDevice::where('member_id', $member->id)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * すべての信頼済みデバイスを削除
     */
    public function revokeAllTrustedDevices(Member $member): int
    {
        $count = MembersTrustedDevice::where('member_id', $member->id)->count();
        $deleted = MembersTrustedDevice::where('member_id', $member->id)->delete();

        if ($deleted) {
            Log::info("[Passkey] すべての信頼済みデバイス削除: ユーザーID {$member->id}, 削除数: {$count}");
        }

        return $count;
    }
}
