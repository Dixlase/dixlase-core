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

return [
    'checking' => 'Checking :name against the extension security settings...',
    'blocked' => ':name is blocked by the extension security settings (health score or signature), so it was not :action. The admin panel refuses it too.',
    'blocked_hint' => 'Run `php artisan dls::kind:audit :slug` to see the findings. --force does not override a block.',
    'confirmation_required' => ':name has health issues the admin panel asks you to confirm (:level), so it was not :action.',
    'confirmation_hint' => 'Review them with `php artisan dls::kind:audit :slug`, then re-run with --force to proceed anyway.',
    'forced' => 'WARNING: proceeding with :name despite its health issues (:level) because --force was passed.',
    'level' => [
        'warning' => 'minor issues',
        'ack' => 'significant issues',
    ],
    'action' => [
        'installed' => 'installed',
        'enabled' => 'enabled',
        'switched' => 'made the active theme',
    ],
];
