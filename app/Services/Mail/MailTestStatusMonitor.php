<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
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

namespace App\Services\Mail;

use App\Enums\AuthenticationMode;
use App\Enums\MemberStatus;
use App\Helpers\AdminHelper;
use App\Helpers\TwoFaHelper;
use App\Models\Member;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tells the operator when the mail tests are not complete and who depends on them.
 *
 * Changing a delivery field of the mail settings (an SMTP password rotation
 * included) resets the three mail tests. Until they pass again, no two-factor
 * code is e-mailed at sign-in: an account that still holds a recovery code
 * signs in with one, and an account that has none left is let through on its
 * password alone (see LoginTrait). Nothing else on the site shows that state,
 * so it is reported in two places: the save result of the mail settings, and
 * a system warning banner for as long as it lasts.
 *
 * The banner is a warning, never an error, whatever the recovery code counts
 * say: the operator has one action to take, re-running the tests, and a
 * permanently red banner on a large site would stop being read. It is not
 * sent by e-mail either, because a mail about broken mail is suppressed by
 * the very state it reports.
 */
class MailTestStatusMonitor
{
    /** Menu whose editors can re-run the tests. */
    public const MENU_KEY = 'settings.base.mail';

    /**
     * Whether all three mail tests are recorded as passed.
     *
     * Reads the settings directly rather than through
     * MailServerValidatorService::isMailServerTested(), which logs every
     * negative answer; the banner asks on every admin page.
     */
    public function testsComplete(): bool
    {
        return (bool) SiteSetting::getValue('mail_connection_tested', false)
            && (bool) SiteSetting::getValue('mail_send_tested', false)
            && (bool) SiteSetting::getValue('mail_receive_tested', false);
    }

    /**
     * Active accounts asked for a second step at sign-in, and how many of
     * them have no usable recovery code left.
     *
     * @return array{two_fa: int, without_recovery_codes: int}
     */
    public function affectedAccounts(): array
    {
        $query = $this->twoFaAccountsQuery();

        if ($query === null) {
            return ['two_fa' => 0, 'without_recovery_codes' => 0];
        }

        return [
            'two_fa' => (clone $query)->count(),
            'without_recovery_codes' => (clone $query)
                ->whereDoesntHave('twoFaRecoveryCodes', function (Builder $codes): void {
                    $codes->where('disabled', false)->whereNull('used_at');
                })
                ->count(),
        ];
    }

    /**
     * The warning shown in the save result after the tests were reset.
     */
    public function resetMessage(): string
    {
        $prefix = 'admin/settings/base/mail.tests_reset.';
        $message = __($prefix.'saved');
        $accounts = $this->affectedAccounts();

        if ($accounts['two_fa'] > 0) {
            $message .= ' '.$this->accountsMessage($accounts);
        }

        return $message;
    }

    /**
     * Banners for SystemWarningService.
     *
     * Shown only to members who can edit the mail settings, and only when
     * some account is asked for a second step; a site that does not use
     * mail at all gets no warning.
     *
     * @return array<int, array<string, mixed>>
     */
    public function banners(): array
    {
        try {
            if (! AdminHelper::canEditMenu(self::MENU_KEY) || $this->testsComplete()) {
                return [];
            }

            $accounts = $this->affectedAccounts();
            if ($accounts['two_fa'] === 0) {
                return [];
            }

            $prefix = 'admin/settings/base/mail.tests_reset.';

            return [[
                'level' => 'warning',
                'icon' => 'fas fa-envelope',
                'title' => __($prefix.'banner_title'),
                'message' => $this->accountsMessage($accounts),
                'actions' => [[
                    'label' => __($prefix.'open_settings'),
                    'url' => route('admin.settings.base.mail'),
                    'style' => 'primary',
                    'method' => 'GET',
                ]],
            ]];
        } catch (Throwable $e) {
            // A banner must never break the admin layout.
            Log::warning('MailTestStatusMonitor: banner build failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Active members that TwoFaHelper::isTwoFaEnabled() can ask for a second
     * step, or null when it asks nobody.
     *
     * @return Builder<Member>|null
     */
    private function twoFaAccountsQuery(): ?Builder
    {
        if (! app(TwoFaHelper::class)->isMailConfigured()) {
            // Two-factor authentication is off altogether without mail settings.
            return null;
        }

        $mode = (int) SecuritySetting::getValue('two_fa_mode', '0');
        $query = Member::query()->where('status', MemberStatus::Active->value);

        return match ($mode) {
            AuthenticationMode::Always->value, AuthenticationMode::DifferentDevice->value => $query,
            AuthenticationMode::UseProfileSetting->value => $query->whereIn('two_fa_mode', [
                AuthenticationMode::Always->value,
                AuthenticationMode::DifferentDevice->value,
            ]),
            default => null,
        };
    }

    /**
     * Who is affected, with the accounts that have no recovery code left
     * mentioned only when there are some.
     *
     * @param  array{two_fa: int, without_recovery_codes: int}  $accounts
     */
    private function accountsMessage(array $accounts): string
    {
        $prefix = 'admin/settings/base/mail.tests_reset.';
        $message = __($prefix.'accounts', ['count' => number_format($accounts['two_fa'])]);

        if ($accounts['without_recovery_codes'] > 0) {
            $message .= ' '.__($prefix.'accounts_without_codes', [
                'count' => number_format($accounts['without_recovery_codes']),
            ]);
        }

        return $message;
    }
}
