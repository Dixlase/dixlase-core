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

declare(strict_types=1);

namespace App\Services\Privacy\Providers;

use App\Contracts\PluginIntegration\PrivacyDataProviderInterface;
use App\DTO\PluginPrivacy\UserDataDeletionDTO;
use App\DTO\PluginPrivacy\UserDataExportDTO;
use App\Enums\PluginPrivacy\DeletionMode;
use App\Models\Member;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Privacy data provider for core admin Member data.
 *
 * Handles every personal-data-bearing core table that is keyed by
 * members.id (or by the member's email for password reset tokens),
 * plus the two site-scoped tables that record per-member activity:
 * audit_logs and security_events.
 *
 * Mode semantics:
 *   - HardDelete : Physically delete rows from every global child table
 *                  in foreign-key-safe order, then delete the members
 *                  row. Audit-trail tables (audit_logs, security_events)
 *                  are NEVER deleted; their PII fields are anonymized in
 *                  place to preserve the hash chain and aggregate
 *                  reporting.
 *   - SoftDelete : Set members.deleted_at; leave related child rows in
 *                  place. Audit-trail PII is anonymized.
 *   - Anonymize  : Keep every row; replace identifying fields with
 *                  HMAC-SHA256 hashes derived from app.key. The account
 *                  password is replaced with a non-loginable random
 *                  string and the status is forced to suspended.
 *
 * Site scoping:
 *   - $siteId === null : Includes every global table plus all rows in
 *                        audit_logs / security_events (across all sites,
 *                        including network-wide rows where site_id IS NULL).
 *   - $siteId !== null : Operates only on rows in audit_logs and
 *                        security_events whose site_id matches the
 *                        requested site. Global tables (members,
 *                        members_*, sessions, webauthn_credentials) are
 *                        NOT touched, because the member account itself
 *                        is network-wide. A warning is added to the
 *                        export DTO so operators understand the scope.
 */
class CoreMemberPrivacyProvider implements PrivacyDataProviderInterface
{
    private const PROVIDER_KEY = 'core-members';

    private const ANONYMIZE_SALT_NAMESPACE = 'dixlase-privacy-anonymize';

    /**
     * Tables holding global per-member data.
     * Ordered so that child rows can be deleted before the members row
     * during HardDelete (members itself is handled last).
     *
     * @var array<int, array{table: string, fk: string, pii: array<int, string>}>
     */
    private const GLOBAL_TABLES = [
        ['table' => 'members_login_attempts',       'fk' => 'member_id', 'pii' => ['identifier', 'ip_address', 'user_agent', 'device_fingerprint']],
        ['table' => 'members_sessions',             'fk' => 'member_id', 'pii' => ['ip_address', 'user_agent']],
        ['table' => 'members_trusted_devices',      'fk' => 'member_id', 'pii' => ['device_name', 'ip_address', 'user_agent', 'user_agent_hash', 'first_ip', 'last_ip']],
        ['table' => 'members_two_fa_attempts',      'fk' => 'member_id', 'pii' => ['ip_address', 'user_agent']],
        ['table' => 'members_two_fa_recovery_codes', 'fk' => 'member_id', 'pii' => []],
        ['table' => 'members_two_fa_tokens',        'fk' => 'member_id', 'pii' => []],
        ['table' => 'webauthn_credentials',         'fk' => 'member_id', 'pii' => ['alias', 'name']],
        ['table' => 'sessions',                     'fk' => 'user_id',   'pii' => ['ip_address', 'user_agent']],
    ];

    public function getPluginSlug(): string
    {
        return self::PROVIDER_KEY;
    }

    public function isCapabilityAvailable(): bool
    {
        return true;
    }

    public function privacyProviderKey(): string
    {
        return self::PROVIDER_KEY;
    }

    public function privacyDataDescription(): array
    {
        return [
            'en' => 'Core admin member account, sessions, login attempts, two-factor records, WebAuthn credentials, and per-member audit / security event entries.',
            'ja' => __('services/privacy/providers/core_member_privacy_provider.member_account_session_auth_data'),
        ];
    }

    public function exportUserData(int $userId, ?int $siteId = null): UserDataExportDTO
    {
        $data = [];
        $warnings = [];

        if ($siteId === null) {
            $data['member'] = $this->fetchMember($userId);
            foreach (self::GLOBAL_TABLES as $spec) {
                $data[$spec['table']] = $this->fetchByFk($spec['table'], $spec['fk'], $userId);
            }
            $data['members_password_reset_tokens'] = $this->fetchPasswordResetTokens($userId);
        } else {
            $warnings[] = 'Site-scoped export: global member data (members, members_*, sessions, webauthn_credentials) is omitted because the member account itself is network-wide. Only audit_logs and security_events filtered by site_id are included.';
        }

        $data['audit_logs'] = $this->fetchAuditLogs($userId, $siteId);
        $data['security_events'] = $this->fetchSecurityEvents($userId, $siteId);

        return new UserDataExportDTO(
            providerKey: self::PROVIDER_KEY,
            data: $data,
            warnings: $warnings,
        );
    }

    public function deleteUserData(int $userId, DeletionMode $mode, ?int $siteId = null): UserDataDeletionDTO
    {
        $deleted = 0;
        $anonymized = 0;
        $errors = [];

        try {
            DB::transaction(function () use ($userId, $mode, $siteId, &$deleted, &$anonymized) {
                if ($siteId === null) {
                    [$d, $a] = $this->applyToGlobalTables($userId, $mode);
                    $deleted += $d;
                    $anonymized += $a;
                }

                $anonymized += $this->anonymizeAuditLogs($userId, $siteId);
                $anonymized += $this->anonymizeSecurityEvents($userId, $siteId);
            });
        } catch (\Throwable $e) {
            $errors[] = 'transaction_failed: '.$e->getMessage();
        }

        return new UserDataDeletionDTO(
            providerKey: self::PROVIDER_KEY,
            mode: $mode,
            deletedRecords: $deleted,
            anonymizedRecords: $anonymized,
            errors: $errors,
        );
    }

    /**
     * Apply the deletion mode across every global table and the members row.
     *
     * @return array{0: int, 1: int} [deletedCount, anonymizedCount]
     */
    private function applyToGlobalTables(int $userId, DeletionMode $mode): array
    {
        $deleted = 0;
        $anonymized = 0;

        $member = DB::table('members')->where('id', $userId)->first();
        if ($member === null) {
            return [0, 0];
        }

        if ($mode === DeletionMode::Anonymize) {
            foreach (self::GLOBAL_TABLES as $spec) {
                $anonymized += $this->anonymizeRows($spec['table'], $spec['fk'], $userId, $spec['pii']);
            }
            $anonymized += $this->anonymizeMember($userId, (string) $member->email);

            return [$deleted, $anonymized];
        }

        if ($mode === DeletionMode::HardDelete) {
            foreach (self::GLOBAL_TABLES as $spec) {
                $deleted += DB::table($spec['table'])->where($spec['fk'], $userId)->delete();
            }
            $deleted += DB::table('members_password_reset_tokens')
                ->where('email', $member->email)
                ->delete();
            $deleted += DB::table('members')->where('id', $userId)->delete();

            return [$deleted, $anonymized];
        }

        // SoftDelete: only mark the members row; child rows remain so
        // they can be re-associated if a soft-delete is restored.
        $deleted += DB::table('members')
            ->where('id', $userId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => Carbon::now()]);

        return [$deleted, $anonymized];
    }

    /**
     * Always anonymize PII in audit_logs (regardless of mode) so the
     * append-only audit chain is preserved while the subject becomes
     * unidentifiable. When $siteId is supplied, only rows on that site
     * are touched.
     */
    private function anonymizeAuditLogs(int $userId, ?int $siteId): int
    {
        $query = DB::table('audit_logs')
            ->where('actor_type', Member::class)
            ->where('actor_id', $userId);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $rows = $query->get(['id', 'ip_address', 'user_agent', 'actor_name', 'target_label']);
        $count = 0;

        foreach ($rows as $row) {
            DB::table('audit_logs')
                ->where('id', $row->id)
                ->update([
                    'ip_address' => $this->hashOrNull((string) ($row->ip_address ?? '')),
                    'user_agent' => $this->hashOrNull((string) ($row->user_agent ?? '')),
                    'actor_name' => $this->hashOrNull((string) ($row->actor_name ?? '')),
                    'target_label' => $this->hashOrNull((string) ($row->target_label ?? '')),
                ]);
            $count++;
        }

        return $count;
    }

    private function anonymizeSecurityEvents(int $userId, ?int $siteId): int
    {
        $query = DB::table('security_events')->where('member_id', $userId);
        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $rows = $query->get(['id', 'ip_address', 'user_agent']);
        $count = 0;

        foreach ($rows as $row) {
            DB::table('security_events')
                ->where('id', $row->id)
                ->update([
                    'ip_address' => $this->hashOrNull((string) ($row->ip_address ?? '')),
                    'user_agent' => $this->hashOrNull((string) ($row->user_agent ?? '')),
                ]);
            $count++;
        }

        return $count;
    }

    private function anonymizeMember(int $userId, string $originalEmail): int
    {
        $updated = DB::table('members')
            ->where('id', $userId)
            ->update([
                'email' => $this->hashOrNull($originalEmail).'@anonymized.invalid',
                'pending_email' => null,
                'account_name' => 'anonymized_'.Str::random(12),
                'display_name' => null,
                'description' => null,
                'last_login_ip' => null,
                'last_login_ua' => null,
                'password' => bcrypt(Str::random(64)),
                'status' => 0,
                'updated_at' => Carbon::now(),
            ]);

        DB::table('members_password_reset_tokens')->where('email', $originalEmail)->delete();

        return $updated;
    }

    /**
     * @param  array<int, string>  $piiColumns
     */
    private function anonymizeRows(string $table, string $fkColumn, int $userId, array $piiColumns): int
    {
        if ($piiColumns === []) {
            return 0;
        }

        $rows = DB::table($table)
            ->where($fkColumn, $userId)
            ->get(array_merge(['id'], $piiColumns));

        $count = 0;
        foreach ($rows as $row) {
            $update = [];
            foreach ($piiColumns as $col) {
                $value = $row->{$col} ?? null;
                $update[$col] = $this->hashOrNull((string) ($value ?? ''));
            }
            if ($update === []) {
                continue;
            }

            DB::table($table)->where('id', $row->id)->update($update);
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchMember(int $userId): ?array
    {
        $row = DB::table('members')->where('id', $userId)->first();

        return $row === null ? null : (array) $row;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchByFk(string $table, string $fkColumn, int $userId): array
    {
        return DB::table($table)
            ->where($fkColumn, $userId)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchPasswordResetTokens(int $userId): array
    {
        $email = DB::table('members')->where('id', $userId)->value('email');
        if ($email === null) {
            return [];
        }

        return DB::table('members_password_reset_tokens')
            ->where('email', $email)
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAuditLogs(int $userId, ?int $siteId): array
    {
        $query = DB::table('audit_logs')
            ->where('actor_type', Member::class)
            ->where('actor_id', $userId);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        return $query->orderBy('id')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSecurityEvents(int $userId, ?int $siteId): array
    {
        $query = DB::table('security_events')->where('member_id', $userId);
        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        return $query->orderBy('id')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    /**
     * Hash a string with HMAC-SHA256 using a derivation of app.key,
     * truncated to 32 hex characters (128 bits of collision resistance,
     * which is plenty for an anonymization fence). The shortened output
     * fits in narrow columns such as ip_address VARCHAR(45) without
     * truncation errors. Returns null for empty inputs so we don't
     * pollute storage with "hash of empty string" placeholders.
     */
    private function hashOrNull(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $salt = hash('sha256', (string) config('app.key').'|'.self::ANONYMIZE_SALT_NAMESPACE);

        return substr(hash_hmac('sha256', $value, $salt), 0, 32);
    }
}
