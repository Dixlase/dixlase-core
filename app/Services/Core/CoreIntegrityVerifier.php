<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @internal Core use only. Do not reference from plugins/themes.
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

namespace App\Services\Core;

use App\Contracts\Core\CoreManifestBuilderInterface;
use App\DTO\Core\CoreIntegrityResult;
use App\Models\SignatureWaiver;
use App\Services\Plugin\AuthorityPublicKeyResolver;
use App\Services\Signature\SignatureWaiverService;
use App\Support\PinnedPublicKeys;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifies the integrity of Core itself against its signed manifest.
 *
 * Mirrors the plugin verifier's Ed25519 + canonical-JSON scheme, but resolves
 * the trusted public key from an offline-pinned anchor in config first (so it
 * works at install time with no network), then falls back to the online
 * Authority resolver for rotation. The crypto step proves the manifest is an
 * authentic Dixlase release; a file diff against that authentic manifest is
 * then surfaced as MODIFIED (not INVALID), since local customization is a
 * legitimate, supported state.
 */
class CoreIntegrityVerifier
{
    public const CACHE_KEY = 'core_integrity.result';

    public function __construct(
        protected AuthorityPublicKeyResolver $publicKeyResolver,
        protected CoreManifestBuilderInterface $manifestBuilder,
        protected SignatureWaiverService $waiverService,
    ) {}

    /**
     * Cached result for hot paths (admin badge, install screen). Recomputes
     * after the TTL or when the cache is cleared.
     */
    public function cachedResult(): CoreIntegrityResult
    {
        $ttl = (int) config('core-integrity.cache_ttl_seconds', 3600);

        if ($ttl <= 0) {
            return $this->verify();
        }

        return Cache::remember(self::CACHE_KEY, $ttl, fn () => $this->verify());
    }

    /**
     * Compute a fresh verification result, with the operator waiver overlaid.
     *
     * @param  string|null  $basePath  core root override (testing); defaults to base_path()
     */
    public function verify(?string $basePath = null): CoreIntegrityResult
    {
        $result = $this->computeObjective($basePath);

        // Overlay the operator waiver (table-missing-safe). Genuine core never
        // needs a waiver, so only overlay non-genuine results.
        if (! $result->isGenuine() && $this->waiverService->isWaived(SignatureWaiver::SCOPE_CORE, 'core')) {
            return $result->withWaived(true);
        }

        return $result;
    }

