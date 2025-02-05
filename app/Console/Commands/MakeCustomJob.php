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
use App\Services\FileGenerator;
use App\Console\Traits\MakeJobTrait;



class MakeCustomJob extends Command
{
    use MakeJobTrait;

    /**
     * コマンド署名
     */
    protected $signature = 'make:custom:job
        {name : The job class name (with optional subfolders, e.g. Admin/MyJob)}
        {--force : Overwrite if job already exists}
        {--sync : Indicates that the job should be synchronous}';

    protected $description = 'Create a new job in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --force, --sync
        $force = (bool) $this->option('force');
        $sync  = (bool) $this->option('sync');

        // 3) Traitのメソッドを呼ぶ
        $this->makeFile($className, $subDirs, $force, $sync);

        return 0;
    }

    /**
     * ディレクトリ/名前空間
     */
    protected function getJobDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Jobs');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getJobNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Jobs';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
