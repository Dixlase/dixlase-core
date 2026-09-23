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
use App\Services\TwoFa\Passkeys\PasskeyCeremony;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Passkey;
use LogicException;
use Throwable;

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
     */
    public function hasCredentials(TwoFaInterface $user): bool
    {
        return $user->twoFaPasskeys()->exists();
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
     * Returns `['id' => string, 'publicKey' => PublicKeyCredentialRequestOptionsJSON]`.
     * `publicKey.challenge` and `publicKey.allowCredentials[].id` are
     * base64url strings; allowCredentials lists only this user's passkeys.
     * The full options are kept in the session for verifyLoginChallenge().
     */
    public function generateLoginChallenge(TwoFaInterface $user): array
    {
        $owner = $this->passkeyOwner($user);

        try {
            $publicKey = $this->ceremony()->verificationOptions($owner);

            Log::info('[Passkey] Login challenge generated', [
                'user_id' => $user->getId(),
                'credentials_count' => count($publicKey['allowCredentials'] ?? []),
            ]);

            return [
                'id' => Str::random(32),
                'publicKey' => $publicKey,
            ];
        } catch (Throwable $e) {
            Log::error('[Passkey] Challenge generation failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify passkey authentication for login
     *
     * `$data` is the serialized PublicKeyCredential returned by
     * navigator.credentials.get(), with binary fields base64url-encoded.
     * Verifies the challenge issued by generateLoginChallenge(), the origin,
     * the RP ID hash, the user verification flag, the signature and the
     * signature counter, and that the passkey belongs to `$user`.
     * `$challengeId` is accepted for compatibility and no longer used.
     */
    public function verifyLoginChallenge(TwoFaInterface $user, array $data, ?string $challengeId = null): bool
    {
        $owner = $this->passkeyOwner($user);

        try {
            $passkey = $this->ceremony()->verify($owner, $data);

            Log::info('[Passkey] Login verification successful', [
                'user_id' => $user->getId(),
                'passkey_id' => $passkey->getKey(),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::warning('[Passkey] Login verification failed', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
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
     *
     * `$credentialData` is the serialized PublicKeyCredential returned by
     * navigator.credentials.create(), with binary fields base64url-encoded.
     * It is validated against the options issued by
     * generateRegistrationChallenge() for the same user.
     *
     * @return Passkey The stored passkey
     *
     * @throws \Laravel\Passkeys\Exceptions\InvalidPasskeyException when the attestation is rejected
     */
    public function registerCredential(TwoFaInterface $user, array $credentialData, ?string $deviceName = null)
    {
        $owner = $this->passkeyOwner($user);
        $name = $deviceName !== null && trim($deviceName) !== '' ? trim($deviceName) : $this->generateDeviceName();

        try {
            $passkey = $this->ceremony()->register($owner, $credentialData, $name);

            Log::info('[Passkey] Credential registration successful', [
                'user_id' => $user->getId(),
                'passkey_id' => $passkey->getKey(),
                'device_name' => $name,
            ]);

            return $passkey;
        } catch (Throwable $e) {
            Log::error('[Passkey] Registration error', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
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
     * (verifyLoginChallenge above) that runs the full WebAuthn assertion
     * validation and is sound.
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
            .'assertion signature. Use verifyLoginChallenge(), which runs the full WebAuthn '
            .'assertion validation (challenge, origin, RP ID, signature, counter).'
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
     *
     * Returns PublicKeyCredentialCreationOptionsJSON: `challenge`, `user.id`
     * and `excludeCredentials[].id` are base64url strings. The full options
     * are kept in the session for registerCredential().
     */
    public function generateRegistrationChallenge(TwoFaInterface $user): array
    {
        $owner = $this->passkeyOwner($user);

        try {
            $options = $this->ceremony()->registrationOptions($owner);

            Log::info('[Passkey] Registration challenge generated', [
                'user_id' => $user->getId(),
            ]);

            return $options;
        } catch (Throwable $e) {
            Log::error('[Passkey] Registration challenge generation error', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Generate WebAuthn authentication challenge
     *
     * Same options as generateLoginChallenge()'s `publicKey`, stored in the
     * session the same way. Core does not route a 2FA-step passkey
     * verification; see verifyAssertion().
     */
    public function generateAuthenticationChallenge(TwoFaInterface $user): array
    {
        return $this->ceremony()->verificationOptions($this->passkeyOwner($user));
    }

    /**
     * The users passkeys can be registered for must implement PasskeyUser.
     *
     * Core's Member does; a plugin user model that stores passkeys has to as
     * well (see DixlaseUsersUser).
     */
    private function passkeyOwner(TwoFaInterface $user): PasskeyUser
    {
        if (! $user instanceof PasskeyUser) {
            throw new LogicException(sprintf(
                '%s must implement %s to use passkeys.',
                $user::class,
                PasskeyUser::class
            ));
        }

        return $user;
    }

    private function ceremony(): PasskeyCeremony
    {
        return app(PasskeyCeremony::class);
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
     * The real implementation is verifyLoginChallenge(), which runs the full
     * WebAuthn assertion validation. There is no reason to keep a
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
