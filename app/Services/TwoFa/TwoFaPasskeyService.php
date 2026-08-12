<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services\TwoFa;

use App\Contracts\TwoFa\TwoFaPasskeyServiceInterface;
use App\Contracts\TwoFaInterface;
use App\Models\Member;
use App\Models\MembersTrustedDevice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TwoFaPasskeyService implements TwoFaPasskeyServiceInterface
{
    /**
     * Check if Passkey is available
     */
    public function isAvailable(): bool
    {
        // Check if HTTPS is enabled (excluding localhost)
        if (request()->getHost() === 'localhost' || request()->getHost() === '127.0.0.1') {
            return true;
        }

        // HTTPS is required in production environment
        return request()->secure();
    }

    /**
     * Check if user has Passkey credentials
     *
     * Use Laragear's webauthnCredentials() relation
     */
    public function hasCredentials(TwoFaInterface $user): bool
    {
        return $user->webauthnCredentials()->exists();
    }

    /**
     * Generate Passkey challenge (for 2FA)
     */
    public function generatePasskeyChallenge($user): array
    {
        // Planned for implementation in Phase 3
        Log::info('[Passkey] Challenge generation requested', [
            'member_id' => $user->id,
        ]);

        return [
            'challenge' => 'placeholder_challenge',
            'message' => 'Passkey authentication will be implemented in Phase 3',
        ];
    }

    /**
     * Generate passkey challenge for login
     *
     * Generate secure challenge using Laragear\WebAuthn
     */
    public function generateLoginChallenge(TwoFaInterface $user): array
    {
        try {
            // Laragear WebAuthn v4: Create AssertionCreation object
            $assertionCreation = new \Laragear\WebAuthn\Assertion\Creator\AssertionCreation($user);

            // Execute AssertionCreator pipeline
            $assertionCreator = app(\Laragear\WebAuthn\Assertion\Creator\AssertionCreator::class);
            $result = $assertionCreator->send($assertionCreation)->thenReturn();

            // Convert from JsonTransport object to array
            $jsonData = is_array($result->json) ? $result->json : $result->json->toArray();

            // Debug: Check allowCredentials included in challenge
            $allowCredentials = $jsonData['allowCredentials'] ?? [];
            Log::info('[Passkey] Login challenge generated (Laragear)', [
                'member_id' => $user->getId(),
                'credentials_count' => $user->webauthnCredentials()->count(),
                'allowCredentials' => $allowCredentials,
            ]);

            return [
                'id' => Str::random(32),
                'publicKey' => $jsonData,
            ];
        } catch (\Exception $e) {
            Log::error('[Passkey] Challenge generation failed', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify passkey authentication for login
     *
     * Verify cryptographic signature using Laragear\WebAuthn
     */
    public function verifyLoginChallenge(TwoFaInterface $user, array $data, ?string $challengeId = null): bool
    {
        try {
            // Laragear WebAuthn v4: Create JsonTransport (pass request JSON data)
            $jsonTransport = new \Laragear\WebAuthn\JsonTransport(request()->json()->all());

            // Debug: Log userHandle and credential information
            $userHandle = request()->json('response.userHandle');
            $credentialId = request()->json('id');
            $credential = \App\Models\WebAuthnCredential::find($credentialId);

            Log::info('[Passkey] Verification debug', [
                'member_id' => $user->getId(),
                'userHandle_from_browser' => $userHandle,
                'credential_id' => $credentialId,
                'credential_user_id' => $credential ? $credential->user_id : null,
                'expected_user_id' => $user->webAuthnId()->toString(),
                'credential_casts' => $credential ? $credential->getCasts() : null,
                'credential_class' => $credential ? get_class($credential) : null,
            ]);

            // Create AssertionValidation object
            $assertionValidation = new \Laragear\WebAuthn\Assertion\Validator\AssertionValidation(
                $jsonTransport,
                $user
            );

            // Execute AssertionValidator pipeline
            $assertionValidator = app(\Laragear\WebAuthn\Assertion\Validator\AssertionValidator::class);
            $result = $assertionValidator->send($assertionValidation)->thenReturn();

            if ($result && $result->credential) {
                Log::info('[Passkey] Login verification successful (Laragear)', [
                    'member_id' => $user->getId(),
                    'credential_id' => $result->credential->id,
                ]);

                return true;
            }

            Log::warning('[Passkey] Login verification failed', [
                'member_id' => $user->getId(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('[Passkey] Verification error', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Verify Passkey authentication
     */
    public function validatePasskeyAuth($user, $input): bool
    {
        // Planned for implementation in Phase 3
        Log::info('[Passkey] Authentication validation requested', [
            'member_id' => $user->id,
        ]);

        return false;
    }

    /**
     * Get all Passkey devices for the user
     *
     * @deprecated Use getCredentials() instead.
     */
    public function getDevices(TwoFaInterface $user)
    {
        return $this->getCredentials($user);
    }

    // ========================================
    // WebAuthn (Biometric) functionality
    // ========================================

    /**
     * Register WebAuthn credentials
     */
    public function registerCredential(TwoFaInterface $user, array $credentialData, ?string $deviceName = null)
    {
        try {
            // Create JsonTransport object
            $jsonTransport = new \Laragear\WebAuthn\JsonTransport($credentialData);

            // Create AttestationValidation object
            $attestationValidation = new \Laragear\WebAuthn\Attestation\Validator\AttestationValidation(
                $user,
                $jsonTransport
            );

            // Execute AttestationValidator pipeline
            $attestationValidator = app(\Laragear\WebAuthn\Attestation\Validator\AttestationValidator::class);
            $result = $attestationValidator->send($attestationValidation)->thenReturn();

            // Set device name
            if ($deviceName) {
                $result->credential->alias = $deviceName;
                $result->credential->save();
            }

            Log::info('[Passkey] Credential registration successful (Laragear)', [
                'member_id' => $user->getId(),
                'credential_id' => $result->credential->id,
                'device_name' => $deviceName ?? $this->generateDeviceName(),
            ]);

            return $result->credential;
        } catch (\Exception $e) {
            Log::error('[Passkey] Registration error', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Not implemented. Use verifyLoginChallenge() instead.
     *
     * This method used to return true after checking only that the challenge
     * in clientDataJSON matched the one in the session. The public key and the
     * signature were accepted as arguments and never compared; the RP ID hash,
     * the UP/UV flags and the signature counter in authenticatorData were not
     * examined either, nor were the `type` and `origin` fields of
     * clientDataJSON. Anyone able to name a credential ID and replay the
     * challenge the server had just issued would have passed.
     *
     * Nothing in Core called it -- verifyPasskeyCredential(), its only route
     * in, has no callers anywhere in the repository, and Core registers no 2FA
     * passkey challenge route. Admin passkey *login* is a separate path
     * (verifyLoginChallenge below) that runs Laragear's AssertionValidator
     * pipeline and is sound.
     *
     * It is left throwing rather than deleted because the class carries `@api`
     * and appears in PLUGIN-API.md, so a plugin may already have been written
     * against the name. Throwing turns "silently accepts an unsigned
     * assertion" into an immediate, obvious failure at the point of use --
     * which is what a plugin author needs, since the old behaviour looked
     * correct: the name promised verification, the signature was in the
     * signature, and the return value was true.
     *
     * @throws \LogicException always
     */
    public function verifyAssertion(TwoFaInterface $user, array $assertionData): bool
    {
        throw new \LogicException(
            'TwoFaPasskeyService::verifyAssertion() is not implemented and never verified the '
            .'assertion signature. Use verifyLoginChallenge(), which runs the Laragear '
            .'AssertionValidator pipeline (challenge, origin, RP ID, signature, counter).'
        );
    }

    /**
     * Get WebAuthn credentials list
     */
    public function getCredentials(TwoFaInterface $user)
    {
        return $user->twoFaPasskeys()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete WebAuthn credentials
     */
    public function revokeCredential(TwoFaInterface $user, string $credentialId): bool
    {
        $deleted = $user->twoFaPasskeys()
            ->where('id', $credentialId)
            ->delete();

        if ($deleted) {
            Log::info("[Passkey] Credential deleted: User ID {$user->getId()}, Credential ID: {$credentialId}");
        }

        return $deleted > 0;
    }

    /**
     * Delete all WebAuthn credentials
     */
    public function revokeAllCredentials(TwoFaInterface $user): int
    {
        $count = $user->twoFaPasskeys()->count();
        $deleted = $user->twoFaPasskeys()->delete();

        if ($deleted) {
            Log::info("[Passkey] All credentials deleted: User ID {$user->getId()}, Count: {$count}");
        }

        return $count;
    }

    /**
     * Generate WebAuthn registration challenge
     */
    public function generateRegistrationChallenge(TwoFaInterface $user): array
    {
        try {
            // Create AttestationCreation object
            $attestationCreation = new \Laragear\WebAuthn\Attestation\Creator\AttestationCreation($user);

            // Execute AttestationCreator pipeline
            $attestationCreator = app(\Laragear\WebAuthn\Attestation\Creator\AttestationCreator::class);
            $result = $attestationCreator->send($attestationCreation)->thenReturn();

            // Convert from JsonTransport object to array
            $jsonData = is_array($result->json) ? $result->json : $result->json->toArray();

            Log::info('[Passkey] Registration challenge generated (Laragear)', [
                'member_id' => $user->getId(),
            ]);

            return $jsonData;
        } catch (\Exception $e) {
            Log::error('[Passkey] Registration challenge generation error', [
                'member_id' => $user->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate WebAuthn authentication challenge
     */
    public function generateAuthenticationChallenge(TwoFaInterface $user): array
    {
        $challenge = random_bytes(32);
        $challengeBase64 = base64_encode($challenge);

        $credentials = $this->getCredentials($user);
        $allowCredentials = $credentials->map(function ($credential) {
            return [
                'type' => 'public-key',
                'id' => $credential->id, // id instead of credential_id
                'transports' => json_decode($credential->transports ?? '["internal","hybrid"]', true),
            ];
        })->toArray();

        $options = [
            'challenge' => $challengeBase64,
            'timeout' => 60000,
            'rpId' => parse_url(config('app.url'), PHP_URL_HOST),
            'allowCredentials' => $allowCredentials,
            'userVerification' => 'required',
        ];

        session(['webauthn_challenge' => $challengeBase64]);

        return $options;
    }

    /**
     * Generate device name
     */
    private function generateDeviceName(): string
    {
        $userAgent = request()->userAgent();

        if (str_contains($userAgent, 'iPhone')) {
            return 'iPhone Touch ID/Face ID';
        } elseif (str_contains($userAgent, 'iPad')) {
            return 'iPad Touch ID/Face ID';
        } elseif (str_contains($userAgent, 'Android')) {
            return 'Android Fingerprint';
        } elseif (str_contains($userAgent, 'Windows')) {
            return 'Windows Hello';
        } elseif (str_contains($userAgent, 'Macintosh')) {
            return 'Mac Touch ID';
        }

        return 'Biometric Device';
    }

    /**
     * Removed. It was labelled "simplified"; it verified no signature at all.
     *
     * The body compared the challenge from clientDataJSON against the one in
     * the session and then returned true, logging "Signature verification
     * succeeded (simplified)". $publicKey and $signature were parameters it
     * never read. A caller could therefore authenticate with any signature
     * bytes.
     *
     * The real implementation is verifyLoginChallenge(), which hands the
     * assertion to Laragear's AssertionValidator. There is no reason to keep a
     * second, weaker one next to it -- the name is what made the original
     * dangerous, so the name does not come back.
     */

    // ========================================
    // Trusted device management
    // ========================================

    /**
     * Check if current device is trusted
     */
    public function isTrustedDevice(Member $member): bool
    {
        $deviceToken = request()->cookie('trusted_device_token');
        $trustedDevice = null;

        // Verify with cookie token
        if ($deviceToken) {
            $hashedToken = hash('sha256', $deviceToken);

            $trustedDevice = MembersTrustedDevice::where('member_id', $member->id)
                ->where('token', $hashedToken)
                ->first();

            if ($trustedDevice) {
                Log::info("[Passkey] Cookie authentication succeeded: User ID {$member->id}");
            }
        }

        // If no cookie, verify with IP + User Agent
        if (! $trustedDevice) {
            $ipAddress = request()->ip();
            $userAgent = request()->userAgent();

            $trustedDevice = MembersTrustedDevice::where('member_id', $member->id)
                ->where('ip_address', $ipAddress)
                ->where('user_agent', $userAgent)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($trustedDevice) {
                Log::info("[Passkey] IP+UA authentication succeeded: User ID {$member->id}");

                // Reset cookie
                $tokenLength = config('two-fa.device_token_length', 64);
                $newToken = Str::random($tokenLength);
                $hashedToken = hash('sha256', $newToken);
                $trustedDevice->update(['token' => $hashedToken]);

                $cookieConfig = config('two-fa.device_cookie', []);
                cookie()->queue(
                    $cookieConfig['name'] ?? 'trusted_device_token',
                    $newToken,
                    $cookieConfig['lifetime'] ?? 60 * 24 * 30,
                    $cookieConfig['path'] ?? '/',
                    $cookieConfig['domain'] ?? null,
                    $cookieConfig['secure'] ?? true,
                    $cookieConfig['http_only'] ?? true,
                    false,
                    $cookieConfig['same_site'] ?? 'strict'
                );
            }
        }

        if (! $trustedDevice) {
            Log::info("[Passkey] No trusted device: User ID {$member->id}");

            return false;
        }

        // Check expiration (from security settings)
        $expirationDays = (int) \App\Models\SecuritySetting::getValue(
            'trusted_device_expire_days',
            config('two-fa.device_expiration_days', 30)
        );

        $expirationDate = $trustedDevice->updated_at->addDays($expirationDays);

        if (now()->greaterThan($expirationDate)) {
            Log::info("[Passkey] Device expired: User ID {$member->id}");

            return false;
        }

        $trustedDevice->touch();

        return true;
    }

    /**
     * Delete trusted device
     */
    public function revokeDevice(Member $member, int $deviceId): bool
    {
        $deleted = MembersTrustedDevice::where('member_id', $member->id)
            ->where('id', $deviceId)
            ->delete();

        if ($deleted) {
            Log::info("[Passkey] Trusted device deleted: User ID {$member->id}, Device ID: {$deviceId}");
        }

        return $deleted > 0;
    }

    /**
     * Get trusted device list
     */
    public function getTrustedDevices(Member $member)
    {
        return MembersTrustedDevice::where('member_id', $member->id)
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Delete all trusted devices
     */
    public function revokeAllTrustedDevices(Member $member): int
    {
        $count = MembersTrustedDevice::where('member_id', $member->id)->count();
        $deleted = MembersTrustedDevice::where('member_id', $member->id)->delete();

        if ($deleted) {
            Log::info("[Passkey] All trusted devices deleted: User ID {$member->id}, Count: {$count}");
        }

        return $count;
    }
}