    /**
     * Compute the objective verification result (no waiver overlay).
     *
     * @param  string|null  $basePath  core root override (testing); defaults to base_path()
     */
    protected function computeObjective(?string $basePath = null): CoreIntegrityResult
    {
        $basePath = rtrim($basePath ?? base_path(), DIRECTORY_SEPARATOR);
        $manifestPath = $basePath.'/'.config('core-integrity.manifest_file', 'core-manifest.json');
        $signaturePath = $basePath.'/'.config('core-integrity.signature_file', 'core-signature.sig');

        if (! File::exists($manifestPath)) {
            return CoreIntegrityResult::unsigned();
        }

        $manifest = json_decode(File::get($manifestPath), true);
        if (! is_array($manifest)) {
            return CoreIntegrityResult::error('core-manifest.json is unreadable or invalid JSON.');
        }

        if (! isset($manifest['signing']) || ! isset($manifest['files']) || ! is_array($manifest['files'])) {
            return CoreIntegrityResult::error('core-manifest.json is missing the signing or files section.');
        }

        $declaredKeyId = $manifest['signing']['key_id'] ?? null;
        $version = $manifest['version'] ?? null;

        if (! File::exists($signaturePath)) {
            return CoreIntegrityResult::unsigned('Manifest present but core-signature.sig is missing.');
        }

        $sig = json_decode(File::get($signaturePath), true);
        if (! is_array($sig) || empty($sig['key_id']) || empty($sig['signature'])) {
            return CoreIntegrityResult::invalid('core-signature.sig is malformed or missing required fields.');
        }

        $keyId = (string) $sig['key_id'];
        $signedAt = $sig['signed_at'] ?? null;

        if ($declaredKeyId !== null && $declaredKeyId !== $keyId) {
            return CoreIntegrityResult::invalid('key_id mismatch between manifest and signature.', ['key_id' => $keyId]);
        }

        $type = $this->determineType($keyId);

        if (! function_exists('sodium_crypto_sign_verify_detached')) {
            return CoreIntegrityResult::error('The sodium extension is required for signature verification.');
        }

        $publicKey = $this->resolvePublicKey($keyId);
        if ($publicKey === null) {
            return CoreIntegrityResult::pending($keyId);
        }

        try {
            $cryptoValid = $this->verifyEd25519($manifest, (string) $sig['signature'], $publicKey);
        } catch (Throwable $e) {
            Log::warning('CoreIntegrityVerifier: ed25519 verification error', [
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);

            return CoreIntegrityResult::error('Signature verification error: '.$e->getMessage());
        }

        if (! $cryptoValid) {
            return CoreIntegrityResult::invalid('Core manifest signature is invalid.', ['key_id' => $keyId]);
        }

        // Manifest is authentic. Compare the recorded hashes against the live
        // files; any difference is a local modification, not tampering.
        $diff = $this->diffFiles($basePath, $manifest['files']);
        if ($diff['changed']) {
            return CoreIntegrityResult::modified($keyId, $type, $signedAt, $version, $diff['mismatched'], $diff['missing'], $diff['extra']);
        }

        return CoreIntegrityResult::genuine($keyId, $type, $signedAt, $version);
    }

    /**
     * Resolve the trusted public key for a key_id: offline-pinned anchor first
     * (works at install time), then the online Authority resolver (rotation).
     *
     * @return string|null base64-encoded public key, or null if unresolved
     */
    protected function resolvePublicKey(string $keyId): ?string
    {
        return PinnedPublicKeys::get($keyId)
            ?? $this->publicKeyResolver->resolve($keyId)?->public_key;
    }

    /**
     * Ed25519-verify the canonical manifest bytes.
     */
    protected function verifyEd25519(array $manifest, string $signatureBase64, string $publicKeyEncoded): bool
    {
        $dataToVerify = $this->manifestBuilder->canonicalize($manifest);

        $signatureBinary = base64_decode($signatureBase64);
        $publicKeyBinary = $this->decodeKey($publicKeyEncoded);

        return sodium_crypto_sign_verify_detached($signatureBinary, $dataToVerify, $publicKeyBinary);
    }

    /**
     * Decode "base64:..." or plain base64 to binary.
     */
    protected function decodeKey(string $encoded): string
    {
        if (str_starts_with($encoded, 'base64:')) {
            $encoded = substr($encoded, 7);
        }

        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            throw new \RuntimeException('Failed to decode public key from base64');
        }

        return $decoded;
    }

    /**
     * Diff the manifest's recorded hashes against the current files.
     *
     * @param  array<string, string>  $expected  path => "sha256:hash"
     * @return array{changed: bool, mismatched: array<int, string>, missing: array<int, string>, extra: array<int, string>}
     */
    protected function diffFiles(string $basePath, array $expected): array
    {
        $actual = $this->manifestBuilder->fileHashes($basePath);

        $missing = array_keys(array_diff_key($expected, $actual));
        $extra = array_keys(array_diff_key($actual, $expected));
        $mismatched = [];

        foreach ($expected as $relPath => $expectedHash) {
            if (isset($actual[$relPath]) && $actual[$relPath] !== $expectedHash) {
                $mismatched[] = $relPath;
            }
        }

        sort($missing);
        sort($extra);
        sort($mismatched);

        return [
            'changed' => ! empty($missing) || ! empty($extra) || ! empty($mismatched),
            'mismatched' => $mismatched,
            'missing' => $missing,
            'extra' => $extra,
        ];
    }

    /**
     * Signature type for badge display. Core keys count as official, but
     * only when pinned -- the prefix alone is just a name (security review X7).
     */
    protected function determineType(string $keyId): ?string
    {
        if (str_starts_with($keyId, 'dixlase-core') || str_starts_with($keyId, 'dixlase-official') || str_starts_with($keyId, 'dixlase-authority')) {
            return PinnedPublicKeys::isPinned($keyId) ? 'official' : null;
        }
        if (str_starts_with($keyId, 'dixlase-verified') || str_starts_with($keyId, 'marketplace')) {
            return 'verified';
        }
        if (str_starts_with($keyId, 'partner-')) {
            return 'partner';
        }

        return null;
    }
}
