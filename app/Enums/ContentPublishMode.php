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

declare(strict_types=1);

namespace App\Enums;

/**
 * How file-stored content reaches the public site
 *
 * Each owner of file-stored content (the core front page, an extension's
 * pages) chooses one in its own settings. See
 * {@see \App\Contracts\Content\PublishesFileContent}.
 */
enum ContentPublishMode: string
{
    /**
     * Render the published copy kept in the database. The file is a working
     * draft; a change to it reaches the site only when someone publishes it
     * from the admin. The default.
     */
    case ADMIN = 'admin';

    /**
     * Render the file as it is on disk. A change made outside the admin is
     * taken in on the next read and recorded in the audit log.
     */
    case FILE_SAVE = 'file_save';

    /**
     * The mode used when the owner has not chosen one
     */
    public static function default(): self
    {
        return self::ADMIN;
    }

    /**
     * Resolve a stored setting value, falling back to the default
     */
    public static function fromSetting(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::default()) : self::default();
    }

    /**
     * Options for a radio card group, keyed by value
     *
     * @return list<array{value: string, label: string, description: string, icon: string}>
     */
    public static function options(): array
    {
        return [
            [
                'value' => self::ADMIN->value,
                'label' => __('common.content_publish_mode.admin'),
                'description' => __('common.content_publish_mode.admin_description'),
                'icon' => 'fas fa-user-check',
            ],
            [
                'value' => self::FILE_SAVE->value,
                'label' => __('common.content_publish_mode.file_save'),
                'description' => __('common.content_publish_mode.file_save_description'),
                'icon' => 'fas fa-file-pen',
            ],
        ];
    }
}
