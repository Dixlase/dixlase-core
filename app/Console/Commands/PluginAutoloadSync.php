<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

use Illuminate\Console\Command;

class PluginAutoloadSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:autoload:sync {--cleanup}';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->description = __('command.plugin_autoload_sync.description');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. 同期 (PSR-4に新規プラグインを追加)
        $this->call('plugin:autoload:sync-only'); // syncPluginAutoload()

        // 2. --cleanup が指定されていれば、不要エントリ削除
        if ($this->option('cleanup')) {
            $this->call('plugin:autoload:cleanup'); // cleanupPluginAutoload()
        }

        // 3. 最後に composer dump-autoload
        exec('composer dump-autoload');

        $this->info(__('command.plugin_autoload_sync.success'));
        return Command::SUCCESS;
    }
}
