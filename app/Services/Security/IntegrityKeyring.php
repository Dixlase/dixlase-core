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

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * The site's integrity key, with its previous versions (dixlase-core#665).
 *
 * One secret, set in `.env` as `INTEGRITY_KEY=<version>:base64:<32+ bytes>`,
 * keys everything core uses to notice tampering: the audit log's per-row
 * hash chain, its daily seals and the boundary between unkeyed and keyed
 * rows. Each use gets its own key, derived with HKDF, so a key recovered
 * from one use says nothing about another.
 *
 * Rotation moves the current key into `INTEGRITY_PREVIOUS_KEYS` (comma
 * separated, same format) and sets a new one with a higher version. Only
 * the current key signs; every listed version still verifies, so what was
 * signed before a rotation keeps verifying for as long as its key is kept.
 *
 * Version numbers are reserved below FIRST_VERSION:
 * - 0 is a row hashed before keying (plain SHA-256, no key)
 * - 1 is a daily seal made with the legacy seal key (`AUDIT_LOG_SECRET`,
 *   or the application key when that is empty), as every seal was before
 *   this keyring existed
 *
 * The key lives in `.env` because that is outside the database (what it
 * guards against is a database-level rewrite) and outside the backup
 * archive. Code running inside the application reads the same `.env`, so
 * this does not protect against a compromised application or plugin.
 */
class IntegrityKeyring
{
    /** A row hashed before keying: plain SHA-256 */
    public const UNKEYED_VERSION = 0;

    /** A daily seal signed with the legacy seal key */
    public const LEGACY_SEAL_VERSION = 1;

    /** The first version a key from this keyring can carry */
    public const FIRST_VERSION = 2;

    public const MAX_VERSION = 65535;

    /** Minimum length of a key, in bytes after base64 decoding */
    public const MIN_KEY_BYTES = 32;

    public const PURPOSE_AUDIT_CHAIN = 'audit-chain';

    public const PURPOSE_AUDIT_SEAL = 'audit-seal';

    public const PURPOSE_AUDIT_EPOCH = 'audit-epoch';

    /** Seal of a file-integrity baseline (core_hashes.json and friends, #803) */
    public const PURPOSE_FILE_BASELINE = 'file-baseline';

    /** MAC of the enabled-plugins cache read during register() (#803) */
    public const PURPOSE_PLUGIN_CACHE = 'plugin-cache';

    /** Prefix of the HKDF info string, so a derived key is bound to Dixlase */
    private const INFO_PREFIX = 'dixlase-integrity:';

    private const KEY_PATTERN = '/^(\d{1,5}):base64:([A-Za-z0-9+\/]+={0,2})$/';

    /**
     * @var array{raw:string, current:int|null, keys:array<int, string>, problems:list<string>}|null
     */
    private ?array $parsed = null;

    /**
     * Version of the key that signs now, or null when no key is configured.
     */
    public function currentVersion(): ?int
    {
        return $this->parse()['current'];
    }

    public function isConfigured(): bool
    {
        return $this->currentVersion() !== null;
    }

    /**
     * Whether a key of this version is available to verify with.
     */
    public function has(int $version): bool
    {
        return isset($this->parse()['keys'][$version]);
    }

    /**
     * Every version that can verify, newest first.
     *
     * @return list<int>
     */
    public function versions(): array
    {
        $versions = array_keys($this->parse()['keys']);
        rsort($versions);

        return $versions;
    }

    /**
     * The key for one use at one version, or null when that version is not
     * configured.
     */
    public function key(string $purpose, int $version): ?string
    {
        $master = $this->parse()['keys'][$version] ?? null;

        if ($master === null) {
            return null;
        }

        return hash_hkdf('sha256', $master, 32, self::INFO_PREFIX.$purpose);
    }

