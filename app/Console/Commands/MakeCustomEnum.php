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
use App\Services\FileGenerator;
use App\Console\Traits\MakeEnumTrait;

class MakeCustomEnum extends Command
{
    use MakeEnumTrait;

    protected $signature = 'make:custom:enum
        {name : The enum class name (optionally with subfolders, e.g. Admin/MyEnum)}
        {--backed= : Create a backed enum with the given type (e.g. string or int)}
        {--force : Overwrite if the enum class already exists}';

    protected $description = 'Create a new enum class in the custom directory (PHP 8.1+)';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse subdirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --backed=?
        $backedType = $this->option('backed') ?: null;

        // 3) --force
        $force = (bool)$this->option('force');

        // 4) Trait method
        $this->makeFile($className, $subDirs, $force, $backedType);

        return 0;
    }

    /**
     * (B)パターン: getEnumDirectory/Namespace
     */
    protected function getEnumDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Enums');
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getEnumNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Enums';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
