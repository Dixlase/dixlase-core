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
use App\Enums\TwoFactorMode;

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
            TwoFactorMode::Disabled->value => false,
            TwoFactorMode::Always->value => true,
            TwoFactorMode::OnlyNewDevice->value => $this->isDifferentEnvironment($member),
            TwoFactorMode::UseProfileSetting->value => $this->checkMemberSetting($member),
            default => false,
        };
    }

    private function checkMemberSetting($member): bool
    {
        $raw = $member->two_factor_mode;

        // すでに Enum ならそのまま、そうでなければ tryFrom で変換
        $mode = $raw instanceof TwoFactorMode
            ? $raw
            : TwoFactorMode::tryFrom((int) $raw);

        return match ($mode) {
            TwoFactorMode::Always => true,
            TwoFactorMode::OnlyNewDevice => $this->isDifferentEnvironment($member),
            default => false,
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