    /**
     * The key for one use at the current version, with that version, or
     * null when no key is configured.
     *
     * @return array{version:int, key:string}|null
     */
    public function currentKey(string $purpose): ?array
    {
        $version = $this->currentVersion();

        if ($version === null) {
            return null;
        }

        $key = $this->key($purpose, $version);

        return $key === null ? null : ['version' => $version, 'key' => $key];
    }

    /**
     * Key of the seals made before this keyring existed (version 1).
     *
     * It is used as configured, not derived, so existing seals verify
     * unchanged. When `AUDIT_LOG_SECRET` is empty it is the application key,
     * so changing `APP_KEY` makes those seals fail.
     */
    public function legacySealKey(): string
    {
        return (string) (config('app.audit_log_secret') ?: config('app.key'));
    }

    /**
     * Entries in the configuration that were ignored, for the operator.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        return $this->parse()['problems'];
    }

    /**
     * A new key in the `.env` format.
     */
    public static function generate(int $version): string
    {
        if ($version < self::FIRST_VERSION || $version > self::MAX_VERSION) {
            throw new \InvalidArgumentException('Integrity key version out of range: '.$version);
        }

        return $version.':base64:'.base64_encode(random_bytes(self::MIN_KEY_BYTES));
    }

    /**
     * Version of a key in the `.env` format, or null when it is not one.
     */
    public static function versionOf(string $entry): ?int
    {
        $decoded = self::decode($entry);

        return $decoded === null ? null : $decoded['version'];
    }

    /**
     * @return array{raw:string, current:int|null, keys:array<int, string>, problems:list<string>}
     */
    private function parse(): array
    {
        $current = trim((string) config('security.integrity_keys.current', ''));
        $previous = trim((string) config('security.integrity_keys.previous', ''));
        $raw = $current."\n".$previous;

        if ($this->parsed !== null && $this->parsed['raw'] === $raw) {
            return $this->parsed;
        }

        $keys = [];
        $problems = [];
        $currentVersion = null;

        if ($current !== '') {
            $decoded = self::decode($current);
            if ($decoded === null) {
                $problems[] = 'INTEGRITY_KEY is not in the form <version>:base64:<key> with a version of at least '
                    .self::FIRST_VERSION.' and a key of at least '.self::MIN_KEY_BYTES.' bytes; it is ignored';
            } else {
                $currentVersion = $decoded['version'];
                $keys[$decoded['version']] = $decoded['key'];
            }
        }

        foreach (array_filter(array_map('trim', explode(',', $previous))) as $index => $entry) {
            $decoded = self::decode($entry);
            if ($decoded === null) {
                $problems[] = 'INTEGRITY_PREVIOUS_KEYS entry '.($index + 1).' is not a valid key; it is ignored';

                continue;
            }
            if (isset($keys[$decoded['version']])) {
                if (! hash_equals($keys[$decoded['version']], $decoded['key'])) {
                    $problems[] = 'INTEGRITY_PREVIOUS_KEYS lists version '.$decoded['version'].' with a different key; the first one is used';
                }

                continue;
            }
            $keys[$decoded['version']] = $decoded['key'];
        }

        if ($problems !== [] && ($this->parsed === null || $this->parsed['problems'] !== $problems)) {
            Log::warning('Integrity key configuration has problems', ['problems' => $problems]);
        }

        return $this->parsed = [
            'raw' => $raw,
            'current' => $currentVersion,
            'keys' => $keys,
            'problems' => $problems,
        ];
    }

    /**
     * @return array{version:int, key:string}|null
     */
    private static function decode(string $entry): ?array
    {
        if (! preg_match(self::KEY_PATTERN, trim($entry), $matches)) {
            return null;
        }

        $version = (int) $matches[1];
        if ($version < self::FIRST_VERSION || $version > self::MAX_VERSION) {
            return null;
        }

        $key = base64_decode($matches[2], true);
        if ($key === false || strlen($key) < self::MIN_KEY_BYTES) {
            return null;
        }

        return ['version' => $version, 'key' => $key];
    }
}
