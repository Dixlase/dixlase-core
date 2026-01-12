<?php

namespace App\Services\TwoFa;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\MemberTwoFaToken;
use Carbon\Carbon;

class TwoFaCodeService
{
    /**
     * 認証コードを生成してデータベースに保存
     * 
     * @param mixed $user ユーザーモデル（Member または DixlaseUsersUser）
     * @param int|null $expireMinutes 有効期限（分）
     * @return string 生成されたコード（平文）
     */
    public function generate($user, int $expireMinutes = null): string
    {
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // デフォルトの有効期限設定
        if ($expireMinutes === null) {
            $expireMinutes = (int) \App\Models\MemberSetting::getValue('two_fa_expire_minutes', config('two-fa.code_expiration', 5));
        }

        // 古いコードを削除
        MemberTwoFaToken::where('member_id', $user->id)->delete();

        // 新しいコードを保存
        MemberTwoFaToken::create([
            'member_id' => $user->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes($expireMinutes),
        ]);

        Log::info('[2FA Code] Generated', [
            'user_id' => $user->id,
            'expires_in_minutes' => $expireMinutes,
        ]);

        return $code;
    }

    /**
     * 認証コードを生成してメール送信
     * 
     * @param mixed $user ユーザーモデル（Member または DixlaseUsersUser）
     * @param string $mailClass メールクラス名
     * @param int|null $expireMinutes 有効期限（分）
     * @param string $context コンテキスト（admin, user等）
     * @return string 生成されたコード
     * @throws \Exception メール設定が未完了またはメール送信に失敗した場合
     */
    public function generateAndSend($user, string $mailClass, int $expireMinutes = null, string $context = 'admin'): string
    {
        // メール設定チェック（TwoFaHelperを使用）
        $helper = app(\App\Helpers\TwoFaHelper::class);
        if (!$helper->isMailConfigured()) {
            Log::error('[2FA Code] Mail not configured');
            throw new \Exception(__('admin/profile.two_fa.mail_not_configured'));
        }
        
        $code = $this->generate($user, $expireMinutes);

        // メール送信
        try {
            if ($mailClass === \App\Mail\TwoFaCodeMail::class) {
                // 汎用メールクラスの場合はコンテキストを渡す
                Mail::to($user->email)->send(new $mailClass($code, $context));
            } else {
                // 既存のメールクラスの場合は従来通り
                Mail::to($user->email)->send(new $mailClass($code));
            }
            
            Log::info('[2FA Code] Sent successfully', [
                'user_id' => $user->id,
                'context' => $context,
            ]);
        } catch (\Exception $e) {
            Log::error('[2FA Code] Failed to send', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $code;
    }

    /**
     * 認証コードを検証
     * 
     * @param mixed $user ユーザーモデル（Member または DixlaseUsersUser）
     * @param string $inputCode 入力されたコード
     * @return bool 検証結果
     */
    public function validate($user, string $inputCode): bool
    {
        $token = MemberTwoFaToken::where('member_id', $user->id)->latest()->first();

        if (!$token) {
            Log::warning('[2FA Code] No token found', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // 有効期限チェック
        if (now()->greaterThan($token->expires_at)) {
            Log::warning('[2FA Code] Token expired', [
                'user_id' => $user->id,
                'expired_at' => $token->expires_at,
            ]);
            return false;
        }

        // コード検証
        if (!Hash::check($inputCode, $token->code)) {
            Log::warning('[2FA Code] Invalid code', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        // 使い切りコードなので削除
        $token->delete();

        Log::info('[2FA Code] Validated successfully', [
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * 有効なコードが存在するかチェック
     * 
     * @param mixed $user ユーザーモデル（Member または DixlaseUsersUser）
     * @return bool
     */
    public function hasValidCode($user): bool
    {
        return MemberTwoFaToken::where('member_id', $user->id)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * コードの残り有効時間（分）を取得
     * 
     * @param mixed $user ユーザーモデル（Member または DixlaseUsersUser）
     * @return int|null 残り時間（分）、コードがない場合はnull
     */
    public function getRemainingTime($user): ?int
    {
        $token = MemberTwoFaToken::where('member_id', $user->id)->latest()->first();

        if (!$token || now()->greaterThan($token->expires_at)) {
            return null;
        }

        return now()->diffInMinutes($token->expires_at, false);
    }

    /**
     * 全てのコードを削除
     * 
     * @param mixed $user ユーザーモデル（Member または DixlaseUsersUser）
     * @return int 削除されたコード数
     */
    public function revokeAll($user): int
    {
        $count = MemberTwoFaToken::where('member_id', $user->id)->count();
        MemberTwoFaToken::where('member_id', $user->id)->delete();

        Log::info('[2FA Code] All codes revoked', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * 期限切れコードをクリーンアップ
     * 
     * @return int 削除されたコード数
     */
    public function cleanupExpired(): int
    {
        $count = MemberTwoFaToken::where('expires_at', '<', now())->delete();
        
        Log::info('[2FA Code] Expired codes cleaned up', [
            'count' => $count,
        ]);
        
        return $count;
    }
}
