<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace App\Services\Extension;

/**
 * Records front-end asset builds that failed during the current request.
 *
 * The admin panel installs and updates extensions through Artisan::call(),
 * which buffers the command's console output and discards it. A failed
 * `npm run build` therefore left no trace: the install reported success
 * and the extension ran without its JS / CSS. BuildsExtensionAssets
 * records each failed step here, and the controller that invoked the
 * command reads it back to warn the operator.
 *
 * Registered as a scoped binding, so each request starts empty.
 */
class ExtensionAssetBuildReport
{
    /**
     * Failed build steps keyed by the extension directory.
     *
     * @var array<string, array{command: string, exit_code: int|null}>
     */
    private array $failures = [];

    public function recordFailure(string $extensionPath, string $command, ?int $exitCode): void
    {
        $this->failures[$this->key($extensionPath)] = [
            'command' => $command,
            'exit_code' => $exitCode,
        ];
    }

    /**
     * Forget an earlier failure, so a successful rebuild in the same
     * request is not reported as failed.
     */
    public function clear(string $extensionPath): void
    {
        unset($this->failures[$this->key($extensionPath)]);
    }

    /**
     * @return array{command: string, exit_code: int|null}|null
     */
    public function failureFor(string $extensionPath): ?array
    {
        return $this->failures[$this->key($extensionPath)] ?? null;
    }

    private function key(string $extensionPath): string
    {
        return rtrim(str_replace('\\', '/', $extensionPath), '/');
    }
}
