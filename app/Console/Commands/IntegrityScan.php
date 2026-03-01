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

namespace App\Console\Commands;

use App\Mail\FileIntegrityAlertMail;
use App\Models\BaseSetting;
use App\Models\FileIntegrityAudit;
use App\Models\SecuritySetting;
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
                            {--json : 結果をJSON形式で出力}
                            {--scheduled : スケジュール実行フラグ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ファイル整合性をスキャンして改ざんを検知します';

    /**
     * Execute the console command.
     */
    public function handle(FileIntegrityService $service): int
    {
        $scope = $this->option('scope');
        $outputJson = $this->option('json');

        if (!$outputJson) {
            $this->info(__('admin/command.integrity.starting_scan'));
            $this->newLine();
        }

        // 現在はコアのみサポート
        if ($scope !== 'core') {
            if (!$outputJson) {
                $this->warn(__('admin/command.integrity.scope_not_supported', ['scope' => $scope]));
                $this->info(__('admin/command.integrity.using_core_scope'));
            }
            $scope = 'core';
        }

        if (!$outputJson) {
            $this->output->write(__('admin/command.integrity.scanning'));
        }

        // スキャン実行（スケジュール実行の場合はTRIGGER_SCHEDULE）
        $trigger = $this->option('scheduled') 
            ? FileIntegrityAudit::TRIGGER_SCHEDULE 
            : FileIntegrityAudit::TRIGGER_MANUAL;
        
        $audit = $service->scanCore(
            $trigger,
            FileIntegrityAudit::INITIATED_BY_CLI
        );

        // 問題が検出された場合はメール通知
        if ($audit->hasIssues()) {
            $this->sendAlertNotification($audit, $outputJson);
        }

        if ($outputJson) {
            $this->outputJson($audit);
            return $audit->hasIssues() ? Command::FAILURE : Command::SUCCESS;
        }

        $this->info(' ' . __('common.done'));
        $this->newLine();

        // 結果を表示
        $this->displayResult($audit);

        return $audit->hasIssues() ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * アラート通知を送信
     */
    protected function sendAlertNotification(FileIntegrityAudit $audit, bool $outputJson): void
    {
        // 通知が有効かチェック
        $notificationEnabled = filter_var(
            SecuritySetting::get('notification_enabled', true),
            FILTER_VALIDATE_BOOLEAN
        );

        if (!$notificationEnabled) {
            if (!$outputJson) {
                $this->line(__('admin/command.integrity.notification_disabled'));
            }
            return;
        }

        // 通知先メールアドレスを取得
        $notificationEmail = BaseSetting::getValue('notification_email');
        if (empty($notificationEmail)) {
            if (!$outputJson) {
                $this->warn(__('admin/command.integrity.no_notification_email'));
            }
            return;
        }

        try {
            Mail::to($notificationEmail)->send(new FileIntegrityAlertMail($audit));
            if (!$outputJson) {
                $this->info(__('admin/command.integrity.notification_sent', ['email' => $notificationEmail]));
            }
        } catch (\Exception $e) {
            if (!$outputJson) {
                $this->error(__('admin/command.integrity.notification_failed', ['error' => $e->getMessage()]));
            }
        }
    }

    /**
     * 結果を表示
     */
    protected function displayResult(FileIntegrityAudit $audit): void
    {
        // ステータス表示
        $statusLabel = match ($audit->status) {
            FileIntegrityAudit::STATUS_OK => '<fg=green>' . __('admin/command.integrity.status_ok') . '</>',
            FileIntegrityAudit::STATUS_WARNING => '<fg=yellow>' . __('admin/command.integrity.status_warning') . '</>',
            FileIntegrityAudit::STATUS_CRITICAL => '<fg=red>' . __('admin/command.integrity.status_critical') . '</>',
            default => $audit->status,
        };

        $this->line(__('admin/command.integrity.status') . ': ' . $statusLabel);
        $this->line(__('admin/command.integrity.files_scanned') . ': ' . $audit->total_files_scanned);
        $this->line(__('admin/command.integrity.duration') . ': ' . $audit->duration_ms . 'ms');
        $this->newLine();

        // サマリー
        if ($audit->summary) {
            $this->line(__('admin/command.integrity.summary') . ': ' . $audit->summary);
            $this->newLine();
        }

        // 詳細表示
        if ($audit->hasIssues()) {
            $this->displayIssues($audit);
        }
    }

    /**
     * 問題の詳細を表示
     */
    protected function displayIssues(FileIntegrityAudit $audit): void
    {
        // 変更されたファイル
        $changed = $audit->getChangedFiles();
        if (!empty($changed)) {
            $this->warn(__('admin/command.integrity.changed_files', ['count' => count($changed)]));
            foreach ($changed as $file) {
                $this->line('  <fg=yellow>M</> ' . $file['path']);
            }
            $this->newLine();
        }

        // 追加されたファイル
        $added = $audit->getAddedFiles();
        if (!empty($added)) {
            $this->warn(__('admin/command.integrity.added_files', ['count' => count($added)]));
            foreach ($added as $file) {
                $this->line('  <fg=green>A</> ' . $file['path']);
            }
            $this->newLine();
        }

        // 削除されたファイル
        $removed = $audit->getRemovedFiles();
        if (!empty($removed)) {
            $this->error(__('admin/command.integrity.removed_files', ['count' => count($removed)]));
            foreach ($removed as $file) {
                $this->line('  <fg=red>D</> ' . $file['path']);
            }
            $this->newLine();
        }

        // 疑わしいファイル
        $suspicious = $audit->getSuspiciousFiles();
        if (!empty($suspicious)) {
            $this->error(__('admin/command.integrity.suspicious_files', ['count' => count($suspicious)]));
            foreach ($suspicious as $file) {
                $reason = match ($file['reason'] ?? '') {
                    'php_in_uploads' => __('admin/command.integrity.reason_php_in_uploads'),
                    'unknown_php_in_public' => __('admin/command.integrity.reason_unknown_php_in_public'),
                    default => $file['reason'] ?? '',
                };
                $this->line('  <fg=red>!</> ' . $file['path'] . ' (' . $reason . ')');
            }
            $this->newLine();
        }

        // 推奨アクション
        if ($audit->isCritical()) {
            $this->newLine();
            $this->error(__('admin/command.integrity.critical_warning'));
            $this->line(__('admin/command.integrity.critical_action_1'));
            $this->line(__('admin/command.integrity.critical_action_2'));
            $this->line(__('admin/command.integrity.critical_action_3'));
        }
    }

    /**
     * JSON形式で出力
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
