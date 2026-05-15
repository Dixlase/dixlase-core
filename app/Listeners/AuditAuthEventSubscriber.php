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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Listeners;

use App\Facades\Audit;
use App\Models\AuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Events\Dispatcher;

/**
 * Audit logging for authentication events
 */
class AuditAuthEventSubscriber
{
    /**
     * Login successful
     */
    public function handleLogin(Login $event): void
    {
        Audit::logAuth(AuditLog::ACTION_LOGIN, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.logged_in'),
                'guard' => $event->guard,
                'remember' => $event->remember,
            ],
        ]);
    }

    /**
     * Login failed
     */
    public function handleFailed(Failed $event): void
    {
        Audit::logAuth(AuditLog::ACTION_LOGIN_FAILED, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_FAILURE,
            'severity' => AuditLog::SEVERITY_WARNING,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.login_failed'),
                'guard' => $event->guard,
                'credentials' => array_keys($event->credentials),
            ],
        ]);
    }

    /**
     * Logout
     */
    public function handleLogout(Logout $event): void
    {
        Audit::logAuth(AuditLog::ACTION_LOGOUT, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.logged_out'),
                'guard' => $event->guard,
            ],
        ]);
    }

    /**
     * Lockout
     */
    public function handleLockout(Lockout $event): void
    {
        $log = Audit::logSecurity(AuditLog::ACTION_LOCKOUT_TRIGGERED, [
            'outcome' => AuditLog::OUTCOME_DENIED,
            'severity' => AuditLog::SEVERITY_CRITICAL,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.locked_out_excessive_attempts'),
                'ip' => $event->request->ip(),
            ],
        ]);

        event(new \App\Events\SecurityAlertEvent('lockout', [
            'ip' => $event->request->ip(),
            'identifier' => $event->request->input('email') ?? $event->request->input('login'),
        ], $log));
    }

    /**
     * Password reset
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        Audit::logAccount(AuditLog::ACTION_PASSWORD_RESET, [
            'actor' => $event->user,
            'target' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.password_reset'),
            ],
        ]);
    }

    /**
     * User registration
     */
    public function handleRegistered(Registered $event): void
    {
        Audit::logAccount(AuditLog::ACTION_MEMBER_CREATED, [
            'target' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.new_user_registered'),
            ],
        ]);
    }

    /**
     * Logout from other devices
     */
    public function handleOtherDeviceLogout(OtherDeviceLogout $event): void
    {
        Audit::logAuth(AuditLog::ACTION_FORCED_LOGOUT, [
            'actor' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'severity' => AuditLog::SEVERITY_NOTICE,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.logged_out_other_devices'),
                'guard' => $event->guard,
            ],
        ]);
    }

    /**
     * Email verification completed
     */
    public function handleVerified(Verified $event): void
    {
        Audit::logAccount('email_verified', [
            'actor' => $event->user,
            'target' => $event->user,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'context' => [
                'message' => __('listeners/audit_auth_event_subscriber.email_verified'),
            ],
        ]);
    }

    /**
     * Register event subscriptions
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            Lockout::class => 'handleLockout',
            PasswordReset::class => 'handlePasswordReset',
            Registered::class => 'handleRegistered',
            OtherDeviceLogout::class => 'handleOtherDeviceLogout',
            Verified::class => 'handleVerified',
        ];
    }
}
