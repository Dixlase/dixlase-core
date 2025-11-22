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
use App\Console\Traits\MakeValidatorTrait;
use App\Console\Traits\MakeThemeCommandTrait;
use App\Console\Traits\MakeLicenseTrait;
use App\Console\Traits\MakeFileTrait;

class MakeThemeValidator extends Command
{
    use MakeValidatorTrait;
    use MakeThemeCommandTrait;
    use MakeLicenseTrait;
    use MakeFileTrait;

    public function __construct()
    {
        $this->signature = $this->makeSignature(
            'dls:make:theme:validator ' . $this->getThemeCommandSignature(),
            $this->getAdditionalOptions()
        );
        $this->setDescription(__('command.make_theme.validator.description'));
        parent::__construct();
    }

    public function handle()
    {
        return $this->generateThemeFile(
            $this->argument('className'),
            $this->argument('themeName'),
            'validator',
            $this->options()
        );
    }

    /**
     * Validatorディレクトリのパスを取得
     *
     * @param array $subDirs サブディレクトリ
     * @return string
     */
    protected function getValidatorDirectory(array $subDirs): string
    {
        $themeName = $this->argument('themeName');
        $base = base_path("themes/{$themeName}/app/Validators");
        if ($subDirs) {
            $base .= '/' . implode('/', $subDirs);
        }
        return $base;
    }

    /**
     * Validatorの名前空間を取得
     *
     * @param array $subDirs サブディレクトリ
     * @return string
     */
    protected function getValidatorNamespace(array $subDirs): string
    {
        $themeName = $this->argument('themeName');
        $base = "Themes\\{$themeName}\\App\\Validators";
        if ($subDirs) {
            $base .= '\\' . implode('\\', $subDirs);
        }
        return $base;
    }

    /**
     * 追加オプションを取得
     *
     * @return array
     */
    protected function getAdditionalOptions(): array
    {
        return [];
    }
}
