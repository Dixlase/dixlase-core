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
use App\Console\Traits\MakeTestTrait;

class MakeCustomTest extends Command
{
    use MakeTestTrait;

    protected $signature = 'make:custom:test
        {name : The test class name (with optional subfolders, e.g. Admin/MyExampleTest)}
        {--force : Overwrite if test already exists}
        {--unit : Create a unit test}
        {--pest : Create a Pest test}
        {--phpunit : Create a PHPUnit test (disable Pest even if installed)}';

    protected $description = 'Create a new test in the custom directory';

    protected FileGenerator $fileGenerator;

    /**
     * コンストラクタ
     */
    public function __construct(FileGenerator $fileGenerator)
    {
        parent::__construct();
        $this->fileGenerator = $fileGenerator;
    }

    public function handle()
    {
        // 1) parse subDirs + className
        [$subDirs, $className] = $this->fileGenerator->parseClassName($this->argument('name'));

        // 2) options
        $force   = (bool) $this->option('force');
        $isUnit  = (bool) $this->option('unit');
        $usingPest = $this->usingPest();

        // 3) Traitの makeFile(...) 呼び出し
        $this->makeFile($className, $subDirs, $force, $isUnit, $usingPest);

        return 0;
    }

    /**
     * Pest を使うかどうかを判定
     *   --phpunit => false
     *   --pest => true
     *   それ以外 => pest がインストールされているか
     */
    protected function usingPest(): bool
    {
        if ($this->option('phpunit')) {
            return false;
        }

        if ($this->option('pest')) {
            return true;
        }

        // pestがあるかどうか簡易チェック
        return function_exists('\Pest\version') && file_exists(base_path('tests/Pest.php'));
    }

    /**
     * (B)パターンで getTestDirectory/Namespace
     */
    protected function getTestDirectory(array $subDirs): string
    {
        // 例: "custom/tests/Feature"
        // もし --unit を見て "custom/tests/Unit" に分岐させたい場合は
        // handle() でフラグを保存して使うか、ここで $this->option('unit') 参照してもOK
        $testType = $this->option('unit') ? 'Unit' : 'Feature';

        $base = base_path("custom/tests/{$testType}");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    protected function getTestNamespace(array $subDirs): string
    {
        // 同様に: "Custom\Tests\Feature" or "Custom\Tests\Unit"
        $testType = $this->option('unit') ? 'Unit' : 'Feature';

        $base = "Custom\\Tests\\{$testType}";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }
}
