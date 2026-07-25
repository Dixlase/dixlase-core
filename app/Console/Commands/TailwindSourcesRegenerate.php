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

use App\Services\Tailwind\PluginSourceAggregator;
use Illuminate\Console\Command;

/**
 * Regenerate resources/src/common/css/dixlase-tailwind-plugin-sources.css
 * from the current set of enabled plugins.
 *
 * The plugin lifecycle commands (install / enable / disable / update
 * / uninstall) already invoke the aggregator automatically; this
 * command is for two cases:
 *
 *   - Fresh installs, before the first asset build (the file does
 *     not exist yet and the theme's Vite build would otherwise fail
 *     trying to @import it).
 *   - Manual recovery when the file has been accidentally edited or
 *     deleted, or when plugin.json was edited on disk without going
 *     through the lifecycle commands.
 */
class TailwindSourcesRegenerate extends Command
{
    protected $signature = 'dls:tailwind:regenerate-plugin-sources';

    protected $description = 'Regenerate the Tailwind plugin-source aggregator CSS from enabled plugins';

    public function handle(PluginSourceAggregator $aggregator): int
    {
        $result = $aggregator->regenerate();

        $this->info(sprintf(
            'Aggregated %d source path(s) from %d enabled plugin(s).',
            $result['source_count'],
            $result['plugin_count'],
        ));
        $this->line("  Output: {$result['path']}");

        return self::SUCCESS;
    }
}
