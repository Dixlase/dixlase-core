<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Services\TwoFa;

use App\Models\MemberTwoFaToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * @internal Core only. Do not reference from plugins/themes
 */
class TwoFaCodeService
{
    /**
     * Generate authentication code and save to database
     *
     * @param  mixed  $user  User model (Member or DixlaseUsersUser)
     * @param  int|null  $expireMinutes  Expiration time (minutes)
     * @return string Generated code (plain text)
     */
    public function generate($user, ?int $expireMinutes = null): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Default expiration settings (from security settings)
        if ($expireMinutes === null) {
            $expireMinutes = (int) \App\Models\SecuritySetting::getValue('two_fa_expire_minutes', config('two-fa.code_expiration', 5));
        }

        // Delete old codes
        MemberTwoFaToken::where('member_id', $user->id)->delete();

        // Save new code
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
     * Generate authentication code and send email
     *
     * @param  mixed  $user  User model (Member or DixlaseUsersUser)
     * @param  string  $mailClass  Mail class name
     * @param  int|null  $expireMinutes  Expiration time (minutes)
     * @param  string  $context  Context (admin, user, etc.)
     * @return string Generated code
     *
     * @throws \Exception When email settings are incomplete or email sending fails
     */
    public function generateAndSend($user, string $mailClass, ?int $expireMinutes = null, string $context = 'admin'): string
    {
        // Check email settings (using TwoFaHelper)
        $helper = app(\App\Helpers\TwoFaHelper::class);
        if (! $helper->isMailConfigured()) {
            Log::error('[2FA Code] Mail not configured');
            throw new \Exception(__('admin/profile/common.two_fa.mail_not_configured'));
        }

        $code = $this->generate($user, $expireMinutes);

        // Send email
        try {
            if ($mailClass === \App\Mail\TwoFaCodeMail::class) {
                // Pass context for generic mail class
                Mail::to($user->email)->send(new $mailClass($code, $context));
            } else {
                // For existing mail class, use conventional method
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
     * Verify authentication code
     *
     * @param  mixed  $user  User model (Member or DixlaseUsersUser)
     * @param  string  $inputCode  Input code
     * @return bool Verification result
     */
    public function validate($user, string $inputCode): bool
    {
        $token = MemberTwoFaToken::where('member_id', $user->id)->latest()->first();

        if (! $token) {
            Log::warning('[2FA Code] No token found', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        // Check expiration
        if (now()->greaterThan($token->expires_at)) {
            Log::warning('[2FA Code] Token expired', [
                'user_id' => $user->id,
                'expired_at' => $token->expires_at,
            ]);

            return false;
        }

        // Verify code
        if (! Hash::check($inputCode, $token->code)) {
            Log::warning('[2FA Code] Invalid code', [
                'user_id' => $user->id,
            ]);

            return false;
        }

        // Delete as it's a one-time use code
        $token->delete();

        Log::info('[2FA Code] Validated successfully', [
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Check if a valid code exists
     *
     * @param  mixed  $user  User model (Member or DixlaseUsersUser)
     */
    public function hasValidCode($user): bool
    {
        return MemberTwoFaToken::where('member_id', $user->id)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get remaining validity time (minutes) of the code
     *
     * @param  mixed  $user  User model (Member or DixlaseUsersUser)
     * @return int|null Remaining time (minutes), null if no code exists
     */
    public function getRemainingTime($user): ?int
    {
        $token = MemberTwoFaToken::where('member_id', $user->id)->latest()->first();

        if (! $token || now()->greaterThan($token->expires_at)) {
            return null;
        }

        return now()->diffInMinutes($token->expires_at, false);
    }

    /**
     * Delete all codes
     *
     * @param  mixed  $user  User model (Member or DixlaseUsersUser)
     * @return int Number of deleted codes
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
     * Clean up expired codes
     *
     * @return int Number of deleted codes
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
