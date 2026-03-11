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

namespace App\Console\Commands;

use App\Services\WebhookDispatcher;
use Illuminate\Console\Command;

/**
 * Process pending webhook retries
 *
 * This command should be scheduled to run periodically (e.g., every minute)
 * to process webhooks that need to be retried.
 */
class ProcessWebhookRetries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'webhooks:retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending webhook retries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = WebhookDispatcher::processRetries();

        if ($count > 0) {
            $this->info("Queued {$count} webhook(s) for retry.");
        } else {
            $this->info('No webhooks pending retry.');
        }

        return self::SUCCESS;
    }
}
