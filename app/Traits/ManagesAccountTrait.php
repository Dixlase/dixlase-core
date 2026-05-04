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

namespace App\Traits;

use App\Services\MailServerValidatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Common account management logic
 * Available for both members and users
 */
trait ManagesAccountTrait
{
    /**
     * Verification email sending process (common)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Member or user model
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $failedMessageKey  Translation key for failure message
     * @param  string  $redirectRouteName  Redirect destination route name
     * @param  string  $mailServerNotTestedKey  Translation key for mail server untested message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendVerificationEmailToModel(
        $model,
        string $successMessageKey,
        string $failedMessageKey,
        string $redirectRouteName,
        string $mailServerNotTestedKey = 'admin/members/form.mail_server_not_tested'
    ) {
        try {
            if (! MailServerValidatorService::isMailServerTested()) {
                return response()->json([
                    'success' => false,
                    'message' => __($mailServerNotTestedKey),
                ], 400);
            }

            $model->email_verified_at = null;
            $model->save();

            // Use appropriate session table and column based on model type
            $isMember = $model instanceof \App\Models\Member;
            $sessionTable = $isMember ? 'members_sessions' : 'users_sessions';
            $idColumn = $isMember ? 'member_id' : 'user_id';

            if (DB::getSchemaBuilder()->hasTable($sessionTable)) {
                DB::table($sessionTable)
                    ->where($idColumn, $model->id)
                    ->delete();
            }

            $model->sendEmailVerificationNotification('resend');

            $message = __($successMessageKey);
            $redirectUrl = route($redirectRouteName, [$this->getModelRouteParameterName() => $model->id]);

            session()->flash('success', $message);

            return response()->json([
                'success' => true,
                'redirect' => $redirectUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __($failedMessageKey),
            ], 500);
        }
    }

    /**
     * Force logout process (common)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Member or user model
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $redirectRouteName  Redirect destination route name
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function forceLogoutModel(
        $model,
        string $successMessageKey,
        string $redirectRouteName
    ) {
        // Use appropriate session table and column based on model type
        $isMember = $model instanceof \App\Models\Member;
        $sessionTable = $isMember ? 'members_sessions' : 'users_sessions';
        $idColumn = $isMember ? 'member_id' : 'user_id';

        if (DB::getSchemaBuilder()->hasTable($sessionTable)) {
            DB::table($sessionTable)
                ->where($idColumn, $model->id)
                ->delete();
        }

        return redirect()->route($redirectRouteName, [$this->getModelRouteParameterName() => $model->id])
            ->with('success', __($successMessageKey));
    }

    /**
     * Two-FA lockout release process (common)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Member or user model
     * @param  string  $twoFaAttemptModelClass  2FA attempt model class name
     * @param  string  $loginAttemptModelClass  Login attempt model class name
     * @param  string  $modelIdColumn  Model ID column name ('member_id' or 'user_id')
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $redirectRouteName  Redirect destination route name
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function unlockTwoFaForModel(
        $model,
        string $twoFaAttemptModelClass,
        string $loginAttemptModelClass,
        string $modelIdColumn,
        string $successMessageKey,
        string $redirectRouteName
    ) {
        $twoFaAttemptModelClass::where($modelIdColumn, $model->id)->delete();
        // Delete only failed attempt records (keep successful records for statistics)
        $loginAttemptModelClass::where('identifier', $model->email)
            ->where('successful', false)
            ->delete();

        return redirect()->route($redirectRouteName, [$this->getModelRouteParameterName() => $model->id])
            ->with('success', __($successMessageKey));
    }

    /**
     * Process email verification status (common)
     *
     * @param  array  &$validated  Validated data (passed by reference)
     * @param  Request  $request  Request
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     */
    protected function processEmailVerificationStatus(array &$validated, Request $request, $model): void
    {
        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailVerified = (string) $request->input('email_verified', $isMailServerTested ? null : '1');
        $wasVerified = $model->hasVerifiedEmail();
        $emailChanged = $request->input('email') !== $model->email;

        if (! $isMailServerTested) {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '1') {
            $validated['email_verified_at'] = now();
        } elseif ($emailVerified === '0') {
            $validated['email_verified_at'] = null;
        } elseif ($emailChanged && $wasVerified) {
            $validated['email_verified_at'] = null;
        }

        unset($validated['email_verified']);
    }

    /**
     * Determine and send verification email on email address change (common)
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model  Model
     * @param  Request  $request  Request
     * @param  bool  $wasVerified  Verification status before change
     * @param  string  $successMessageKey  Translation key for success message
     * @param  string  $failedMessageKey  Translation key for failure message
     * @param  string  $defaultMessageKey  Translation key for default message
     * @param  string  $context  Notification context (e.g. 'email_change')
     * @return string Translation key for message
     */
    protected function sendEmailVerificationIfNeeded(
        $model,
        Request $request,
        bool $wasVerified,
        string $successMessageKey,
        string $failedMessageKey,
        string $defaultMessageKey,
        string $context = 'email_change'
    ): string {
        $isMailServerTested = MailServerValidatorService::isMailServerTested();
        $emailChanged = $request->input('email') !== $model->getOriginal('email');
        $shouldSendEmail = $isMailServerTested && $emailChanged && $wasVerified;

        if ($shouldSendEmail && ! $model->hasVerifiedEmail()) {
            try {
                $model->sendEmailVerificationNotification($context);

                return $successMessageKey;
            } catch (\Exception $e) {
                Log::error('Failed to send verification email', [
                    'model_id' => $model->id,
                    'error' => $e->getMessage(),
                ]);

                return $failedMessageKey;
            }
        }

        return $defaultMessageKey;
    }

    /**
     * Get the route parameter name for the model
     * Implement in subclass
     */
    abstract protected function getModelRouteParameterName(): string;
}
