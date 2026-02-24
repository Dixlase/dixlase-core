<?php

namespace App\Contracts\TwoFa;

use App\Contracts\TwoFaInterface;
use App\Models\Member;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * Passkey（WebAuthn）認証サービスの契約
 *
 * パスキーの登録・認証・信頼済みデバイス管理等を提供します。
 */
interface TwoFaPasskeyServiceInterface
{
    /**
     * Passkeyが利用可能かどうか
     */
    public function isAvailable(): bool;

    /**
     * ユーザーがPasskey認証情報を持っているか
     */
    public function hasCredentials(TwoFaInterface $user): bool;

    /**
     * Passkeyチャレンジを生成（2FA用）
     */
    public function generatePasskeyChallenge($user): array;

    /**
     * ログイン用のパスキーチャレンジを生成
     */
    public function generateLoginChallenge(TwoFaInterface $user): array;

    /**
     * ログイン用のパスキー認証を検証
     */
    public function verifyLoginChallenge(TwoFaInterface $user, array $data, ?string $challengeId = null): bool;

    /**
     * Passkey認証を検証
     */
    public function validatePasskeyAuth($user, $input): bool;

    /**
     * ユーザーの全Passkeyデバイスを取得
     *
     * @deprecated getCredentials()を使用してください
     */
    public function getDevices(TwoFaInterface $user);

    /**
     * WebAuthn認証情報を登録
     */
    public function registerCredential(TwoFaInterface $user, array $credentialData, ?string $deviceName = null);

    /**
     * WebAuthn認証を検証
     */
    public function verifyAssertion(TwoFaInterface $user, array $assertionData): bool;

    /**
     * WebAuthn認証情報一覧を取得
     */
    public function getCredentials(TwoFaInterface $user);

    /**
     * WebAuthn認証情報を削除
     */
    public function revokeCredential(TwoFaInterface $user, string $credentialId): bool;

    /**
     * すべてのWebAuthn認証情報を削除
     */
    public function revokeAllCredentials(TwoFaInterface $user): int;

    /**
     * WebAuthn登録チャレンジを生成
     */
    public function generateRegistrationChallenge(TwoFaInterface $user): array;

    /**
     * WebAuthn認証チャレンジを生成
     */
    public function generateAuthenticationChallenge(TwoFaInterface $user): array;

    /**
     * 現在のデバイスが信頼済みかチェック
     */
    public function isTrustedDevice(Member $member): bool;

    /**
     * 信頼済みデバイスを削除
     */
    public function revokeDevice(Member $member, int $deviceId): bool;

    /**
     * 信頼済みデバイス一覧を取得
     */
    public function getTrustedDevices(Member $member);

    /**
     * すべての信頼済みデバイスを削除
     */
    public function revokeAllTrustedDevices(Member $member): int;
}
