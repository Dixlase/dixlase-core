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

namespace App\Services\Plugin\Scanning;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ミドルウェア登録の検出パターン
 *
 * system.register_middleware を検出します。
 * Route::middleware() による「使用」と、pushMiddleware() による「登録」を区別します。
 */
class MiddlewareDetectionPattern extends DetectionPattern
{
    public function permissionKey(): string
    {
        return 'system.register_middleware';
    }

    public function filePatterns(): array
    {
        return ['app/Http/Middleware/*.php'];
    }

    public function regexPatterns(): array
    {
        return [
            '/\$this->app\[.*Router.*\]->pushMiddleware/i',
            '/->aliasMiddleware\s*\(/i',
            '/->pushMiddlewareToGroup\s*\(/i',
        ];
    }

    /**
     * Route::middleware() によるミドルウェアの「使用」は除外し、
     * 「登録」のみを検出する
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        // ルート定義でのmiddleware使用は「登録」ではない
        $trimmedLine = ltrim($line);
        if (preg_match('/^Route::/i', $trimmedLine) && str_contains($trimmedLine, 'middleware')) {
            return false;
        }

        // チェーンメソッドでの ->middleware('name') は「使用」
        if (preg_match('/->middleware\s*\(\s*[\'"]/i', $match)) {
            return false;
        }

        return true;
    }
}
