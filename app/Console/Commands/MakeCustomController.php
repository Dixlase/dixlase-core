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
use App\Console\Traits\MakeControllerTrait;
use App\Console\Traits\MakeLicenseTrait;

class MakeCustomController extends Command
{
    use MakeControllerTrait;
    use MakeLicenseTrait;

    /**
     * Artisan コマンド名と引数/オプション定義
     */
    protected $signature = 'make:custom:controller
        {name : The name of the controller}
        {--scope=plain : The scope of the controller (admin, front, plain)}
        {--license= : Specify a license (e.g. gpl, mit, apache)}
        {--force}
        {--invokable}
        {--model=}
        {--parent=}
        {--resource}
        {--requests}
        {--api}
        {--singleton}
        {--creatable}
        {--license= : Specify a license for this file}';


    protected $description = 'Create a new controller in the custom directory';

    protected string $controllerRootType = 'Custom';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {

        $scope = $this->choice(
            'コントローラのスコープを選択してください',
            ['admin' => '管理画面用', 'front' => 'フロント用', 'plain' => 'プレーン（共通）'],
            'plain'
        );


        $path = str_replace('\\', '/', $this->argument('name'));
        $parts = explode('/', $path);
        $className = array_pop($parts);
        $subDirs   = $parts;

        // まとめたオプション
        $options = [
            'scope'     => $scope,
            'force'     => $this->option('force'),
            'invokable' => $this->option('invokable'),
            'model'     => $this->option('model'),
            'parent'    => $this->option('parent'),
            'resource'  => $this->option('resource'),
            'requests'  => $this->option('requests'),
            'api'       => $this->option('api'),
            'singleton' => $this->option('singleton'),
            'creatable' => $this->option('creatable'),
            'license'   => $this->option('license') ?? 'gpl',
        ];


        $this->makeFile(
            $className,
            $subDirs,
            $options,
            'Custom',
            'Custom',
            []
        );

        return Command::SUCCESS;
    }
}
