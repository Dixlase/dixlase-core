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
 * Plugin activation policy
 *
 * The action to take when activating a plugin or theme, resolved by
 * ExtensionEnableActionResolver:
 *
 * - Blocked: the security preset requires a signature and the extension
 *   has no verified signature, or its health status is above the
 *   preset's maximum
 * - Otherwise, by score: >= 90 Allowed, >= 70 WarningRequired, below 70
 *   or a critical issue AcknowledgementRequired
 */
enum PluginEnableAction: string
{
    /**
     * No issues → activate directly
     */
    case Allowed = 'allowed';

    /**
     * Minor issues → show warning and allow continuation
     */
    case WarningRequired = 'warning';

    /**
     * Significant issues → acknowledgement required
     */
    case AcknowledgementRequired = 'ack';

    /**
     * Not allowed under the current security settings → activation blocked
     */
    case Blocked = 'blocked';

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins/index.enable_action.'.$this->value;
    }

    /**
     * Get label
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Whether activation is allowed
     */
    public function isAllowed(): bool
    {
        return $this !== self::Blocked;
    }

    /**
     * Whether warning display is required
     */
    public function requiresWarning(): bool
    {
        return $this === self::WarningRequired || $this === self::AcknowledgementRequired;
    }

    /**
     * Whether acknowledgement is required
     */
    public function requiresAcknowledgement(): bool
    {
        return $this === self::AcknowledgementRequired;
    }
}
