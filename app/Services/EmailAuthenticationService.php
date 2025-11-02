<?php

namespace App\Services;

use App\Helpers\TwoFactorHelper;
use App\Mail\MembersTwoFactorCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailAuthenticationService
{
    protected TwoFactorHelper $helper;

    public function __construct(TwoFactorHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * メール認証コードを生成してメール送信
     *
     * @param mixed $user ユーザーモデル
     * @param string $context コンテキスト（admin, user等）
     * @param int|null $expireMinutes 有効期限（分）
     * @return string 生成されたコード
     */
    public function generateAndSendCode($user, string $context = 'admin', int $expireMinutes = null): string
    {
        return $this->helper->generateAndSendCode(
            $user,
            MembersTwoFactorCodeMail::class,
            $expireMinutes,
            $context
        );
    }

    /**
     * メール認証コードを検証
     *
     * @param mixed $user ユーザーモデル
     * @param string $inputCode 入力されたコード
     * @return bool 検証結果
     */
    public function validateCode($user, string $inputCode): bool
    {
        return $this->helper->validateTwoFactorCode($user, $inputCode);
    }

    /**
     * カスタムメールクラスを使用してコードを生成・送信
     *
     * @param mixed $user ユーザーモデル
     * @param string $mailClass メールクラス名
     * @param int|null $expireMinutes 有効期限（分）
     * @return string 生成されたコード
     */
    public function generateAndSendCodeWithCustomMail($user, string $mailClass, int $expireMinutes = null): string
    {
        $code = $this->helper->generateTwoFactorCode($user, $expireMinutes);

        // カスタムメールクラスでメール送信
        try {
            Mail::to($user->email)->send(new $mailClass($code));
            Log::info("[Email Auth] カスタムメール送信成功: ユーザーID {$user->id}, メールクラス: {$mailClass}");
        } catch (\Exception $e) {
            Log::error("[Email Auth] カスタムメール送信失敗: ユーザーID {$user->id}, エラー: " . $e->getMessage());
            throw $e;
        }

        return $code;
    }

    /**
     * メール認証が利用可能かどうかを確認
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        // メールサーバーの設定状況を確認
        try {
            $mailConfig = config('mail');
            return !empty($mailConfig['default']) && !empty($mailConfig['mailers'][$mailConfig['default']]);
        } catch (\Exception $e) {
            Log::error("[Email Auth] 設定確認エラー: " . $e->getMessage());
            return false;
        }
    }

    /**
     * メール認証の統計情報を取得
     *
     * @return array
     */
    public function getStats(): array
    {
        try {
            $totalTokens = \App\Models\Member2faToken::count();
            $activeTokens = \App\Models\Member2faToken::where('expires_at', '>', now())->count();
            $expiredTokens = $totalTokens - $activeTokens;

            return [
                'total_tokens' => $totalTokens,
                'active_tokens' => $activeTokens,
                'expired_tokens' => $expiredTokens,
                'success_rate' => $totalTokens > 0 ? round(($activeTokens / $totalTokens) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            Log::error("[Email Auth] 統計取得エラー: " . $e->getMessage());
            return [
                'total_tokens' => 0,
                'active_tokens' => 0,
                'expired_tokens' => 0,
                'success_rate' => 0,
            ];
        }
    }

    /**
     * 期限切れトークンをクリーンアップ
     *
     * @return int 削除されたトークン数
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $deleted = \App\Models\Member2faToken::where('expires_at', '<', now())->delete();
            Log::info("[Email Auth] 期限切れトークンクリーンアップ: {$deleted}件削除");
            return $deleted;
        } catch (\Exception $e) {
            Log::error("[Email Auth] クリーンアップエラー: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 特定ユーザーのアクティブなトークンを取得
     *
     * @param mixed $user ユーザーモデル
     * @return \App\Models\Member2faToken|null
     */
    public function getActiveToken($user)
    {
        return \App\Models\Member2faToken::where('member_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * 特定ユーザーのトークンを無効化
     *
     * @param mixed $user ユーザーモデル
     * @return int 削除されたトークン数
     */
    public function revokeUserTokens($user): int
    {
        try {
            $deleted = \App\Models\Member2faToken::where('member_id', $user->id)->delete();
            Log::info("[Email Auth] ユーザートークン無効化: ユーザーID {$user->id}, {$deleted}件削除");
            return $deleted;
        } catch (\Exception $e) {
            Log::error("[Email Auth] トークン無効化エラー: ユーザーID {$user->id}, エラー: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * コード再送信の制限チェック
     *
     * @param mixed $user ユーザーモデル
     * @param int $limitMinutes 制限時間（分）
     * @return bool 再送信可能かどうか
     */
    public function canResendCode($user, int $limitMinutes = 1): bool
    {
        $lastToken = \App\Models\Member2faToken::where('member_id', $user->id)
            ->latest()
            ->first();

        if (!$lastToken) {
            return true;
        }

        $limitTime = now()->subMinutes($limitMinutes);
        return $lastToken->created_at->lessThan($limitTime);
    }

    /**
     * コード再送信（制限チェック付き）
     *
     * @param mixed $user ユーザーモデル
     * @param string $context コンテキスト（admin, user等）
     * @param int $limitMinutes 制限時間（分）
     * @return string|null 生成されたコード（制限に引っかかった場合はnull）
     */
    public function resendCode($user, string $context = 'admin', int $limitMinutes = 1): ?string
    {
        if (!$this->canResendCode($user, $limitMinutes)) {
            Log::warning("[Email Auth] コード再送信制限: ユーザーID {$user->id}");
            return null;
        }

        return $this->generateAndSendCode($user, $context);
    }
}
