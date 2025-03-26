<?php


namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\MembersTwoFactorLoginCodeMail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Models\MembersTwoFactorToken;
use Illuminate\Support\Facades\Hash;
use App\Models\MemberSetting;
use App\Enums\MembersTwoFactorMode;
use App\Enums\GlobalTwoFactorMode;

class MembersTwoFactorService
{
    public function generate($user): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        MembersTwoFactorToken::where('member_id', $user->id)->delete(); // 古いコードを削除

        MembersTwoFactorToken::create([
            'member_id' => $user->id,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes((int) config('app.two_factor.email_code_expire')),
        ]);

        Mail::to($user->email)->send(new MembersTwoFactorLoginCodeMail($code));

        Log::info("[2FA] コード送信: {$code} to {$user->email}");

        return $code;
    }

    public function validate($user, string $inputCode): bool
    {
        $token = MembersTwoFactorToken::where('member_id', $user->id)->latest()->first();

        if (!$token || now()->greaterThan($token->expires_at) || !Hash::check($inputCode, $token->code)) {
            return false;
        }

        $token->delete(); // 使い切りコード

        return true;
    }

    public function has($member)
    {
        $force = (int) MemberSetting::getValue('force_2fa', 0);

        return match ($force) {
            GlobalTwoFactorMode::Disabled->value => false,
            GlobalTwoFactorMode::Always->value => true,
            GlobalTwoFactorMode::Smart->value => $this->isDifferentEnvironment($member),
            default => match ($member->two_factor_mode) {
                MembersTwoFactorMode::Always->value => true,
                MembersTwoFactorMode::Smart->value => $this->isDifferentEnvironment($member),
                default => false,
            }
        };
    }

    public function isDifferentEnvironment($member): bool
    {

        $currentIp = request()->ip();
        $currentUa = request()->userAgent();

        $lastIp = $member->last_login_ip;
        $lastUa = $member->last_login_ua;

        return $currentIp !== $lastIp || $currentUa !== $lastUa;
    }
}
