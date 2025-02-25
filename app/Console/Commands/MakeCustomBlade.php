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
use App\Services\FileGenerator;
use App\Console\Traits\MakeBladeTrait;


class MakeCustomBlade extends Command
{
    use MakeBladeTrait;

    protected $signature = 'make:custom:blade
        {file : The blade file name (e.g. admin/dashboard)}
        {--force : Overwrite if the blade file already exists}
        {--type=front : The type of Blade file (front/admin)}';

    protected $description = 'Create a new Blade template in the custom/views directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        $file  = $this->argument('file');

        $options = [
            'force' => (bool) $this->option('force'),
            'type' => $this->option('type'),
        ];

        if (!in_array($options['type'], ['front', 'admin'])) {
            $this->error("Invalid type: '{$options['type']}'. Choose 'front' or 'admin'.");
            return 1;
        }

        $this->makeFile($file, [], $options);

        return 0;
    }

    /**
     * Blade ファイルの保存先
     */
    protected function getDirectory(array $subDirs): string
    {
        $base = base_path('custom/resources/views');
        if (!empty($subDirs)) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getNamespace(array $subDirs): string
    {
        return ''; // Blade ファイルにはネームスペース不要
    }
}
