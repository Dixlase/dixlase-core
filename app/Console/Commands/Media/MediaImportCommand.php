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

declare(strict_types=1);

namespace App\Console\Commands\Media;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Services\Media\MediaImporter;
use App\Services\Media\MediaImportResult;
use Illuminate\Console\Command;

/**
 * Import local files into the media library (#902)
 *
 * The command-line counterpart of the admin upload, for a script or an
 * assistant that has prepared an article's images locally. Each file goes
 * through {@see MediaImporter}: the upload form's type and size rules, the
 * upload action's security checks, and an audit entry under the member
 * named with --as. `--json` prints each file's media ID and URL so the
 * caller can put them into the content.
 */
class MediaImportCommand extends Command
{
    protected $signature = 'dls:media:import
                            {paths* : The files to import}
                            {--as= : The member the uploads are recorded under (ID or e-mail address)}
                            {--json : Print the results as JSON}';

    protected $description = 'Import local files into the media library';

    public function handle(MediaImporter $importer): int
    {
        $member = $this->resolveMember();
        if ($member === null) {
            return self::FAILURE;
        }

        /** @var list<string> $paths */
        $paths = array_values(array_filter((array) $this->argument('paths'), fn ($path): bool => (string) $path !== ''));
        if ($paths === []) {
            $this->error(__('admin/media/import.no_files'));

            return self::FAILURE;
        }

        $results = array_map(fn (string $path): MediaImportResult => $importer->import($path, $member), $paths);

        $this->option('json') ? $this->printJson($results) : $this->printTable($results);

        foreach ($results as $result) {
            if (! $result->succeeded()) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function resolveMember(): ?Member
    {
        $identifier = trim((string) $this->option('as'));
        if ($identifier === '') {
            $this->error(__('admin/media/import.member_required'));

            return null;
        }

        $member = ctype_digit($identifier)
            ? Member::find((int) $identifier)
            : Member::where('email', $identifier)->first();

        if (! $member instanceof Member) {
            $this->error(__('admin/media/import.member_not_found', ['identifier' => $identifier]));

            return null;
        }

        if ($member->status !== MemberStatus::Active) {
            $this->error(__('admin/media/import.member_inactive', ['identifier' => $identifier]));

            return null;
        }

        return $member;
    }

    /**
     * @param  list<MediaImportResult>  $results
     */
    private function printJson(array $results): void
    {
        $this->line((string) json_encode(
            array_map(fn (MediaImportResult $result): array => $result->toArray(), $results),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    /**
     * @param  list<MediaImportResult>  $results
     */
    private function printTable(array $results): void
    {
        $rows = [];
        $counts = ['imported' => 0, 'reused' => 0, 'failed' => 0];

        foreach ($results as $result) {
            $status = match (true) {
                ! $result->succeeded() => 'failed',
                $result->reused => 'reused',
                default => 'imported',
            };
            $counts[$status]++;

            $rows[] = [
                $result->source,
                __('admin/media/import.status_'.$status),
                $result->media?->id ?? '',
                $result->media !== null ? MediaImporter::pathOf($result->media) : (string) $result->error,
            ];
        }

        $this->table([
            __('admin/media/import.column_file'),
            __('admin/media/import.column_result'),
            __('admin/media/import.column_id'),
            __('admin/media/import.column_path'),
        ], $rows);

        foreach ($results as $result) {
            foreach ($result->warnings as $warning) {
                $this->warn($warning);
            }
        }

        $this->info(__('admin/media/import.summary', $counts));
    }
}
