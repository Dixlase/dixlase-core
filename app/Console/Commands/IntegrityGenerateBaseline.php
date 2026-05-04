<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Console\Commands;

use App\Models\FileIntegrityAudit;
use App\Services\FileIntegrityService;
use Illuminate\Console\Command;

class IntegrityGenerateBaseline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:integrity:generate-baseline
                            {--force : 既存のベースラインを上書きする}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate baseline for core file integrity check';

    /**
     * Execute the console command.
     */
    public function handle(FileIntegrityService $service): int
    {
        $this->info(__('admin/command.integrity.generating_baseline'));

        // Check existing baseline
        if ($service->hasBaseline() && ! $this->option('force')) {
            $meta = $service->getBaselineMeta();
            $this->warn(__('admin/command.integrity.baseline_exists', [
                'date' => $meta['generated_at'] ?? 'unknown',
                'version' => $meta['app_version'] ?? 'unknown',
            ]));

            if (! $this->confirm(__('admin/command.integrity.overwrite_confirm'))) {
                $this->info(__('admin/command.integrity.cancelled'));

                return Command::SUCCESS;
            }
        }

        $this->output->write(__('admin/command.integrity.scanning_files'));

        // Generate baseline
        $baseline = $service->generateCoreBaseline();

        $this->info(' '.__('common.done'));

        // Save
        $this->output->write(__('admin/command.integrity.saving_baseline'));

        if ($service->saveBaselineArray($baseline)) {
            $this->info(' '.__('common.done'));

            // Record audit log
            FileIntegrityAudit::create([
                'scope' => FileIntegrityAudit::SCOPE_CORE,
                'trigger' => FileIntegrityAudit::TRIGGER_MANUAL,
                'initiated_by_type' => FileIntegrityAudit::INITIATED_BY_CLI,
                'status' => FileIntegrityAudit::STATUS_OK,
                'hash_algo' => 'sha256',
                'baseline_version' => $baseline['meta']['app_version'] ?? null,
                'total_files_scanned' => count($baseline['files']),
                'started_at' => now(),
                'finished_at' => now(),
                'duration_ms' => 0,
                'summary' => __('admin/command.integrity.baseline_generated'),
            ]);

            $this->newLine();
            $this->info(__('admin/command.integrity.baseline_success'));
            $this->table(
                [__('admin/command.integrity.item'), __('admin/command.integrity.value')],
                [
                    [__('admin/command.integrity.files_count'), count($baseline['files'])],
                    [__('admin/command.integrity.app_version'), $baseline['meta']['app_version'] ?? 'unknown'],
                    [__('admin/command.integrity.hash_algo'), $baseline['meta']['hash_algo']],
                    [__('admin/command.integrity.generated_at'), $baseline['meta']['generated_at']],
                ]
            );

            return Command::SUCCESS;
        }

        $this->error(__('admin/command.integrity.baseline_failed'));

        return Command::FAILURE;
    }
}
