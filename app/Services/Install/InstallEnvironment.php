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

namespace App\Services\Install;

/**
 * Tells the setup wizard how this copy of Dixlase was installed.
 *
 * The installers name the method in DIXLASE_INSTALL_METHOD: the Docker
 * installer sets "docker", the one-line installer "oneliner", and
 * `composer create-project` writes "composer". A release ZIP or a
 * `git clone` leaves it unset. When it is unset, the wizard falls back
 * to resolving the Docker service host names (`mysql`, `mailpit`), which
 * is how it detected Docker before the variable existed.
 */
class InstallEnvironment
{
    public const METHOD_DOCKER = 'docker';

    public const METHOD_ONELINER = 'oneliner';

    public const METHOD_COMPOSER = 'composer';

    private const KNOWN_METHODS = [
        self::METHOD_DOCKER,
        self::METHOD_ONELINER,
        self::METHOD_COMPOSER,
    ];

    /**
     * The install method the installer declared, or null when it declared
     * none (or a value this version does not know).
     */
    public function method(): ?string
    {
        $method = strtolower(trim((string) config('dixlase.install_method', '')));

        return in_array($method, self::KNOWN_METHODS, true) ? $method : null;
    }

    /**
     * Whether Dixlase runs under the Docker installer, which provides a
     * MySQL container.
     */
    public function isDocker(): bool
    {
        $method = $this->method();
        if ($method !== null) {
            return $method === self::METHOD_DOCKER;
        }

        return $this->hostResolves('mysql');
    }

    /**
     * Whether a Mailpit container is available to send test mail to.
     */
    public function hasMailpit(): bool
    {
        $method = $this->method();
        if ($method !== null) {
            return $method === self::METHOD_DOCKER;
        }

        return $this->hostResolves('mailpit');
    }

    /**
     * Where the browser opens the Mailpit web interface.
     *
     * Only the installer knows the host port Mailpit is published on, so it
     * may name the URL in DIXLASE_MAILPIT_URL. Otherwise the URL is the host
     * the wizard is being viewed on with Mailpit's own default port, 8025.
     * Anything but an http(s) URL is ignored, since the value becomes a link.
     */
    public function mailpitUrl(string $scheme, string $host): string
    {
        $configured = trim((string) config('dixlase.mailpit_url', ''));
        $configuredScheme = strtolower((string) parse_url($configured, PHP_URL_SCHEME));
        if ($configured !== '' && in_array($configuredScheme, ['http', 'https'], true)
            && filter_var($configured, FILTER_VALIDATE_URL) !== false) {
            return rtrim($configured, '/');
        }

        $scheme = in_array(strtolower($scheme), ['http', 'https'], true) ? strtolower($scheme) : 'http';
        $host = $host !== '' ? $host : 'localhost';
        // An IPv6 literal needs brackets once a port follows it.
        if (str_contains($host, ':') && ! str_starts_with($host, '[')) {
            $host = '['.$host.']';
        }

        return $scheme.'://'.$host.':8025';
    }

    /**
     * True when the host name resolves to an address. gethostbyname()
     * returns its argument unchanged when it cannot resolve it.
     */
    protected function hostResolves(string $host): bool
    {
        return gethostbyname($host) !== $host;
    }
}
