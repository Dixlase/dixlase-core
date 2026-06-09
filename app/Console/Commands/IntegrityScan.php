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

use App\Mail\FileIntegrityAlertMail;
use App\Models\FileIntegrityAudit;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Services\FileIntegrityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class IntegrityScan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:integrity:scan
                            {--scope=core : スキャン対象 (core, plugin, theme, all)}
                            {--identifier= : プラグイン名またはテーマ名}
                            {--json : 結果をOutput in JSON format}
                            {--scheduled : スケジュール実行フラグ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan file integrity and detect tampering';

    /**
     * Execute the console command.
     */
    public function handle(FileIntegrityService $service): int
    {
        $scope = $this->option('scope');
        $outputJson = $this->option('json');

        if (! $outputJson) {
            $this->info(__('admin/command/integrity.starting_scan'));
            $this->newLine();
        }

        // Currently only Core is supported
        if ($scope !== 'core') {
            if (! $outputJson) {
                $this->warn(__('admin/command/integrity.scope_not_supported', ['scope' => $scope]));
                $this->info(__('admin/command/integrity.using_core_scope'));
            }
            $scope = 'core';
        }

        if (! $outputJson) {
            $this->output->write(__('admin/command/integrity.scanning'));
        }

        // Execute scan (TRIGGER_SCHEDULE for scheduled execution)
        $trigger = $this->option('scheduled')
            ? FileIntegrityAudit::TRIGGER_SCHEDULE
            : FileIntegrityAudit::TRIGGER_MANUAL;

        $audit = $service->scanCore(
            $trigger,
            FileIntegrityAudit::INITIATED_BY_CLI
        );

        // Send email notification if issues are detected
        if ($audit->hasIssues()) {
            $this->sendAlertNotification($audit, $outputJson);
        }

        if ($outputJson) {
            $this->outputJson($audit);

            return $audit->hasIssues() ? Command::FAILURE : Command::SUCCESS;
        }

        $this->info(' '.__('common.done'));
        $this->newLine();

        // Display results
        $this->displayResult($audit);

        return $audit->hasIssues() ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Send alert notification
     */
    protected function sendAlertNotification(FileIntegrityAudit $audit, bool $outputJson): void
    {
        // Check if notification is enabled
        $notificationEnabled = filter_var(
            SecuritySetting::get('notification_enabled', true),
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $notificationEnabled) {
            if (! $outputJson) {
                $this->line(__('admin/command/integrity.notification_disabled'));
            }

            return;
        }

        // Get notification email address
        $notificationEmail = SiteSetting::getValue('notification_email');
        if (empty($notificationEmail)) {
            if (! $outputJson) {
                $this->warn(__('admin/command/integrity.no_notification_email'));
            }

            return;
        }

        try {
            Mail::to($notificationEmail)->send(new FileIntegrityAlertMail($audit));
            if (! $outputJson) {
                $this->info(__('admin/command/integrity.notification_sent', ['email' => $notificationEmail]));
            }
        } catch (\Exception $e) {
            if (! $outputJson) {
                $this->error(__('admin/command/integrity.notification_failed', ['error' => $e->getMessage()]));
            }
        }
    }

    /**
     * Display results
     */
    protected function displayResult(FileIntegrityAudit $audit): void
    {
        // Display status
        $statusLabel = match ($audit->status) {
            FileIntegrityAudit::STATUS_OK => '<fg=green>'.__('admin/command/integrity.status_ok').'</>',
            FileIntegrityAudit::STATUS_WARNING => '<fg=yellow>'.__('admin/command/integrity.status_warning').'</>',
            FileIntegrityAudit::STATUS_CRITICAL => '<fg=red>'.__('admin/command/integrity.status_critical').'</>',
            default => $audit->status,
        };

        $this->line(__('admin/command/integrity.status').': '.$statusLabel);
        $this->line(__('admin/command/integrity.files_scanned').': '.$audit->total_files_scanned);
        $this->line(__('admin/command/integrity.duration').': '.$audit->duration_ms.'ms');
        $this->newLine();

        // Summary
        if ($audit->summary) {
            $this->line(__('admin/command/integrity.summary').': '.$audit->summary);
            $this->newLine();
        }

        // Display details
        if ($audit->hasIssues()) {
            $this->displayIssues($audit);
        }
    }

    /**
     * Display issue details
     */
    protected function displayIssues(FileIntegrityAudit $audit): void
    {
        // Modified files
        $changed = $audit->getChangedFiles();
        if (! empty($changed)) {
            $this->warn(__('admin/command/integrity.changed_files', ['count' => count($changed)]));
            foreach ($changed as $file) {
                $this->line('  <fg=yellow>M</> '.$file['path']);
            }
            $this->newLine();
        }

        // Added files
        $added = $audit->getAddedFiles();
        if (! empty($added)) {
            $this->warn(__('admin/command/integrity.added_files', ['count' => count($added)]));
            foreach ($added as $file) {
                $this->line('  <fg=green>A</> '.$file['path']);
            }
            $this->newLine();
        }

        // Deleted files
        $removed = $audit->getRemovedFiles();
        if (! empty($removed)) {
            $this->error(__('admin/command/integrity.removed_files', ['count' => count($removed)]));
            foreach ($removed as $file) {
                $this->line('  <fg=red>D</> '.$file['path']);
            }
            $this->newLine();
        }

        // Suspicious files
        $suspicious = $audit->getSuspiciousFiles();
        if (! empty($suspicious)) {
            $this->error(__('admin/command/integrity.suspicious_files', ['count' => count($suspicious)]));
            foreach ($suspicious as $file) {
                $reason = match ($file['reason'] ?? '') {
                    'php_in_uploads' => __('admin/command/integrity.reason_php_in_uploads'),
                    'unknown_php_in_public' => __('admin/command/integrity.reason_unknown_php_in_public'),
                    default => $file['reason'] ?? '',
                };
                $this->line('  <fg=red>!</> '.$file['path'].' ('.$reason.')');
            }
            $this->newLine();
        }

        // Recommended actions
        if ($audit->isCritical()) {
            $this->newLine();
            $this->error(__('admin/command/integrity.critical_warning'));
            $this->line(__('admin/command/integrity.critical_action_1'));
            $this->line(__('admin/command/integrity.critical_action_2'));
            $this->line(__('admin/command/integrity.critical_action_3'));
        }
    }

    /**
     * Output in JSON format
     */
    protected function outputJson(FileIntegrityAudit $audit): void
    {
        $output = [
            'scan_uuid' => $audit->scan_uuid,
            'status' => $audit->status,
            'scope' => $audit->scope,
            'total_files_scanned' => $audit->total_files_scanned,
            'changed_files_count' => $audit->changed_files_count,
            'added_files_count' => $audit->added_files_count,
            'removed_files_count' => $audit->removed_files_count,
            'suspicious_files_count' => $audit->suspicious_files_count,
            'duration_ms' => $audit->duration_ms,
            'summary' => $audit->summary,
            'started_at' => $audit->started_at?->toIso8601String(),
            'finished_at' => $audit->finished_at?->toIso8601String(),
            'result' => $audit->result_payload,
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
