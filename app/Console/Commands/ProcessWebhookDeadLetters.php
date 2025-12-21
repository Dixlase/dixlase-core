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
                            {--stats : Show dead letter statistics}';

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

        if ($this->option('notify')) {
            $this->sendNotifications();
        }

        if ($this->option('cleanup')) {
            $this->cleanup();
        }

        if (!$this->option('notify') && !$this->option('cleanup') && !$this->option('stats')) {
            $this->info(__('admin/command.webhook.dead_letters.no_action'));
            $this->line('  --notify   ' . __('admin/command.webhook.dead_letters.option_notify'));
            $this->line('  --cleanup  ' . __('admin/command.webhook.dead_letters.option_cleanup'));
            $this->line('  --stats    ' . __('admin/command.webhook.dead_letters.option_stats'));
        }

        return self::SUCCESS;
    }

    /**
     * Send notifications for unnotified dead letters
     */
    protected function sendNotifications(): void
    {
        $this->info(__('admin/command.webhook.dead_letters.sending_notifications'));

        $count = WebhookDeadLetterService::sendPendingNotifications();

        if ($count > 0) {
            $this->info(__('admin/command.webhook.dead_letters.notifications_sent', ['count' => $count]));
        } else {
            $this->info(__('admin/command.webhook.dead_letters.no_pending_notifications'));
        }
    }

    /**
     * Clean up old dead letter records
     */
    protected function cleanup(): void
    {
        $days = (int) $this->option('days');
        
        $this->info(__('admin/command.webhook.dead_letters.cleaning_up', ['days' => $days]));

        $count = WebhookDeadLetterService::cleanup($days);

        $this->info(__('admin/command.webhook.dead_letters.cleanup_complete', ['count' => $count]));
    }

    /**
     * Show dead letter statistics
     */
    protected function showStats(): int
    {
        $stats = WebhookDeadLetterService::getStats();

        $this->info(__('admin/command.webhook.dead_letters.stats_title'));
        $this->newLine();

        $this->table(
            [__('admin/command.webhook.dead_letters.stat_name'), __('admin/command.webhook.dead_letters.stat_value')],
            [
                [__('admin/command.webhook.dead_letters.total'), $stats['total']],
                [__('admin/command.webhook.dead_letters.pending'), $stats['pending']],
                [__('admin/command.webhook.dead_letters.notified'), $stats['notified']],
                [__('admin/command.webhook.dead_letters.manually_retried'), $stats['manually_retried']],
            ]
        );

        if (!empty($stats['by_event'])) {
            $this->newLine();
            $this->info(__('admin/command.webhook.dead_letters.by_event'));

            $eventData = [];
            foreach ($stats['by_event'] as $event => $count) {
                $eventData[] = [$event, $count];
            }

            $this->table(
                [__('admin/command.webhook.dead_letters.event'), __('admin/command.webhook.dead_letters.count')],
                $eventData
            );
        }

        return self::SUCCESS;
    }
}
