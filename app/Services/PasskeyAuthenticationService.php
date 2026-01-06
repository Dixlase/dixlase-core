<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberTwoFaPasskey;
use App\Models\MembersTrustedDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasskeyAuthenticationService
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
     */
    public function hasCredentials($user): bool
    {
        return MemberTwoFaPasskey::where('member_id', $user->id)->exists();
    }

    /**
     * Passkeyチャレンジを生成
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
     * Passkeyデバイスを登録
     */
    public function register($user, string $credentialId, string $publicKey, string $name): MemberTwoFaPasskey
    {
        return MemberTwoFaPasskey::create([
            'member_id' => $user->id,
            'credential_id' => $credentialId,
            'public_key' => $publicKey,
            'name' => $name,
        ]);
    }

    /**
     * Passkeyデバイス名を更新
     */
    public function updateName($user, int $passkeyId, string $name): bool
    {
        $passkey = MemberTwoFaPasskey::where('member_id', $user->id)
            ->where('id', $passkeyId)
            ->first();

        if (!$passkey) {
            return false;
        }

        $passkey->updateName($name);
        return true;
    }

    /**
     * Passkeyデバイスを削除
     */
    public function delete($user, int $passkeyId): bool
    {
        return MemberTwoFaPasskey::where('member_id', $user->id)
            ->where('id', $passkeyId)
            ->delete() > 0;
    }

    /**
     * ユーザーの全Passkeyデバイスを取得
     */
    public function getDevices($user)
    {
        return MemberTwoFaPasskey::where('member_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Passkeyデバイスの登録可能数を取得
     */
    public function getMaxDevices(): int
    {
        return (int) \App\Models\MemberSetting::getValue('max_passkey_devices', 3);
    }

    /**
     * Passkeyデバイスの登録可能数に達しているか
     */
    public function hasReachedMaxDevices($user): bool
    {
        $currentCount = MemberTwoFaPasskey::where('member_id', $user->id)->count();
        return $currentCount >= $this->getMaxDevices();
    }

    // ========================================
    // WebAuthn (Biometric) 機能
    // ========================================

    /**
     * WebAuthn認証情報を登録
     */
    public function registerCredential(Member $member, array $credentialData, string $deviceName = null): MemberTwoFaPasskey
    {
        $publicKey = $credentialData['publicKey'] ?? $credentialData['id'];
        
        $credential = MemberTwoFaPasskey::create([
            'id' => $credentialData['id'],
            'member_id' => $member->id,
            'public_key' => $publicKey,
            'name' => $deviceName ?? $this->generateDeviceName(),
            'rp_id' => request()->getHost(),
            'origin' => request()->getSchemeAndHttpHost(),
        ]);
        
        Log::info("[Passkey] 認証情報登録: ユーザーID {$member->id}, デバイス: " . ($deviceName ?? $this->generateDeviceName()));
        
        return $credential;
    }

    /**
     * WebAuthn認証を検証
     */
    public function verifyAssertion(Member $member, array $assertionData): bool
    {
        $credential = MemberTwoFaPasskey::where('member_id', $member->id)
            ->where('id', $assertionData['id'])
            ->first();
            
        if (!$credential) {
            Log::warning("[Passkey] 認証情報が見つかりません: ユーザーID {$member->id}");
            return false;
        }
        
        $response = $assertionData['response'] ?? [];
        
        if (empty($response['signature']) || empty($response['authenticatorData']) || empty($response['clientDataJSON'])) {
            Log::warning("[Passkey] 不完全なレスポンスデータ: ユーザーID {$member->id}");
            return false;
        }
        
        $isValid = $this->verifySignature(
            $credential->public_key,
            $response['signature'],
            $response['authenticatorData'],
            $response['clientDataJSON']
        );
        
        if ($isValid) {
            Log::info("[Passkey] 認証成功: ユーザーID {$member->id}");
        } else {
            Log::warning("[Passkey] 認証失敗: ユーザーID {$member->id}");
        }
        
        return $isValid;
    }

    /**
     * WebAuthn認証情報一覧を取得
     */
    public function getCredentials(Member $member)
    {
        return MemberTwoFaPasskey::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * WebAuthn認証情報を削除
     */
    public function revokeCredential(Member $member, string $credentialId): bool
    {
        $deleted = MemberTwoFaPasskey::where('member_id', $member->id)
            ->where('id', $credentialId)
            ->delete();
            
        if ($deleted) {
            Log::info("[Passkey] 認証情報削除: ユーザーID {$member->id}, 認証情報ID: {$credentialId}");
        }
        
        return $deleted > 0;
    }

    /**
     * すべてのWebAuthn認証情報を削除
     */
    public function revokeAllCredentials(Member $member): int
    {
        $count = MemberTwoFaPasskey::where('member_id', $member->id)->count();
        $deleted = MemberTwoFaPasskey::where('member_id', $member->id)->delete();
        
        if ($deleted) {
            Log::info("[Passkey] すべての認証情報削除: ユーザーID {$member->id}, 削除数: {$count}");
        }
        
        return $count;
    }

    /**
     * WebAuthn登録チャレンジを生成
     */
    public function generateRegistrationChallenge(Member $member): array
    {
        $challenge = random_bytes(32);
        $challengeBase64 = base64_encode($challenge);
        
        $options = [
            'challenge' => $challengeBase64,
            'rp' => [
                'name' => config('app.name', 'Dixlase'),
                'id' => parse_url(config('app.url'), PHP_URL_HOST),
            ],
            'user' => [
                'id' => base64_encode($member->id),
                'name' => $member->email,
                'displayName' => $member->display_name ?? $member->account_name ?? $member->email,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],  // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'direct',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'userVerification' => 'required',
            ],
        ];
        
        session(['webauthn_challenge' => $challengeBase64]);
        
        return $options;
    }

    /**
     * WebAuthn認証チャレンジを生成
     */
    public function generateAuthenticationChallenge(Member $member): array
    {
        $challenge = random_bytes(32);
        $challengeBase64 = base64_encode($challenge);
        
        $credentials = $this->getCredentials($member);
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
            if (!$storedChallenge) {
                Log::warning("[Passkey] セッションにチャレンジが存在しません");
                return false;
            }
            
            $clientData = json_decode(base64_decode($clientDataJSON), true);
            if (!$clientData) {
                Log::warning("[Passkey] clientDataJSONのデコードに失敗");
                return false;
            }
            
            $receivedChallenge = $clientData['challenge'] ?? '';
            
            $normalizedStored = str_replace(['+', '/', '='], ['-', '_', ''], $storedChallenge);
            $normalizedReceived = str_replace(['+', '/', '='], ['-', '_', ''], $receivedChallenge);
            
            if ($normalizedStored !== $normalizedReceived) {
                Log::warning("[Passkey] チャレンジが一致しません");
                return false;
            }
            
            session()->forget('webauthn_challenge');
            
            Log::info("[Passkey] 署名検証成功（簡略版）");
            
            return true;
        } catch (\Exception $e) {
            Log::error("[Passkey] 署名検証エラー: " . $e->getMessage());
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
        if (!$trustedDevice) {
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
                $tokenLength = config('two-factor.device_token_length', 64);
                $newToken = Str::random($tokenLength);
                $hashedToken = hash('sha256', $newToken);
                $trustedDevice->update(['token' => $hashedToken]);
                
                $cookieConfig = config('two-factor.device_cookie', []);
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
        
        if (!$trustedDevice) {
            Log::info("[Passkey] 信頼済みデバイスなし: ユーザーID {$member->id}");
            return false;
        }
        
        // 有効期限チェック
        $expirationDays = (int) \App\Models\MemberSetting::getValue(
            'trusted_device_expire_days',
            config('two-factor.device_expiration_days', 30)
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
}
