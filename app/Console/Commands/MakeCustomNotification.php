<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Console\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeCustomNotification extends GeneratorCommand
{
    protected $signature = 'make:custom-notification {name}';
    protected $description = 'Create a new notification in the custom directory';

    protected function getStub()
    {
        return base_path('/stubs/notification.stub');  // 通知スタブ（オプション）
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Custom\Notifications';  // custom/notifications に作成
    }

    protected function buildClass($name)
    {
        $name = str_replace('Custom\\Notifications', '', $name);
        return parent::buildClass($name);
    }
}
