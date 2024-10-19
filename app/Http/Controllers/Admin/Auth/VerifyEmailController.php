<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user('admins')->hasVerifiedEmail()) {
            return redirect()->intended('/admin'.RouteServiceProvider::HOME.'?verified=1');
        }

        if ($request->user('admins')->markEmailAsVerified()) {
            event(new Verified($request->user('admins')));
        }

        return redirect()->intended(route('admin.dashboard', absolute: false).'?verified=1');
    }
}
