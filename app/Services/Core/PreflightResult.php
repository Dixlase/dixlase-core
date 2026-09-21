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

namespace App\Services\Core;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Outcome of one CorePreflightChecker pass: an ordered list of named
 * checks, each ok / warn / fail. Only `fail` blocks the update; `warn`
 * is printed to the update log so an operator sees it.
 */
final class PreflightResult
{
    public const OK = 'ok';

    public const WARN = 'warn';

    public const FAIL = 'fail';

    /** @var list<array{name: string, status: string, message: string}> */
    private array $checks = [];

    public function add(string $name, string $status, string $message): self
    {
        $this->checks[] = ['name' => $name, 'status' => $status, 'message' => $message];

        return $this;
    }

    public function merge(self $other): self
    {
        foreach ($other->checks() as $check) {
            $this->checks[] = $check;
        }

        return $this;
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function checks(): array
    {
        return $this->checks;
    }

    public function failed(): bool
    {
        return $this->failures() !== [];
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function failures(): array
    {
        return array_values(array_filter($this->checks, static fn (array $c): bool => $c['status'] === self::FAIL));
    }

    /**
     * @return list<array{name: string, status: string, message: string}>
     */
    public function warnings(): array
    {
        return array_values(array_filter($this->checks, static fn (array $c): bool => $c['status'] === self::WARN));
    }

    /**
     * One line per check, for the update log / CLI transcript.
     *
     * @return list<string>
     */
    public function lines(): array
    {
        return array_map(
            static fn (array $c): string => sprintf('[preflight] %-4s %s: %s', strtoupper($c['status']), $c['name'], $c['message']),
            $this->checks
        );
    }

    /**
     * The failures joined into one sentence, for the exception message and
     * core_releases.update_failure_reason (shown in the admin panel).
     */
    public function failureSummary(): string
    {
        return implode(' / ', array_map(
            static fn (array $c): string => $c['name'].': '.$c['message'],
            $this->failures()
        ));
    }
}
