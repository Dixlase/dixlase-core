<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

declare(strict_types=1);

namespace App\Presenters\Admin;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * @api リビジョン差分のサイドバイサイド表示用 Presenter
 *
 * 任意の 2 つのテキストを行単位で比較し、Git 風に左右に並べた表示行の配列を返す。
 * 各行は ['status' => 'same|removed|added|changed', 'left' => ?string, 'right' => ?string]
 */
class RevisionDiffPresenter
{
    /**
     * 2 つのテキストを比較し、サイドバイサイド表示用の行配列を返す。
     *
     * @return list<array{status: string, left: ?string, right: ?string}>
     */
    public function buildSideBySide(string $from, string $to): array
    {
        $differ = new Differ(new UnifiedDiffOutputBuilder(''));
        $tokens = $differ->diffToArray($from, $to);

        $rows = [];
        $pendingRemoved = [];
        $pendingAdded = [];

        $flush = function () use (&$rows, &$pendingRemoved, &$pendingAdded): void {
            $count = max(count($pendingRemoved), count($pendingAdded));
            for ($i = 0; $i < $count; $i++) {
                $left = $pendingRemoved[$i] ?? null;
                $right = $pendingAdded[$i] ?? null;
                if ($left !== null && $right !== null) {
                    $rows[] = ['status' => 'changed', 'left' => $left, 'right' => $right];
                } elseif ($left !== null) {
                    $rows[] = ['status' => 'removed', 'left' => $left, 'right' => null];
                } else {
                    $rows[] = ['status' => 'added', 'left' => null, 'right' => $right];
                }
            }
            $pendingRemoved = [];
            $pendingAdded = [];
        };

        foreach ($tokens as [$line, $type]) {
            if ($type === Differ::REMOVED) {
                $pendingRemoved[] = $this->trimEol($line);
            } elseif ($type === Differ::ADDED) {
                $pendingAdded[] = $this->trimEol($line);
            } else {
                $flush();
                $rows[] = ['status' => 'same', 'left' => $this->trimEol($line), 'right' => $this->trimEol($line)];
            }
        }
        $flush();

        return $rows;
    }

    /**
     * 差分があるかどうかを判定する。
     */
    public function hasChanges(string $from, string $to): bool
    {
        return $from !== $to;
    }

    private function trimEol(string $line): string
    {
        return rtrim($line, "\r\n");
    }
}
