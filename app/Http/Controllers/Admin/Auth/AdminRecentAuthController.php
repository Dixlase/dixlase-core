<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Facades\Audit;
use App\Http\Middleware\RequireRecentAuthentication;
use App\Models\AuditLog;
use App\Models\Member;
use App\Services\Security\RecentAuthentication;
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use Throwable;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * The screen a member confirms their identity on before a sensitive admin
 * operation (dixlase-core#675). See RequireRecentAuthentication.
 *
 * The member confirms with their password or with one of their passkeys.
 * After `security.recent_auth.max_attempts` failures in a row the session
 * is ended: a session that cannot answer for its own member is treated as
 * one that is not theirs.
 */
class AdminRecentAuthController extends Controller
{
    public function __construct(
        private readonly RecentAuthentication $recent,
        private readonly TwoFaPasskeyService $passkeys,
    ) {}

    public function show(Request $request): View
    {
        $member = $this->member($request);

        return view('admin.auth.recent-auth', [
            'hasPassword' => $this->passwordHash($member) !== null,
            'canUsePasskey' => $this->canUsePasskey($member),
            'minutes' => RecentAuthentication::defaultMinutes(),
            'cancelUrl' => $this->cancelUrl($request),
        ]);
    }

    public function confirmPassword(Request $request): RedirectResponse
    {
        $member = $this->member($request);

        $request->validate([
            'password' => ['required', 'string', 'max:1024'],
        ]);

        $hash = $this->passwordHash($member);
        $valid = $hash !== null && Hash::check((string) $request->input('password'), $hash);

        if (! $valid) {
            if ($this->recordFailure($request, $member, 'password')) {
                return $this->endSession($request);
            }

            return back()->withErrors(['password' => __('admin/recent-auth.password_incorrect')]);
        }

        return $this->confirmed($request, $member, 'password');
    }

    public function passkeyOptions(Request $request): JsonResponse
    {
        $member = $this->member($request);

        if (! $this->canUsePasskey($member)) {
            return response()->json(['error' => __('admin/recent-auth.passkey_unavailable')], 422);
        }

        try {
            $challenge = $this->passkeys->generateLoginChallenge($member);
        } catch (Throwable $e) {
            Log::error('[RecentAuth] Passkey challenge failed', ['member_id' => $member->getKey(), 'error' => $e->getMessage()]);

            return response()->json(['error' => __('common.error_occurred')], 500);
        }

        return response()->json(['success' => true, 'challenge' => $challenge['publicKey']]);
    }

    public function passkeyVerify(Request $request): JsonResponse
    {
        $member = $this->member($request);

        // verifyLoginChallenge() checks the challenge it issued for this
        // member, so an assertion from someone else's passkey fails here.
        if (! $this->canUsePasskey($member) || ! $this->passkeys->verifyLoginChallenge($member, $request->all())) {
            if ($this->recordFailure($request, $member, 'passkey')) {
                $this->endSession($request);

                return response()->json(['error' => __('admin/recent-auth.too_many_attempts'), 'redirect' => route('admin.login')], 423);
            }

            return response()->json(['error' => __('admin/recent-auth.passkey_failed')], 422);
        }

        $redirect = $this->confirmed($request, $member, 'passkey');

        return response()->json(['success' => true, 'redirect' => $redirect->getTargetUrl()]);
    }

    private function confirmed(Request $request, Member $member, string $factor): RedirectResponse
    {
        RateLimiter::clear($this->throttleKey($member));
        $this->recent->confirm($member->getKey());

        Audit::log([
            'category' => AuditLog::getCategoryForAction(AuditLog::ACTION_STEP_UP_AUTH_COMPLETED),
            'action' => AuditLog::ACTION_STEP_UP_AUTH_COMPLETED,
            'severity' => AuditLog::getDefaultSeverity(AuditLog::ACTION_STEP_UP_AUTH_COMPLETED),
            'actor' => $member,
            'context' => ['factor' => $factor],
        ]);

        $intended = $this->recent->pullIntended();
        $url = $intended !== null && RequireRecentAuthentication::isOwnUrl($request, $intended['url'])
            ? $intended['url']
            : route('admin.dashboard');

        $message = $intended !== null && $intended['resubmit']
            ? __('admin/recent-auth.confirmed_resubmit', ['minutes' => RecentAuthentication::defaultMinutes()])
            : __('admin/recent-auth.confirmed', ['minutes' => RecentAuthentication::defaultMinutes()]);

        return redirect()->to($url)->with('success', $message);
    }

    /**
     * Count a failed confirmation. True when the limit is reached and the
     * session has to end.
     */
    private function recordFailure(Request $request, Member $member, string $factor): bool
    {
        $key = $this->throttleKey($member);
        RateLimiter::hit($key, RecentAuthentication::defaultMinutes() * 60);
        $attempts = RateLimiter::attempts($key);
        $limit = max(1, (int) config('security.recent_auth.max_attempts', 5));
        $exhausted = $attempts >= $limit;

        Audit::log([
            'category' => AuditLog::getCategoryForAction(AuditLog::ACTION_STEP_UP_AUTH_FAILED),
            'action' => AuditLog::ACTION_STEP_UP_AUTH_FAILED,
            'severity' => AuditLog::getDefaultSeverity(AuditLog::ACTION_STEP_UP_AUTH_FAILED),
            'actor' => $member,
            'context' => [
                'factor' => $factor,
                'attempts' => $attempts,
                'session_ended' => $exhausted,
            ],
        ]);

        if ($exhausted) {
            RateLimiter::clear($key);
        }

        return $exhausted;
    }

    private function endSession(Request $request): RedirectResponse
    {
        $this->recent->forget();
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('info', __('admin/recent-auth.too_many_attempts'));
    }

    private function passwordHash(Member $member): ?string
    {
        $hash = $member->getAuthPassword();

        return is_string($hash) && $hash !== '' ? $hash : null;
    }

    private function canUsePasskey(Member $member): bool
    {
        return $this->passkeys->isAvailable() && $this->passkeys->hasCredentials($member);
    }

    /**
     * Back to where the member came from, unless that is the page that
     * sent them here in the first place.
     */
    private function cancelUrl(Request $request): string
    {
        $previous = url()->previous(route('admin.dashboard'));

        return RequireRecentAuthentication::isOwnUrl($request, $previous)
            && $previous !== $request->fullUrl()
            ? $previous
            : route('admin.dashboard');
    }

    private function throttleKey(Member $member): string
    {
        return 'admin-recent-auth:'.$member->getKey();
    }

    private function member(Request $request): Member
    {
        $member = $request->user('member');
        abort_unless($member instanceof Member, 403);

        return $member;
    }
}
