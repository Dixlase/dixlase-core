<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Services\Plugin;

use App\Models\AuthorityPublicKey;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Authority 公開鍵リゾルバ
 *
 * 鍵 ID から対応する公開鍵を解決する。優先順位は以下:
 *   1. ローカル DB キャッシュ（fresh）
 *   2. keys.dixlase.com から HTTPS フェッチ → DB キャッシュ更新 → 返却
 *   3. ローカル DB キャッシュ（stale でも、ネット不通時のフォールバックとして使用）
 *   4. null（取得不能）
 *
 * 失効鍵の扱い: is_active=false の鍵も返す。過去に署名された
 * プラグインの検証を継続できるようにするため。検証側で
 * 「現在も新規署名に使えるか」と「過去署名が数学的に有効か」を区別する。
 */
class AuthorityPublicKeyResolver
{
    /**
     * 鍵 ID から公開鍵レコードを解決する
     *
     * @return AuthorityPublicKey|null 鍵が解決できれば DB レコード、できなければ null
     */
    public function resolve(string $keyId): ?AuthorityPublicKey
    {
        $cached = AuthorityPublicKey::where('key_id', $keyId)->first();

        // キャッシュが有り、TTL 内 → そのまま返す
        if ($cached !== null && ! $cached->isStale($this->cacheTtlHours())) {
            return $cached;
        }

        // フェッチを試みる（成功したら DB に upsert）
        $fetched = $this->fetchFromAuthority($keyId);
        if ($fetched !== null) {
            return $fetched;
        }

        // フェッチ失敗 → stale キャッシュでもあれば返す（オフラインフォールバック）
        return $cached;
    }

    /**
     * Authority API から鍵を取得して DB に upsert する
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
     * キャッシュ TTL（時間）
     */
    protected function cacheTtlHours(): int
    {
        return max(1, (int) config('dixlase-authority.cache_ttl_hours', 24));
    }
}
