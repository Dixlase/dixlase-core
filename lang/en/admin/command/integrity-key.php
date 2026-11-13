<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
    'env_missing' => 'No .env file at :path.',
    'current_invalid' => 'INTEGRITY_KEY in .env is not in the form <version>:base64:<key>. Fix or remove it, then run this command again.',
    'already_configured' => 'An integrity key is already configured (version :version).',
    'rotate_hint' => 'To replace it, run `php artisan dls:integrity:key --rotate`. The old key is kept so that what it signed still verifies.',
    'nothing_to_rotate' => 'No integrity key is configured yet. Run `php artisan dls:integrity:key` to create one.',
    'versions_exhausted' => 'No key version is left to use.',
    'show_intro' => 'Add these lines to .env (nothing was written):',
    'created' => 'Integrity key version :version written to .env. New audit log rows and daily seals are signed with it.',
    'rotated' => 'Integrity key version :version written to .env. The previous key was moved to INTEGRITY_PREVIOUS_KEYS; keep it there for as long as rows and seals signed with it are kept.',
    'config_cached' => 'The configuration is cached. Run `php artisan config:cache` so the new key takes effect.',
    'restart_hint' => 'Restart long-running processes (queue workers, the scheduler) so they read the new key.',
];
