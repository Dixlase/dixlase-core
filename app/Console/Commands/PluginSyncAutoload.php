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

namespace App\Console\Commands;

use App\Services\Extension\PluginAutoloadState;
use Illuminate\Console\Command;

/**
 * Rewrite composer.local.json from the plugins on disk and their enabled
 * state in the database, then regenerate the autoloader.
 *
 * Enabling, disabling, installing and uninstalling a plugin keep this in step
 * on their own. Run it once on a site that was upgraded from a version that
 * loaded every plugin's `autoload.files` regardless of its state, after a
 * deploy that changed the plugins table, or when a disable reported that the
 * autoloader could not be regenerated.
 *
 * `scripts/sync-local-autoload.php` cannot do this: it runs before the
 * framework exists and has no database, so it only reads the list this
 * command writes.
 */
class PluginSyncAutoload extends Command
{
    protected $signature = 'dls:plugin:sync-autoload';

    protected $description = 'Regenerate the plugin autoloader from the enabled state in the database, leaving the autoload files of plugins that are not enabled out';

    public function handle(PluginAutoloadState $autoloadState): int
    {
        $result = $autoloadState->synchronise();

        if ($result['reconciled'] === null) {
            $this->warn('Could not read the enabled plugins from the database; the existing list was kept.');
        }

        if ($result['withheld'] === []) {
            $this->line('Autoload files withheld: none');
        } else {
            $this->line('Autoload files withheld (plugin not enabled): '.implode(', ', $result['withheld']));
        }

        if (! $result['regenerated']) {
            $this->error('composer.local.json was written, but `composer dump-autoload` failed; see the log. Run `composer dump-autoload --no-scripts` once Composer works.');

            return self::FAILURE;
        }

        $this->info('Plugin autoload regenerated.');

        return $result['reconciled'] === null ? self::FAILURE : self::SUCCESS;
    }
}
