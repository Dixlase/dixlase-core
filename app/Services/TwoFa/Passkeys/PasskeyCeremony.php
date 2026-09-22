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

namespace App\Services\TwoFa\Passkeys;

use App\Models\Site;
use Illuminate\Http\Request;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\Exceptions\InvalidPasskeyException;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Support\WebAuthn;
use Throwable;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Runs WebAuthn registration and verification ceremonies on laravel/passkeys.
 *
 * Each ceremony has two halves split across requests: the options half
 * issues a challenge and stores the full options in the session, and the
 * second half pulls them back and validates the browser's response against
 * them. The stored options are tagged with the owner they were issued for,
 * so a challenge issued to one account can never be answered for another.
 *
 * The relying party (RP ID and allowed origins) is configured right before
 * each ceremony -- see configureRelyingParty().
 *
 * @internal Used by TwoFaPasskeyService; plugins go through that service.
 */
class PasskeyCeremony
{
    private const REGISTRATION_SESSION_KEY = 'dixlase_passkeys.registration_options';

    private const VERIFICATION_SESSION_KEY = 'dixlase_passkeys.verification_options';

    public function __construct(private readonly Request $request) {}

    /**
     * Issue registration options for a new passkey.
     *
     * @return array<array-key, mixed> PublicKeyCredentialCreationOptionsJSON for the browser
     */
    public function registrationOptions(PasskeyUser $user): array
    {
        $this->configureRelyingParty();

        $options = app(GenerateRegistrationOptions::class)($user);
        $this->remember(self::REGISTRATION_SESSION_KEY, $user, WebAuthn::toJson($options));

        return WebAuthn::toBrowserArray($options);
    }

    /**
     * Validate the browser's attestation and store the passkey.
     *
     * @param  array<string, mixed>  $credential  Serialized PublicKeyCredential from navigator.credentials.create()
     *
     * @throws InvalidPasskeyException
     */
    public function register(PasskeyUser $user, array $credential, string $name): Passkey
    {
        $this->configureRelyingParty();

        $options = WebAuthn::fromJson(
            $this->recall(self::REGISTRATION_SESSION_KEY, $user),
            PublicKeyCredentialCreationOptions::class
        );

        return app(StoreOwnedPasskey::class)($user, $name, $this->parseCredential($credential), $options);
    }

    /**
     * Issue verification options limited to the user's own passkeys.
     *
     * @return array<array-key, mixed> PublicKeyCredentialRequestOptionsJSON for the browser
     */
    public function verificationOptions(PasskeyUser $user): array
    {
        $this->configureRelyingParty();

        $options = app(GenerateVerificationOptions::class)($user);
        $this->remember(self::VERIFICATION_SESSION_KEY, $user, WebAuthn::toJson($options));

        return WebAuthn::toBrowserArray($options);
    }

    /**
     * Validate the browser's assertion for the given user.
     *
     * Checks the challenge, origin, RP ID hash, user presence/verification
     * flags, the signature and the signature counter, then stores the
     * updated counter and last_used_at.
     *
     * @param  array<string, mixed>  $credential  Serialized PublicKeyCredential from navigator.credentials.get()
     *
     * @throws InvalidPasskeyException
     */
    public function verify(PasskeyUser $user, array $credential): Passkey
    {
        $this->configureRelyingParty();

        $options = WebAuthn::fromJson(
            $this->recall(self::VERIFICATION_SESSION_KEY, $user),
            PublicKeyCredentialRequestOptions::class
        );

        return app(VerifyOwnedPasskey::class)($this->parseCredential($credential), $options, $user);
    }

    /**
     * Point laravel/passkeys at the relying party for this request.
     *
     * Operators can pin the RP ID and origins in config/fortify.php
     * (PASSKEYS_RP_ID / PASSKEYS_ALLOWED_ORIGINS). Without that, the RP ID is
     * the host this request was served on -- which lets one install serve
     * several site domains -- but only when that host is APP_URL's host or
     * the host of an active site. Any other Host header falls back to
     * APP_URL's host.
     *
     * The allow-list matters because nothing else validates the Host header
     * (TrustHosts is not enabled). Taking it as-is would let a reverse proxy
     * on another domain that forwards requests here with its own Host make
     * the RP ID its own domain: a session hijacked through that proxy could
     * then register a passkey scoped to the proxy's domain and keep signing
     * in through it later.
     */
    public function configureRelyingParty(): void
    {
        $appOrigin = $this->originOf((string) config('app.url'));
        $appHost = $appOrigin !== null ? (string) parse_url($appOrigin, PHP_URL_HOST) : '';

        $requestHost = strtolower($this->request->getHost());
        $servesRequestHost = $requestHost !== '' && in_array($requestHost, $this->knownHosts($appHost), true);

        $rpId = config('fortify.passkeys.relying_party_id');
        if (! is_string($rpId) || $rpId === '') {
            $rpId = $servesRequestHost ? $requestHost : $appHost;
        }

        $origins = config('fortify.passkeys.allowed_origins');
        if (is_string($origins)) {
            $origins = explode(',', $origins);
        }
        $origins = array_values(array_filter(
            array_map(fn ($origin) => is_string($origin) ? rtrim(trim($origin), '/') : '', (array) $origins),
            fn (string $origin) => $origin !== '',
        ));

        if ($origins === []) {
            if ($servesRequestHost) {
                $origins[] = $this->request->getSchemeAndHttpHost();
            }
            if ($appOrigin !== null) {
                $origins[] = $appOrigin;
            }
        }

        config([
            'passkeys.relying_party_id' => $rpId,
            'passkeys.allowed_origins' => array_values(array_unique($origins)),
        ]);
    }

    /**
     * Hosts this install serves: APP_URL's host plus every active site host.
     *
     * @return list<string>
     */
    private function knownHosts(string $appHost): array
    {
        $hosts = $appHost !== '' ? [strtolower($appHost)] : [];

        try {
            $siteHosts = Site::query()
                ->where('is_active', true)
                ->whereNotNull('host')
                ->pluck('host')
                ->all();
        } catch (Throwable) {
            // Sites table not available (e.g. during installation).
            $siteHosts = [];
        }

        foreach ($siteHosts as $host) {
            if (is_string($host) && trim($host) !== '') {
                $hosts[] = strtolower(trim($host));
            }
        }

        return array_values(array_unique($hosts));
    }

    /**
     * @throws InvalidPasskeyException
     */
    private function parseCredential(array $credential): PublicKeyCredential
    {
        try {
            return WebAuthn::fromJson(json_encode($credential, JSON_THROW_ON_ERROR), PublicKeyCredential::class);
        } catch (Throwable) {
            throw InvalidPasskeyException::make('Invalid credential format.');
        }
    }

    private function remember(string $key, PasskeyUser $user, string $options): void
    {
        $this->request->session()->put($key, [
            'owner' => $this->ownerTag($user),
            'options' => $options,
        ]);
    }

    /**
     * Pull the stored options; they are single-use.
     *
     * @throws InvalidPasskeyException
     */
    private function recall(string $key, PasskeyUser $user): string
    {
        $stored = $this->request->session()->pull($key);

        if (! is_array($stored)
            || ! is_string($stored['options'] ?? null)
            || ! hash_equals($this->ownerTag($user), (string) ($stored['owner'] ?? ''))) {
            throw InvalidPasskeyException::make('Passkey session expired. Please try again.');
        }

        return $stored['options'];
    }

    private function ownerTag(PasskeyUser $user): string
    {
        return $user::class.'#'.(string) $user->getKey();
    }

    private function originOf(string $url): ?string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        return $parts['scheme'].'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
