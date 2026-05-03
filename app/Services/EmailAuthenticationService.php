<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Services;

use App\Mail\TwoFaCodeMail;
use App\Services\TwoFa\TwoFaCodeService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailAuthenticationService
{
    protected TwoFaCodeService $codeService;

    public function __construct(TwoFaCodeService $codeService)
    {
        $this->codeService = $codeService;
    }

    /**
     * Generate and send email authentication code
     *
     * @param  mixed  $user  User model
     * @param  string  $context  Context (admin, user, etc.)
     * @param  int|null  $expireMinutes  Expiration time (minutes)
     * @return string Generated code
     */
    public function generateAndSendCode($user, string $context = 'admin', ?int $expireMinutes = null): string
    {
        return $this->codeService->generateAndSend(
            $user,
            TwoFaCodeMail::class,
            $expireMinutes,
            $context
        );
    }

    /**
     * Verify email authentication code
     *
     * @param  mixed  $user  User model
     * @param  string  $inputCode  Entered code
     * @return bool Verification result
     */
    public function validateCode($user, string $inputCode): bool
    {
        return $this->codeService->validate($user, $inputCode);
    }

    /**
     * Generate and send code using custom mail class
     *
     * @param  mixed  $user  User model
     * @param  string  $mailClass  Mail class name
     * @param  int|null  $expireMinutes  Expiration time (minutes)
     * @return string Generated code
     */
    public function generateAndSendCodeWithCustomMail($user, string $mailClass, ?int $expireMinutes = null): string
    {
        $code = $this->codeService->generate($user, $expireMinutes);

        // Send email with custom mail class
        try {
            Mail::to($user->email)->send(new $mailClass($code));
            Log::info("[Email Auth] Custom email sent successfully: User ID {$user->id}, Mail class: {$mailClass}");
        } catch (\Exception $e) {
            Log::error("[Email Auth] Custom email send failed: User ID {$user->id}, Error: ".$e->getMessage());
            throw $e;
        }

        return $code;
    }

    /**
     * Check if email authentication is available
     */
    public function isAvailable(): bool
    {
        // Check mail server configuration status
        try {
            $mailConfig = config('mail');

            return ! empty($mailConfig['default']) && ! empty($mailConfig['mailers'][$mailConfig['default']]);
        } catch (\Exception $e) {
            Log::error('[Email Auth] Configuration check error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get email authentication statistics
     */
    public function getStats(): array
    {
        try {
            $totalTokens = \App\Models\MemberTwoFaToken::count();
            $activeTokens = \App\Models\MemberTwoFaToken::where('expires_at', '>', now())->count();
            $expiredTokens = $totalTokens - $activeTokens;

            return [
                'total_tokens' => $totalTokens,
                'active_tokens' => $activeTokens,
                'expired_tokens' => $expiredTokens,
                'success_rate' => $totalTokens > 0 ? round(($activeTokens / $totalTokens) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            Log::error('[Email Auth] Statistics retrieval error: '.$e->getMessage());

            return [
                'total_tokens' => 0,
                'active_tokens' => 0,
                'expired_tokens' => 0,
                'success_rate' => 0,
            ];
        }
    }

    /**
     * Clean up expired tokens
     *
     * @return int Number of deleted tokens
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $deleted = \App\Models\MemberTwoFaToken::where('expires_at', '<', now())->delete();
            Log::info("[Email Auth] Expired token cleanup: {$deleted} deleted");

            return $deleted;
        } catch (\Exception $e) {
            Log::error('[Email Auth] Cleanup error: '.$e->getMessage());

            return 0;
        }
    }

    /**
     * Get active tokens for specific user
     *
     * @param  mixed  $user  User model
     * @return \App\Models\MemberTwoFaToken|null
     */
    public function getActiveToken($user)
    {
        return \App\Models\MemberTwoFaToken::where('member_id', $user->id)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Invalidate tokens for specific user
     *
     * @param  mixed  $user  User model
     * @return int Number of deleted tokens
     */
    public function revokeUserTokens($user): int
    {
        try {
            $deleted = \App\Models\MemberTwoFaToken::where('member_id', $user->id)->delete();
            Log::info("[Email Auth] User tokens invalidated: User ID {$user->id}, {$deleted} deleted");

            return $deleted;
        } catch (\Exception $e) {
            Log::error("[Email Auth] Token invalidation error: User ID {$user->id}, Error: ".$e->getMessage());

            return 0;
        }
    }

    /**
     * Check code resend rate limit
     *
     * @param  mixed  $user  User model
     * @param  int  $limitMinutes  Rate limit time (minutes)
     * @return bool Whether resending is allowed
     */
    public function canResendCode($user, int $limitMinutes = 1): bool
    {
        $lastToken = \App\Models\MemberTwoFaToken::where('member_id', $user->id)
            ->latest()
            ->first();

        if (! $lastToken) {
            return true;
        }

        $limitTime = now()->subMinutes($limitMinutes);

        return $lastToken->created_at->lessThan($limitTime);
    }

    /**
     * Resend code (with rate limit check)
     *
     * @param  mixed  $user  User model
     * @param  string  $context  Context (admin, user, etc.)
     * @param  int  $limitMinutes  Rate limit time (minutes)
     * @return string|null Generated code (null if rate limit exceeded)
     */
    public function resendCode($user, string $context = 'admin', int $limitMinutes = 1): ?string
    {
        if (! $this->canResendCode($user, $limitMinutes)) {
            Log::warning("[Email Auth] Code resend rate limited: User ID {$user->id}");

            return null;
        }

        return $this->generateAndSendCode($user, $context);
    }
}
