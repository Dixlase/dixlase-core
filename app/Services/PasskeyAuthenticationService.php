<?php

namespace App\Services;

use App\Models\Member;
use App\Models\MemberPasskey;
use Illuminate\Support\Facades\Log;

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
        return MemberPasskey::where('member_id', $user->id)->exists();
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
    public function register($user, string $credentialId, string $publicKey, string $name): MemberPasskey
    {
        return MemberPasskey::create([
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
        $passkey = MemberPasskey::where('member_id', $user->id)
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
        return MemberPasskey::where('member_id', $user->id)
            ->where('id', $passkeyId)
            ->delete() > 0;
    }

    /**
     * ユーザーの全Passkeyデバイスを取得
     */
    public function getDevices($user)
    {
        return MemberPasskey::where('member_id', $user->id)
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
        $currentCount = MemberPasskey::where('member_id', $user->id)->count();
        return $currentCount >= $this->getMaxDevices();
    }
}
