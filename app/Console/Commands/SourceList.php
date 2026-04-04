<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

use App\Models\ExtensionSource;
use Illuminate\Console\Command;

class SourceList extends Command
{
    protected $signature = 'dls:source:list';

    protected $description = 'List all registered extension sources';

    public function handle(): int
    {
        $sources = ExtensionSource::query()->orderBy('priority')->get();

        if ($sources->isEmpty()) {
            $this->info('No extension sources registered.');

            return self::SUCCESS;
        }

        $rows = $sources->map(fn (ExtensionSource $source) => [
            $source->id,
            $source->name,
            $source->type,
            $source->owner ?? '-',
            $source->is_enabled ? 'Yes' : 'No',
            $source->is_official ? 'Official' : 'Third-party',
            $source->priority,
            $source->last_checked_at?->diffForHumans() ?? 'Never',
        ]);

        $this->table(
            ['ID', 'Name', 'Type', 'Owner', 'Enabled', 'Status', 'Priority', 'Last Check'],
            $rows
        );

        return self::SUCCESS;
    }
}
