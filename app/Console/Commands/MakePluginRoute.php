<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
use Illuminate\Support\Str;
use App\Console\Traits\MakeRouteTrait;
use App\Services\FileGenerator;

class MakePluginRoute extends Command
{
    use MakeRouteTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:plugin:route {plugin} {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new route file for a plugin';

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $plugin = Str::studly($this->argument('plugin'));
        $slug = Str::slug(Str::snake($plugin));
        $name = Str::studly($this->argument('name'));

        $directory = base_path("plugins/{$plugin}/routes");
        $namespace = "Plugins\\{$plugin}\\Routes";

        // プレースホルダにプラグインスラッグを追加
        $placeholders = [
            '{{ pluginSlug }}' => $slug,
        ];

        // ルートファイルを作成
        $this->makeRouteFile($name, $directory, $namespace, 'routes.plugin.stub', $placeholders);
    }
}
