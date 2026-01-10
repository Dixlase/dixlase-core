<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 二段階認証機能を持つユーザーのインターフェース
 */
interface TwoFaInterface
{
    /**
     * ユーザーIDを取得
     */
    public function getId(): int;

    /**
     * メールアドレスを取得
     */
    public function getEmail(): string;

    /**
     * 表示名を取得
     */
    public function getDisplayName(): string;

    /**
     * アカウント名を取得
     */
    public function getAccountName(): ?string;

    /**
     * 二段階認証モードを取得
     */
    public function getTwoFaMode(): int;

    /**
     * パスキーが有効かどうか
     */
    public function isTwoFaPasskeyEnabled(): bool;

    /**
     * デフォルトの二段階認証方法を取得
     */
    public function getTwoFaDefaultMethod(): int;

    /**
     * パスキーデバイスのリレーション
     */
    public function twoFaPasskeys(): HasMany;

    /**
     * 回復コードのリレーション
     */
    public function twoFaRecoveryCodes(): HasMany;

    /**
     * 二段階認証試行のリレーション
     */
    public function twoFaAttempts(): HasMany;
}
