<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Services\Backup;

use App\Models\BackupRecord;
use App\Services\Security\IntegrityKeyring;
use App\Services\Security\IntegritySeal;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Signs a backup archive when it is created and checks the signature
 * before it is restored (dixlase-core#635).
 *
 * The signature is an HMAC-SHA256, keyed with the site's integrity key
 * (`INTEGRITY_KEY`, purpose `backup-archive`), over the archive's SHA-256
 * and the fields of its record that say what it is. Restoring checks the
 * archive against that SHA-256 first, so a signature that verifies covers
 * every byte of the archive without reading it twice.
 *
 * The record's plain hash lives in the database the restore overwrites,
 * so on its own it only detects corruption: whoever can replace the
 * archive and edit its row can make the two agree. The key lives in
 * `.env`, which no backup target collects, so an archive and its row
 * cannot be given a signature that verifies without it.
 *
 * A site without an integrity key signs nothing and checks nothing beyond
 * the hash, as before.
 */
final class BackupArchiveSigner
{
    public const ALGORITHM = 'hmac-sha256';

    /** The signature matches the record */
    public const VALID = 'valid';

    /** The record carries no signature */
    public const UNSIGNED = 'unsigned';

    /** The signature does not match the record, or is malformed */
    public const INVALID = 'invalid';

    /** The key version that signed it is no longer configured */
    public const KEY_UNAVAILABLE = 'key_unavailable';

    /** This site has no integrity key, so nothing is checked */
    public const NOT_CHECKED = 'not_checked';

    /** Version of the signed message, so its layout can change later */
    private const MESSAGE_PREFIX = 'dixlase-backup-archive/1';

    public function __construct(private IntegritySeal $seal) {}

    /**
     * Whether this site signs backups and refuses to restore one whose
     * signature does not verify.
     */
    public function enforced(): bool
    {
        return $this->seal->keyring()->isConfigured();
    }

    /**
     * Signature columns for a new record, or an empty array when no
     * integrity key is configured.
     *
     * @param  list<string>  $targets
     * @return array{signature?:string, signature_algorithm?:string, signature_key_version?:int}
     */
    public function sign(string $pluginSlug, string $fileName, string $hashAlgorithm, string $hash, array $targets): array
    {
        $seal = $this->seal->sign(
            IntegrityKeyring::PURPOSE_BACKUP_ARCHIVE,
            $this->message($pluginSlug, $fileName, $hashAlgorithm, $hash, $targets),
        );

        if ($seal === null) {
            return [];
        }

        return [
            'signature' => $seal['mac'],
            'signature_algorithm' => self::ALGORITHM,
            'signature_key_version' => $seal['key_version'],
        ];
    }

    /**
     * State of a record's signature.
     *
     * Reads the record only; the caller checks the archive against the
     * record's hash. On a site without a key this is NOT_CHECKED.
     *
     * @return self::VALID|self::UNSIGNED|self::INVALID|self::KEY_UNAVAILABLE|self::NOT_CHECKED
     */
    public function check(BackupRecord $backup): string
    {
        if (! $this->enforced()) {
            return self::NOT_CHECKED;
        }

        $signature = $backup->signature;
        if ($signature === null || $signature === '') {
            return self::UNSIGNED;
        }

        if ($backup->signature_algorithm !== self::ALGORITHM
            || $backup->signature_key_version === null
            || ! is_string($backup->hash) || $backup->hash === '') {
            return self::INVALID;
        }

        $targets = is_array($backup->targets) ? array_values(array_map('strval', $backup->targets)) : [];

        $result = $this->seal->check(
            IntegrityKeyring::PURPOSE_BACKUP_ARCHIVE,
            $this->message(
                (string) $backup->plugin_slug,
                (string) $backup->file_name,
                (string) ($backup->hash_algorithm ?: 'sha256'),
                $backup->hash,
                $targets,
            ),
            ['key_version' => (int) $backup->signature_key_version, 'mac' => (string) $signature],
        );

        return match ($result) {
            IntegritySeal::VALID => self::VALID,
            IntegritySeal::KEY_UNAVAILABLE => self::KEY_UNAVAILABLE,
            default => self::INVALID,
        };
    }

    /**
     * Whether a restore of this record must be refused unless the operator
     * explicitly accepts it: the site has a key and the signature does
     * not verify with it.
     */
    public function refuses(string $status): bool
    {
        return $status !== self::VALID && $status !== self::NOT_CHECKED;
    }

    /**
     * @param  list<string>  $targets
     */
    private function message(string $pluginSlug, string $fileName, string $hashAlgorithm, string $hash, array $targets): string
    {
        sort($targets);

        return implode("\n", [
            self::MESSAGE_PREFIX,
            $pluginSlug,
            $fileName,
            strtolower($hashAlgorithm).':'.strtolower($hash),
            implode(',', $targets),
        ]);
    }
}
