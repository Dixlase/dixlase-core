<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services\Csp;

/**
 * CSP Nonce Generator
 *
 * Service for generating and managing a unique nonce per request
 * The nonce is used to allow inline scripts/styles
 */
class CspNonceGenerator
{
    /**
     * Nonce value for the current request
     */
    protected ?string $nonce = null;

    /**
     * Byte length when generating nonce
     */
    protected int $nonceLength;

    public function __construct()
    {
        $this->nonceLength = config('csp.nonce_length', 16);
    }

    /**
     * Get the nonce for the current request
     *
     * Generate a new one if not yet generated
     * Always returns the same nonce within the same request
     */
    public function getNonce(): string
    {
        if ($this->nonce === null) {
            $this->nonce = $this->generateNonce();
        }

        return $this->nonce;
    }

    /**
     * Generate a new nonce
     *
     * Generate a Base64 encoded string from cryptographically secure random bytes
     */
    protected function generateNonce(): string
    {
        $bytes = random_bytes($this->nonceLength);

        return base64_encode($bytes);
    }

    /**
     * Reset the nonce
     *
     * Not normally used, but can be used when needed for testing, etc.
     */
    public function resetNonce(): void
    {
        $this->nonce = null;
    }

    /**
     * Get the nonce string for CSP directive
     *
     * Example: 'nonce-abc123...'
     */
    public function getNonceDirective(): string
    {
        return "'nonce-".$this->getNonce()."'";
    }

    /**
     * Get the nonce string for HTML attribute
     *
     * Example: nonce="abc123..."
     */
    public function getNonceAttribute(): string
    {
        return 'nonce="'.$this->getNonce().'"';
    }
}
