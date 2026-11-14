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

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\FileIntegrityAudit;
use App\Services\AuditService;
use App\Services\FileIntegrityService;
use App\Services\Security\IntegrityKeyring;
use App\Services\Security\IntegritySeal;
use Illuminate\Console\Command;

class IntegrityGenerateBaseline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:integrity:generate-baseline
                            {--force : Replace an existing baseline}
                            {--reason= : Why the current files are the expected ones; required with --force, recorded in the audit log}';

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
        $this->info(__('admin/command/integrity.generating_baseline'));

        // Replacing a baseline accepts whatever is on disk now as genuine, so
        // it is never a confirmation prompt away: it needs --force and a
        // reason, and it is recorded in the audit log (#803).
        $replacing = $service->hasBaseline();
        $reason = trim((string) $this->option('reason'));

        if ($replacing && ! $this->option('force')) {
            $meta = $service->getBaselineMeta();
            $this->error(__('admin/command/integrity.force_required', [
                'date' => $meta['generated_at'] ?? 'unknown',
                'version' => $meta['app_version'] ?? 'unknown',
            ]));

            return Command::FAILURE;
        }

        if ($replacing && $reason === '') {
            $this->error(__('admin/command/integrity.reason_required'));

            return Command::FAILURE;
        }

        $this->output->write(__('admin/command/integrity.scanning_files'));

        // Generate baseline
        $baseline = $service->generateCoreBaseline();

        $this->info(' '.__('common.done'));

        // Save
        $this->output->write(__('admin/command/integrity.saving_baseline'));

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
                'summary' => __('admin/command/integrity.baseline_generated'),
            ]);

            $sealStatus = $service->baselineSealStatus();

            if ($replacing) {
                app(AuditService::class)->log([
                    'action' => AuditLog::ACTION_INTEGRITY_BASELINE_REGENERATED,
                    'category' => AuditLog::getCategoryForAction(AuditLog::ACTION_INTEGRITY_BASELINE_REGENERATED),
                    'severity' => AuditLog::getDefaultSeverity(AuditLog::ACTION_INTEGRITY_BASELINE_REGENERATED),
                    'context' => [
                        'reason' => $reason,
                        'sealed' => $sealStatus === IntegritySeal::VALID,
                        'triggered_by' => 'cli',
                    ],
                ]);
            }

            $this->newLine();
            $this->info(__('admin/command/integrity.baseline_success'));
            $this->table(
                [__('admin/command/integrity.item'), __('admin/command/integrity.value')],
                [
                    [__('admin/command/integrity.files_count'), count($baseline['files'])],
                    [__('admin/command/integrity.app_version'), $baseline['meta']['app_version'] ?? 'unknown'],
                    [__('admin/command/integrity.hash_algo'), $baseline['meta']['hash_algo']],
                    [__('admin/command/integrity.generated_at'), $baseline['meta']['generated_at']],
                ]
            );
            $keyVersion = app(IntegrityKeyring::class)->currentVersion();
            $this->line($sealStatus === IntegritySeal::VALID && $keyVersion !== null
                ? __('admin/command/integrity.baseline_sealed_with', ['version' => $keyVersion])
                : __('admin/command/integrity.baseline_not_sealed'));

            return Command::SUCCESS;
        }

        $this->error(__('admin/command/integrity.baseline_failed'));

        return Command::FAILURE;
    }
}
