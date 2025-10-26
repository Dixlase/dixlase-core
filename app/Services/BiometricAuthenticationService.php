<?php

namespace App\Services;

use App\Models\Member;
use App\Models\WebauthnCredential;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BiometricAuthenticationService
{
    /**
     * WebAuthn認証情報を登録
     *
     * @param Member $member
     * @param array $credentialData
     * @param string $deviceName
     * @return WebauthnCredential
     */
    public function registerCredential(Member $member, array $credentialData, string $deviceName = null): WebauthnCredential
    {
        // WebAuthnレスポンスからpublic keyを抽出
        // 実際の実装では、attestationObjectをパースしてpublic keyを取得する
        // ここでは簡略化のため、credential IDをpublic keyとして保存
        $publicKey = $credentialData['publicKey'] ?? $credentialData['id'];
        
        $credential = WebauthnCredential::create([
            'member_id' => $member->id,
            'credential_id' => $credentialData['id'],
            'public_key' => $publicKey,
            'name' => $deviceName ?? $this->generateDeviceName(),
        ]);
        
        Log::info("[Biometric Auth] 認証情報登録: ユーザーID {$member->id}, デバイス: " . ($deviceName ?? $this->generateDeviceName()));
        
        return $credential;
    }
    
    /**
     * WebAuthn認証を検証
     *
     * @param Member $member
     * @param array $assertionData
     * @return bool
     */
    public function verifyAssertion(Member $member, array $assertionData): bool
    {
        $credential = WebauthnCredential::where('member_id', $member->id)
            ->where('credential_id', $assertionData['id'])
            ->first();
            
        if (!$credential) {
            Log::warning("[Biometric Auth] 認証情報が見つかりません: ユーザーID {$member->id}, 認証情報ID: {$assertionData['id']}");
            return false;
        }
        
        // レスポンスデータの取得
        $response = $assertionData['response'] ?? [];
        
        if (empty($response['signature']) || empty($response['authenticatorData']) || empty($response['clientDataJSON'])) {
            Log::warning("[Biometric Auth] 不完全なレスポンスデータ: ユーザーID {$member->id}");
            return false;
        }
        
        // 実際の実装では、WebAuthn仕様に従った署名検証を行う
        // ここでは簡略化した検証を行う
        $isValid = $this->verifySignature(
            $credential->public_key,
            $response['signature'],
            $response['authenticatorData'],
            $response['clientDataJSON']
        );
        
        if ($isValid) {
            Log::info("[Biometric Auth] 認証成功: ユーザーID {$member->id}, 認証情報ID: {$credential->credential_id}");
        } else {
            Log::warning("[Biometric Auth] 認証失敗: ユーザーID {$member->id}, 認証情報ID: {$credential->credential_id}");
        }
        
        return $isValid;
    }
    
    /**
     * メンバーの生体認証情報一覧を取得
     *
     * @param Member $member
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCredentials(Member $member)
    {
        return WebauthnCredential::where('member_id', $member->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }
    
    /**
     * 生体認証情報を削除
     *
     * @param Member $member
     * @param string $credentialId
     * @return bool
     */
    public function revokeCredential(Member $member, string $credentialId): bool
    {
        $deleted = WebauthnCredential::where('member_id', $member->id)
            ->where('credential_id', $credentialId)
            ->delete();
            
        if ($deleted) {
            Log::info("[Biometric Auth] 認証情報削除: ユーザーID {$member->id}, 認証情報ID: {$credentialId}");
        }
        
        return $deleted > 0;
    }
    
    /**
     * WebAuthn登録チャレンジを生成
     *
     * @param Member $member
     * @return array
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
                'displayName' => $member->name ?? $member->email,
            ],
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7], // ES256
                ['type' => 'public-key', 'alg' => -257], // RS256
            ],
            'timeout' => 60000,
            'attestation' => 'direct',
            'authenticatorSelection' => [
                'authenticatorAttachment' => 'platform',
                'userVerification' => 'required',
            ],
        ];
        
        // セッションにチャレンジを保存
        session(['webauthn_challenge' => $challengeBase64]);
        
        return $options;
    }
    
    /**
     * WebAuthn認証チャレンジを生成
     *
     * @param Member $member
     * @return array
     */
    public function generateAuthenticationChallenge(Member $member): array
    {
        $challenge = random_bytes(32);
        $challengeBase64 = base64_encode($challenge);
        
        $credentials = $this->getCredentials($member);
        $allowCredentials = $credentials->map(function ($credential) {
            return [
                'type' => 'public-key',
                'id' => $credential->credential_id,
            ];
        })->toArray();
        
        $options = [
            'challenge' => $challengeBase64,
            'timeout' => 60000,
            'rpId' => parse_url(config('app.url'), PHP_URL_HOST),
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'required',
        ];
        
        // セッションにチャレンジを保存
        session(['webauthn_challenge' => $challengeBase64]);
        
        return $options;
    }
    
    /**
     * デバイス名を生成
     *
     * @return string
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
     *
     * @param string $publicKey
     * @param string $signature
     * @param string $authenticatorData
     * @param string $clientDataJSON
     * @return bool
     */
    private function verifySignature(string $publicKey, string $signature, string $authenticatorData, string $clientDataJSON): bool
    {
        // 実際の実装では、WebAuthn仕様に従った詳細な署名検証を行う
        // ここでは簡略化した検証を行う
        
        try {
            // チャレンジの検証
            $storedChallenge = session('webauthn_challenge');
            if (!$storedChallenge) {
                Log::warning("[Biometric Auth] セッションにチャレンジが存在しません");
                return false;
            }
            
            // clientDataJSONをデコード
            $clientData = json_decode(base64_decode($clientDataJSON), true);
            if (!$clientData) {
                Log::warning("[Biometric Auth] clientDataJSONのデコードに失敗");
                return false;
            }
            
            // チャレンジの比較（Base64URL形式を考慮）
            $receivedChallenge = $clientData['challenge'] ?? '';
            
            // Base64とBase64URLの変換を考慮
            $normalizedStored = str_replace(['+', '/', '='], ['-', '_', ''], $storedChallenge);
            $normalizedReceived = str_replace(['+', '/', '='], ['-', '_', ''], $receivedChallenge);
            
            if ($normalizedStored !== $normalizedReceived) {
                Log::warning("[Biometric Auth] チャレンジが一致しません");
                return false;
            }
            
            // セッションからチャレンジを削除
            session()->forget('webauthn_challenge');
            
            Log::info("[Biometric Auth] 署名検証成功（簡略版）");
            
            // 実際の署名検証はWebAuthnライブラリを使用することを推奨
            // 本番環境では、webauthn-lib/webauthn-lib などのライブラリを使用してください
            return true;
        } catch (\Exception $e) {
            Log::error("[Biometric Auth] 署名検証エラー: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 生体認証が利用可能かどうかを確認
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        // HTTPS接続が必要
        if (!request()->secure() && !app()->environment('local')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * メンバーが生体認証を設定済みかどうかを確認
     *
     * @param Member $member
     * @return bool
     */
    public function hasCredentials(Member $member): bool
    {
        return WebauthnCredential::where('member_id', $member->id)->exists();
    }
}
