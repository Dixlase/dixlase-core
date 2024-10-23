<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use App\Http\Controllers\Mypage\Auth\MypageAuthenticatedSessionController;
use App\Http\Controllers\Mypage\Auth\MypageConfirmablePasswordController;
use App\Http\Controllers\Mypage\Auth\MypageEmailVerificationNotificationController;
use App\Http\Controllers\Mypage\Auth\MypageEmailVerificationPromptController;
use App\Http\Controllers\Mypage\Auth\MypageNewPasswordController;
use App\Http\Controllers\Mypage\Auth\MypagePasswordController;
use App\Http\Controllers\Mypage\Auth\MypagePasswordResetLinkController;
use App\Http\Controllers\Mypage\Auth\MypageVerifyEmailController;
use App\Http\Controllers\Mypage\Profile\MypageProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


Route::prefix('mypage')->name('mypage.')->group(function () {
    Route::get('/', function () {
        $user = Auth::guard('web')->user();
        if ($user) {
            return redirect()->route('mypage.dashboard');
        } else {
            return redirect()->route('mypage.login');
        }
    });

    Route::get('/dashboard', function () {
        return view('mypage.dashboard');
    })->middleware(['auth:web', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [MypageProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [MypageProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [MypageProfileController::class, 'destroy'])->name('profile.destroy');
    });

    Route::middleware('guest')->group(function () {

        Route::get('login', [MypageAuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('login', [MypageAuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [MypagePasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [MypagePasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('reset-password/{token}', [MypageNewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post('reset-password', [MypageNewPasswordController::class, 'store'])
            ->name('password.store');
    });

    Route::middleware('auth:web')->group(function () {
        Route::get('verify-email', MypageEmailVerificationPromptController::class)
            ->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', MypageVerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verification-notification', [MypageEmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('confirm-password', [MypageConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        Route::post('confirm-password', [MypageConfirmablePasswordController::class, 'store']);

        Route::put('password', [MypagePasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [MypageAuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});
