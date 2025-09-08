<?php

namespace App\Services;

use App\Helpers\TwoFactorHelper;
use App\Mail\MembersTwoFactorLoginCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\MembersTwoFactorToken;
use Illuminate\Support\Hash;
use App\Models\MemberSetting;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;

class AdminTwoFactorService
{
    protected TwoFactorHelper $helper;

    public function __construct(TwoFactorHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * 二段階認証コードを生成してメール送信
     *
     * @param mixed $user ユーザーモデル
     * @return string 生成されたコード
     */
    public function generate($user): string
    {
        return $this->helper->generateAndSendCode(
            $user,
            MembersTwoFactorLoginCodeMail::class
        );
    }

    /**
     * 二段階認証コードを検証
     *
     * @param mixed $user ユーザーモデル
     * @param string $inputCode 入力されたコード
     * @return bool 検証結果
     */
    public function validate($user, string $inputCode): bool
    {
        return $this->helper->validateTwoFactorCode($user, $inputCode);
    }

    /**
     * 二段階認証が必要かどうかを判定
     *
     * @param mixed $member ユーザーモデル
     * @return bool 2FAが必要かどうか
     */
    public function has($member): bool
    {
        return $this->helper->isTwoFactorEnabled($member);
    }

    /**
     * 異なる環境からのアクセスかどうかを判定
     *
     * @param mixed $member ユーザーモデル
     * @return bool 異なる環境かどうか
     */
    public function isDifferentEnvironment($member): bool
    {
        return $this->helper->isDifferentEnvironment($member);
    }

    /**
     * 信頼済みデバイスからのアクセスかどうかを判定
     *
     * @param mixed $member ユーザーモデル
     * @return bool 信頼済みデバイスかどうか
     */
    private function isFromTrustedDevice($member): bool
    {
        return $this->helper->isFromTrustedDevice($member);
    }

    /**
     * 使用する認証方法を取得
     *
     * @param mixed $user ユーザーモデル
     * @return int 認証方法
     */
    public function getEffectiveAuthMethod($user): int
    {
        return $this->helper->getEffectiveAuthMethod($user);
    }

    /**
     * システムの二段階認証設定を取得
     *
     * @return array 設定配列
     */
    public function getSystemSettings(): array
    {
        return $this->helper->getSystemTwoFactorSettings();
    }
}
