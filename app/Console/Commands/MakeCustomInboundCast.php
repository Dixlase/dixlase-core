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
use App\Console\Traits\MakeInboundCastTrait;

class MakeCustomInboundCast extends Command
{
    use MakeInboundCastTrait;

    protected $signature = 'make:custom:inbound-cast
        {name : The inbound cast class name (optionally with subfolders, e.g. Admin/MyInboundCast)}
        {--force : Overwrite if the cast already exists}';

    protected $description = 'Create a new inbound cast (CastsInboundAttributes) in the custom directory';

    protected FileGenerator $fileGenerator;

    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) --force
        $force = (bool)$this->option('force');

        // 3) trait method
        $this->makeFile($className, $subDirs, $force);

        return 0;
    }

    /**
     * (B)パターン: getInboundCastDirectory/Namespace
     */
    protected function getInboundCastDirectory(array $subDirs): string
    {
        $base = base_path('custom/app/Casts/Inbound');
        // 例: inbound 専用にサブフォルダを分けてもよいし、
        //     'custom/app/Casts' 下にまとめてもOK
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getInboundCastNamespace(array $subDirs): string
    {
        $base = 'Custom\\App\\Casts\\Inbound';
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
