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

namespace App\Enums;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Trust level for plugins and themes
 *
 * Trust level represents the reliability of origin and distribution channel
 * Evaluates signing key issuer, distribution channel, author authentication, etc.
 *
 * Difference from Health:
 * - Trust: "where it came from"
 * - Health: "current state of contents"
 *
 * Examples:
 * - Trust: Official / Health: NeedsAttention → suspected tampering even if official
 * - Trust: Local / Health: Healthy → integrity OK even if self-made
 */
enum PluginTrustLevel: string
{
    /**
     * Official - Distributed by official Dixlase
     */
    case Official = 'official';

    /**
     * Verified - Distributed by verified publisher
     */
    case Verified = 'verified';

    /**
     * Partner - Distributed by Dixlase partner
     */
    case Partner = 'partner';

    /**
     * Community - Unverified distributor
     */
    case Community = 'community';

    /**
     * Local - Manual installation/local development
     */
    case Local = 'local';

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins/index.trust_level.'.$this->value;
    }

    /**
     * Get label
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return __($this->translationKey().'_description');
    }

    /**
     * Get CSS class (for badge)
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Official => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            self::Verified => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::Partner => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            self::Community => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            self::Local => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Official => 'fas fa-crown',
            self::Verified => 'fas fa-check-circle',
            self::Partner => 'fas fa-handshake',
            self::Community => 'fas fa-users',
            self::Local => 'fas fa-laptop-code',
        };
    }

    /**
     * Get color name
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Official => 'purple',
            self::Verified => 'green',
            self::Partner => 'blue',
            self::Community => 'gray',
            self::Local => 'gray',
        };
    }

    /**
     * Determine trust level from signature verification result
     *
     * Downgrade trust level if signature is invalid:
     * - unsigned/pending_verification → Local
     * - invalid/expired/error → Community
     * - valid → calculate correct Trust Level with fromSignatureType()
     */
    public static function fromSignatureVerification(?string $signatureType, string $signatureStatus): self
    {
        if ($signatureStatus !== 'valid') {
            return match ($signatureStatus) {
                'unsigned', 'pending_verification' => self::Local,
                default => self::Community, // invalid, expired, error, unknown_key
            };
        }

        return self::fromSignatureType($signatureType);
    }

    /**
     * Determine trust level from signature type
     */
    public static function fromSignatureType(?string $signatureType): self
    {
        return match ($signatureType) {
            'official' => self::Official,
            'verified' => self::Verified,
            'partner' => self::Partner,
            'community' => self::Community,
            default => self::Local,
        };
    }

    /**
     * Get trust level priority (higher value means higher trust)
     */
    public function priority(): int
    {
        return match ($this) {
            self::Official => 100,
            self::Verified => 80,
            self::Partner => 70,
            self::Community => 30,
            self::Local => 10,
        };
    }

    /**
     * Whether recommended for production environment
     */
    public function isProductionRecommended(): bool
    {
        return match ($this) {
            self::Official, self::Verified, self::Partner => true,
            self::Community, self::Local => false,
        };
    }

    /**
     * Get all levels
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get only levels recommended for production environment
     */
    public static function productionRecommended(): array
    {
        return array_filter(self::cases(), fn ($level) => $level->isProductionRecommended());
    }
}
