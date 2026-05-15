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

namespace App\Console\Commands;

use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use Illuminate\Console\Command;

class SourceAdd extends Command
{
    protected $signature = 'dls:source:add
        {type : Source type (github, custom)}
        {--name= : Display name for the source}
        {--url= : Base API URL}
        {--owner= : Repository owner (GitHub org/user)}
        {--token= : Authentication token}
        {--priority=0 : Priority (lower = higher priority)}';

    protected $description = 'Add a new extension source';

    public function handle(ExtensionSourceManager $manager): int
    {
        $type = $this->argument('type');

        $availableTypes = $manager->getAvailableTypes();
        if (! isset($availableTypes[$type])) {
            $this->error("Unknown source type: {$type}. Available: ".implode(', ', array_keys($availableTypes)));

            return self::FAILURE;
        }

        $name = $this->option('name') ?? $this->ask('Source name');
        $url = $this->option('url') ?? $this->getDefaultUrl($type);
        $owner = $this->option('owner') ?? $this->askForOwner($type);
        $token = $this->option('token') ?? $this->secret('Authentication token (optional)');
        $priority = (int) $this->option('priority');

        $source = ExtensionSource::query()->create([
            'name' => $name,
            'type' => $type,
            'base_url' => $url,
            'owner' => $owner,
            'auth_token' => $token ?: null,
            'priority' => $priority,
            'is_enabled' => true,
        ]);

        $this->info("Source #{$source->id} '{$name}' ({$type}) added successfully.");

        if ($this->confirm('Test connection now?', true)) {
            $this->call('dls:source:test', ['id' => $source->id]);
        }

        return self::SUCCESS;
    }

    protected function getDefaultUrl(string $type): string
    {
        return match ($type) {
            'github' => config('extension-sources.github.api_base', 'https://api.github.com'),
            default => $this->ask('Base API URL'),
        };
    }

    protected function askForOwner(string $type): ?string
    {
        return match ($type) {
            'github' => $this->ask('GitHub organization/user', config('extension-sources.github.default_owner', 'Dixlase')),
            default => $this->ask('Owner (optional)'),
        };
    }
}
