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

namespace App\Console\Commands;

use App\Services\WebhookDeadLetterService;
use Illuminate\Console\Command;

/**
 * Process webhook dead letters
 *
 * Sends notifications for failed webhooks and optionally cleans up old records.
 */
class ProcessWebhookDeadLetters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:dead-letters
                            {--notify : Send notifications for unnotified dead letters}
                            {--cleanup : Clean up old dead letter records}
                            {--days=90 : Days to keep dead letters (for cleanup)}
                            {--stats : Show dead letter statistics}
                            {--list : List pending dead letters}
                            {--retry= : Retry a specific dead letter by ID}
                            {--retry-all : Retry all pending dead letters}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process webhook dead letters (notifications and cleanup)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('stats')) {
            return $this->showStats();
        }

        if ($this->option('list')) {
            return $this->listPending();
        }

        if ($this->option('retry')) {
            return $this->retryById((int) $this->option('retry'));
        }

        if ($this->option('retry-all')) {
            return $this->retryAll();
        }

        if ($this->option('notify')) {
            $this->sendNotifications();
        }

        if ($this->option('cleanup')) {
            $this->cleanup();
        }

        if (! $this->option('notify') && ! $this->option('cleanup') && ! $this->option('stats')) {
            $this->info(__('admin/command/webhook.dead_letters.no_action'));
            $this->line('  --notify     '.__('admin/command/webhook.dead_letters.option_notify'));
            $this->line('  --cleanup    '.__('admin/command/webhook.dead_letters.option_cleanup'));
            $this->line('  --stats      '.__('admin/command/webhook.dead_letters.option_stats'));
            $this->line('  --list       '.__('admin/command/webhook.dead_letters.option_list'));
            $this->line('  --retry=ID   '.__('admin/command/webhook.dead_letters.option_retry'));
            $this->line('  --retry-all  '.__('admin/command/webhook.dead_letters.option_retry_all'));
        }

        return self::SUCCESS;
    }

    /**
     * Send notifications for unnotified dead letters
     */
    protected function sendNotifications(): void
    {
        $this->info(__('admin/command/webhook.dead_letters.sending_notifications'));

        $count = WebhookDeadLetterService::sendPendingNotifications();

        if ($count > 0) {
            $this->info(__('admin/command/webhook.dead_letters.notifications_sent', ['count' => $count]));
        } else {
            $this->info(__('admin/command/webhook.dead_letters.no_pending_notifications'));
        }
    }

    /**
     * Clean up old dead letter records
     */
    protected function cleanup(): void
    {
        $days = (int) $this->option('days');

        $this->info(__('admin/command/webhook.dead_letters.cleaning_up', ['days' => $days]));

        $count = WebhookDeadLetterService::cleanup($days);

        $this->info(__('admin/command/webhook.dead_letters.cleanup_complete', ['count' => $count]));
    }

    /**
     * Show dead letter statistics
     */
    protected function showStats(): int
    {
        $stats = WebhookDeadLetterService::getStats();

        $this->info(__('admin/command/webhook.dead_letters.stats_title'));
        $this->newLine();

        $this->table(
            [__('admin/command/webhook.dead_letters.stat_name'), __('admin/command/webhook.dead_letters.stat_value')],
            [
                [__('admin/command/webhook.dead_letters.total'), $stats['total']],
                [__('admin/command/webhook.dead_letters.pending'), $stats['pending']],
                [__('admin/command/webhook.dead_letters.notified'), $stats['notified']],
                [__('admin/command/webhook.dead_letters.manually_retried'), $stats['manually_retried']],
            ]
        );

        if (! empty($stats['by_event'])) {
            $this->newLine();
            $this->info(__('admin/command/webhook.dead_letters.by_event'));

            $eventData = [];
            foreach ($stats['by_event'] as $event => $count) {
                $eventData[] = [$event, $count];
            }

            $this->table(
                [__('admin/command/webhook.dead_letters.event'), __('admin/command/webhook.dead_letters.count')],
                $eventData
            );
        }

        return self::SUCCESS;
    }

    /**
     * List pending dead letters
     */
    protected function listPending(): int
    {
        $deadLetters = WebhookDeadLetterService::getPending();

        if ($deadLetters->isEmpty()) {
            $this->info(__('admin/command/webhook.dead_letters.no_pending'));

            return self::SUCCESS;
        }

        $this->info(__('admin/command/webhook.dead_letters.pending_list_title'));
        $this->newLine();

        $rows = [];
        foreach ($deadLetters as $dl) {
            $rows[] = [
                $dl->id,
                $dl->event,
                $dl->webhook->name ?? 'N/A',
                $dl->total_attempts,
                substr($dl->last_error ?? '', 0, 50).(strlen($dl->last_error ?? '') > 50 ? '...' : ''),
                $dl->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table(
            [
                __('admin/command/webhook.dead_letters.col_id'),
                __('admin/command/webhook.dead_letters.event'),
                __('admin/command/webhook.dead_letters.col_webhook'),
                __('admin/command/webhook.dead_letters.col_attempts'),
                __('admin/command/webhook.dead_letters.col_error'),
                __('admin/command/webhook.dead_letters.col_created'),
            ],
            $rows
        );

        $this->newLine();
        $this->line(__('admin/command/webhook.dead_letters.retry_hint'));

        return self::SUCCESS;
    }

    /**
     * Retry a specific dead letter by ID
     */
    protected function retryById(int $id): int
    {
        $deadLetter = \App\Models\WebhookDeadLetter::find($id);

        if (! $deadLetter) {
            $this->error(__('admin/command/webhook.dead_letters.not_found', ['id' => $id]));

            return self::FAILURE;
        }

        if (! $deadLetter->canRetry()) {
            $this->error(__('admin/command/webhook.dead_letters.cannot_retry'));

            return self::FAILURE;
        }

        try {
            $delivery = WebhookDeadLetterService::retryDeadLetter($deadLetter);
            $this->info(__('admin/command/webhook.dead_letters.retry_success', [
                'id' => $id,
                'delivery_id' => $delivery->id,
            ]));

            // Dispatch the job
            \App\Jobs\SendWebhook::dispatch($delivery);
            $this->info(__('admin/command/webhook.dead_letters.retry_queued'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error(__('admin/command/webhook.dead_letters.retry_failed', ['error' => $e->getMessage()]));

            return self::FAILURE;
        }
    }

    /**
     * Retry all pending dead letters
     */
    protected function retryAll(): int
    {
        $deadLetters = \App\Models\WebhookDeadLetter::pending()->get();

        if ($deadLetters->isEmpty()) {
            $this->info(__('admin/command/webhook.dead_letters.no_pending'));

            return self::SUCCESS;
        }

        $count = $deadLetters->count();
        if (! $this->confirm(__('admin/command/webhook.dead_letters.confirm_retry_all', ['count' => $count]))) {
            $this->info(__('admin/command/webhook.dead_letters.cancelled'));

            return self::SUCCESS;
        }

        $success = 0;
        $failed = 0;

        foreach ($deadLetters as $deadLetter) {
            if (! $deadLetter->canRetry()) {
                $failed++;

                continue;
            }

            try {
                $delivery = WebhookDeadLetterService::retryDeadLetter($deadLetter);
                \App\Jobs\SendWebhook::dispatch($delivery);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->warn("ID {$deadLetter->id}: {$e->getMessage()}");
            }
        }

        $this->info(__('admin/command/webhook.dead_letters.retry_all_complete', [
            'success' => $success,
            'failed' => $failed,
        ]));

        return self::SUCCESS;
    }
}
