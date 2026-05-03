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

namespace App\Enums;

/**
 * Content status
 * Manages publication state for pages, blog posts, etc.
 */
enum ContentStatus: int
{
    case DRAFT = 0;       // draft
    case PUBLISHED = 1;   // public
    case SCHEDULED = 2;   // scheduled

    /**
     * Get legacy string identifier (slug)
     *
     * Used to maintain compatibility with JS/Alpine.js.
     * Use this method when passing to form values or JS.
     */
    public function slug(): string
    {
        return match ($this) {
            self::DRAFT => 'draft',
            self::PUBLISHED => 'published',
            self::SCHEDULED => 'scheduled',
        };
    }

    /**
     * Get Enum instance from slug string
     *
     * @throws \ValueError When slug is not found
     */
    public static function fromSlug(string $slug): self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        throw new \ValueError("\"$slug\" is not a valid slug for ".self::class);
    }

    /**
     * Get Enum instance from slug string (returns null on failure)
     */
    public static function tryFromSlug(string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    /**
     * Get display name of status
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => __('components/ui-status-badge.draft'),
            self::PUBLISHED => __('components/ui-status-badge.published'),
            self::SCHEDULED => __('components/ui-status-badge.scheduled'),
        };
    }

    /**
     * Get description of status
     */
    public function description(): string
    {
        return match ($this) {
            self::DRAFT => __('components/ui-status-badge.draft_description'),
            self::PUBLISHED => __('components/ui-status-badge.published_description'),
            self::SCHEDULED => __('components/ui-status-badge.scheduled_description'),
        };
    }

    /**
     * Get CSS class (for status badge)
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PUBLISHED => 'green',
            self::SCHEDULED => 'yellow',
        };
    }

    /**
     * Get all statuses as array
     */
    public static function toArray(): array
    {
        return [
            self::DRAFT->slug() => self::DRAFT->label(),
            self::PUBLISHED->slug() => self::PUBLISHED->label(),
            self::SCHEDULED->slug() => self::SCHEDULED->label(),
        ];
    }

    /**
     * Whether the status is publishable
     */
    public function isPublishable(): bool
    {
        return match ($this) {
            self::PUBLISHED, self::SCHEDULED => true,
            self::DRAFT => false,
        };
    }

    /**
     * Get labeled options (for forms)
     */
    public static function optionsWithDescription(): array
    {
        return [
            self::DRAFT->slug() => [
                'label' => self::DRAFT->label(),
                'description' => self::DRAFT->description(),
            ],
            self::PUBLISHED->slug() => [
                'label' => self::PUBLISHED->label(),
                'description' => self::PUBLISHED->description(),
            ],
            self::SCHEDULED->slug() => [
                'label' => self::SCHEDULED->label(),
                'description' => self::SCHEDULED->description(),
            ],
        ];
    }
}
