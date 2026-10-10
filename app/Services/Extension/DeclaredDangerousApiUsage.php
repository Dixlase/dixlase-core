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

namespace App\Services\Extension;

use App\Services\Plugin\Scanning\PatternRegistry;

/**
 * Resolves the dangerous APIs an extension declares and actually uses.
 *
 * The audit stores only the permissions whose declaration and detection
 * disagree. A dangerous API that is both declared in the manifest and
 * detected in the code therefore leaves no trace in the stored mismatches,
 * yet it is still a call to a process-execution (or similar) API and has to
 * be reflected in the health score.
 *
 * A key counts as "declared and in use" when:
 *   - it is a dangerous API the scanner checks for that extension type,
 *   - the manifest declares it (true, or a non-empty array), and
 *   - the stored audit reports no mismatch for it. With the key declared,
 *     the only possible mismatch is `unused_declaration`, so the absence of
 *     one means the scan detected the call.
 *
 * Keys with a stored mismatch are left to the existing mismatch handling.
 *
 * @internal For Core use only. Do not reference from plugins/themes.
 */
class DeclaredDangerousApiUsage
{
    /**
     * Permission-key prefix shared by every dangerous API pattern.
     */
    public const PREFIX = 'dangerous_api.';

    /**
     * Resolve the dangerous API permission keys that are declared and in use.
     *
     * @param  array<string, mixed>|null  $permissions  Declared manifest permissions
     * @param  array<int, mixed>|null  $mismatches  Stored audit mismatches
     * @param  'plugin'|'theme'  $extensionType
     * @return list<string>
     */
    public static function resolve(?array $permissions, ?array $mismatches, string $extensionType = 'plugin'): array
    {
        if ($permissions === null) {
            return [];
        }

        $mismatched = [];
        foreach ($mismatches ?? [] as $mismatch) {
            if (is_array($mismatch) && is_string($mismatch['permission'] ?? null)) {
                $mismatched[$mismatch['permission']] = true;
            }
        }

        $keys = [];
        foreach (self::knownKeys($extensionType) as $key) {
            if (isset($mismatched[$key])) {
                continue;
            }

            if (self::isDeclared($permissions, $key)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Dangerous API permission keys the scanner checks for an extension type.
     *
     * @param  'plugin'|'theme'  $extensionType
     * @return list<string>
     */
    protected static function knownKeys(string $extensionType): array
    {
        $keys = array_keys(PatternRegistry::createDefault()->getPatternsFor($extensionType));

        return array_values(array_filter(
            $keys,
            fn (string $key) => str_starts_with($key, self::PREFIX),
        ));
    }

    /**
     * Whether the manifest declares a dot-notation permission key.
     *
     * Mirrors the audit's comparison: a non-empty array counts as declared.
     *
     * @param  array<string, mixed>  $permissions
     */
    protected static function isDeclared(array $permissions, string $key): bool
    {
        $value = $permissions;
        foreach (explode('.', $key) as $part) {
            $value = is_array($value) ? ($value[$part] ?? false) : false;
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return (bool) $value;
    }
}
