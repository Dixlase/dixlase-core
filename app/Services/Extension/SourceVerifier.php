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

namespace App\Services\Extension;

use App\Models\ExtensionSource;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Source Verifier
 *
 * Verifies the authenticity of extension sources using Ed25519 signatures.
 * Official sources are signed with the DixlaseAuthority key, and this class
 * provides the verification logic as a core stub. When DixlaseDevKit is
 * installed, the actual Ed25519 verification is performed; otherwise,
 * the signature status is returned as pending.
 */
class SourceVerifier
{
    /**
     * Verify whether the source has a valid official signature
     *
     * @return array{verified: bool, status: string, message: string}
     */
    public function verify(ExtensionSource $source): array
    {
        if (! $source->hasSignature()) {
            return [
                'verified' => false,
                'status' => 'unsigned',
                'message' => 'No official signature present.',
            ];
        }

        $canonicalData = $this->getCanonicalData($source);
        $signature = $source->official_signature;

        // Attempt Ed25519 verification if sodium is available
        if (! $this->hasVerificationCapability()) {
            return [
                'verified' => false,
                'status' => 'pending',
                'message' => 'Signature verification module is not available. Install DixlaseDevKit to enable verification.',
            ];
        }

        $publicKey = $this->resolvePublicKey();
        if ($publicKey === null) {
            return [
                'verified' => false,
                'status' => 'no_key',
                'message' => 'Public key for source verification is not configured.',
            ];
        }

        try {
            $signatureBytes = sodium_base642bin($signature, SODIUM_BASE64_VARIANT_ORIGINAL);
            $isValid = sodium_crypto_sign_verify_detached($signatureBytes, $canonicalData, $publicKey);

            if ($isValid) {
                return [
                    'verified' => true,
                    'status' => 'valid',
                    'message' => 'Official source signature is valid.',
                ];
            }

            return [
                'verified' => false,
                'status' => 'invalid',
                'message' => 'Official source signature verification failed.',
            ];
        } catch (\SodiumException $e) {
            return [
                'verified' => false,
                'status' => 'error',
                'message' => "Signature verification error: {$e->getMessage()}",
            ];
        }
    }

    /**
     * Sign a source with the official private key (CLI use only)
     *
     * @param  string  $privateKeyBase64  Base64-encoded Ed25519 private key
     */
    public function sign(ExtensionSource $source, string $privateKeyBase64): string
    {
        $canonicalData = $this->getCanonicalData($source);
        $privateKey = sodium_base642bin($privateKeyBase64, SODIUM_BASE64_VARIANT_ORIGINAL);
        $signature = sodium_crypto_sign_detached($canonicalData, $privateKey);

        return sodium_bin2base64($signature, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * Generate canonical data string for signature verification
     *
     * The canonical form is a deterministic JSON representation of the
     * source identity fields, ensuring consistent signature generation
     * and verification regardless of other source attributes.
     */
    public function getCanonicalData(ExtensionSource $source): string
    {
        $data = [
            'type' => $source->type,
            'base_url' => $source->base_url,
            'owner' => $source->owner,
        ];

        ksort($data);

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Check if Ed25519 verification is available
     */
    protected function hasVerificationCapability(): bool
    {
        return extension_loaded('sodium');
    }

    /**
     * Resolve the public key for source verification
     *
     * Looks up the public key by the configured key ID. Currently uses
     * an environment variable; future versions will integrate with
     * DixlaseKeyVault for key management.
     */
    protected function resolvePublicKey(): ?string
    {
        $keyBase64 = env('EXTENSION_SOURCE_PUBLIC_KEY');
        if ($keyBase64 === null) {
            return null;
        }

        try {
            return sodium_base642bin($keyBase64, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (\SodiumException) {
            return null;
        }
    }
}
