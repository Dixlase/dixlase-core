<?php

/**
 * This file is part of MySoftware.
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
use Illuminate\Support\Str;
use App\Services\FileGenerator;
use App\Console\Traits\MakeControllerTrait;

class MakeCustomController extends Command
{
    use MakeControllerTrait;

    /**
     * Artisan コマンド名と引数/オプション定義
     */
    protected $signature = 'make:custom:controller
        {name : The name of the controller}
        {--type=}
        {--force}
        {--invokable}
        {--model=}
        {--parent=}
        {--resource}
        {--requests}
        {--api}
        {--singleton}
        {--creatable}';

    protected $description = 'Create a new controller in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $path = str_replace('\\', '/', $this->argument('name'));
        $parts = explode('/', $path);
        $className = array_pop($parts);
        $subDirs   = $parts;

        // まとめたオプション
        $options = [
            'type'      => $this->option('type'),
            'force'     => $this->option('force'),
            'invokable' => $this->option('invokable'),
            'model'     => $this->option('model'),
            'parent'    => $this->option('parent'),
            'resource'  => $this->option('resource'),
            'requests'  => $this->option('requests'),
            'api'       => $this->option('api'),
            'singleton' => $this->option('singleton'),
            'creatable' => $this->option('creatable'),
        ];

        // "makeFile" (rename後) でコントローラ作成
        $this->makeFile($className, $subDirs, $options);

        return 0;
    }

    /**
     * @override from MakeFileTrait
     */
    protected function getDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Http/Controllers');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    /**
     * @override from MakeFileTrait
     */
    protected function getNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Http\\Controllers';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
