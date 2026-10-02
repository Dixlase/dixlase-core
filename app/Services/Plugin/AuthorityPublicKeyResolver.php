<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core-only. Do not reference from plugins/themes.
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

namespace App\Services\Plugin;

use App\Models\AuthorityPublicKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Authority public key resolver
 *
 * Resolve the corresponding public key from key ID. Priority order:
 *   1. Local DB cache (fresh)
 *   2. HTTPS fetch from authority.dixlase.net → update DB cache → return
 *   3. Local DB cache (even if stale, used as fallback when network is unavailable)
 *   4. null (unable to retrieve)
 *
 * Handling of revoked keys: also returns keys with is_active=false. This is to allow
 * verification of plugins signed in the past to continue. The verifier should
 * distinguish between "can still be used for new signatures" and "past signatures are mathematically valid".
 */
class AuthorityPublicKeyResolver
{
    /**
     * Resolve public key record from key ID
     *
     * @return AuthorityPublicKey|null DB record if key can be resolved, null otherwise
     */
    public function resolve(string $keyId): ?AuthorityPublicKey
    {
        $cached = $this->readCache($keyId);

        // If cache exists and within TTL → return as-is
        if ($cached !== null && ! $cached->isStale($this->cacheTtlHours())) {
            return $cached;
        }

        // Attempt to fetch (upsert to DB if successful)
        $fetched = $this->fetchFromAuthority($keyId);
        if ($fetched !== null) {
            return $fetched;
        }

        // Fetch failed → return stale cache if available (offline fallback)
        return $cached;
    }

    /**
     * Read cached key from the DB, returning null if the underlying table is missing
     * or the query fails for any reason.
     *
     * Without this guard a missed migration (authority_public_keys) would crash the
     * entire plugin admin page via PluginHealthScorer → CoreSignatureVerifier →
     * this resolver. Treating the failure as a cache miss lets the caller fall
     * through to the HTTPS fetch path, and ultimately surface signatures as
     * "pending_verification" rather than aborting the request.
     */
    protected function readCache(string $keyId): ?AuthorityPublicKey
    {
        try {
            return AuthorityPublicKey::where('key_id', $keyId)->first();
        } catch (Throwable $e) {
            Log::warning('AuthorityPublicKeyResolver: cache read failed, falling back to network', [
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch key from Authority API and upsert to DB
     */
    protected function fetchFromAuthority(string $keyId): ?AuthorityPublicKey
    {
        $baseUrl = rtrim((string) config('dixlase-authority.url'), '/');
        $url = $baseUrl.'/api/v1/authority/keys/'.urlencode($keyId);

        try {
            $response = Http::timeout((int) config('dixlase-authority.fetch_timeout_seconds', 5))
                ->withOptions(['verify' => (bool) config('dixlase-authority.verify_ssl', true)])
                ->get($url);

            if ($response->status() === 404) {
                Log::info('AuthorityPublicKeyResolver: key not found at authority', [
                    'key_id' => $keyId,
                    'url' => $url,
                ]);

                return null;
            }

            if (! $response->ok()) {
                Log::warning('AuthorityPublicKeyResolver: fetch failed', [
                    'key_id' => $keyId,
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return null;
            }

            $data = $response->json();
            if (empty($data['public_key']) || empty($data['key_id'])) {
                Log::warning('AuthorityPublicKeyResolver: malformed response', [
                    'key_id' => $keyId,
                    'data' => $data,
                ]);

                return null;
            }

            // The answer must be about the key that was asked for: storing it
            // under the returned key_id would let a response fill the cache
            // for a different key.
            if ($data['key_id'] !== $keyId) {
                Log::warning('AuthorityPublicKeyResolver: response is for a different key_id; ignored', [
                    'requested' => $keyId,
                    'returned' => $data['key_id'],
                ]);

                return null;
            }

            // Trust on first use: once a key_id is cached, a different public
            // key under the same id is not accepted silently. Rotating the
            // key behind an unchanged id would otherwise re-key every site on
            // the next refresh, which is exactly what a compromised Authority
            // would do (security review X7). Keep the cached key, record the
            // refusal, and only refresh the timestamp so the site does not
            // ask again on every request. A genuine rotation uses a new key_id.
            $cached = $this->readCache($keyId);
            if ($cached !== null && $cached->public_key !== $data['public_key']) {
                Log::warning('AuthorityPublicKeyResolver: the Authority returned a different public key for a cached key_id; keeping the cached key', [
                    'key_id' => $keyId,
                ]);
                $cached->forceFill(['fetched_at' => now()])->save();

                return $cached;
            }

            return AuthorityPublicKey::updateOrCreate(
                ['key_id' => $data['key_id']],
                [
                    'public_key' => $data['public_key'],
                    'algorithm' => $data['algorithm'] ?? 'ed25519',
                    'is_active' => (bool) ($data['is_active'] ?? true),
                    'expires_at' => $data['expires_at'] ?? null,
                    'authority_created_at' => $data['created_at'] ?? null,
                    'fetched_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('AuthorityPublicKeyResolver: fetch exception', [
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cache TTL (hours)
     */
    protected function cacheTtlHours(): int
    {
        return max(1, (int) config('dixlase-authority.cache_ttl_hours', 24));
    }
}
