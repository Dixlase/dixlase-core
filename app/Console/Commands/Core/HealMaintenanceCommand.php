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

namespace App\Console\Commands\Core;

use App\Services\Core\CoreMaintenanceGuard;
use Illuminate\Console\Command;

/**
 * Lift a maintenance window that a core update or rollback opened and
 * then failed to close because the process died.
 *
 * Scheduled every minute from routes/console.php; also safe to run by
 * hand. It only ever lifts a window whose owner record was written by
 * CoreMaintenanceGuard and whose process is gone — a manual
 * `php artisan down` is left untouched.
 */
class HealMaintenanceCommand extends Command
{
    protected $signature = 'dls:core:heal-maintenance
        {--dry-run : Report what would be done without lifting anything}';

    protected $description = 'Lift maintenance mode left behind by an interrupted core update or rollback';

    public function handle(CoreMaintenanceGuard $guard): int
    {
        if (! $guard->isMaintenanceActive()) {
            $this->info('Maintenance mode is not active; nothing to do.');

            return self::SUCCESS;
        }

        $owner = $guard->readOwner();
        if ($owner === null) {
            $this->info('Maintenance mode was enabled manually (no core-update owner record); leaving it in place.');

            return self::SUCCESS;
        }

        $operation = (string) ($owner['operation'] ?? 'update');
        $pid = (int) ($owner['pid'] ?? 0);

        if ($this->option('dry-run')) {
            $reason = $guard->orphanReason($owner);
            if ($reason === null) {
                $this->info(sprintf('Core %s (pid %d) still appears to be running; would leave maintenance mode in place.', $operation, $pid));
            } else {
                $this->warn(sprintf('Core %s (pid %d) %s; would lift maintenance mode.', $operation, $pid, $reason));
            }

            return self::SUCCESS;
        }

        $healed = $guard->healIfOrphaned();
        if ($healed === null) {
            $this->info(sprintf('Core %s (pid %d) still appears to be running; leaving maintenance mode in place.', $operation, $pid));

            return self::SUCCESS;
        }

        $this->warn(sprintf(
            'Lifted maintenance mode left behind by an interrupted core %s (pid %d %s). The %s did not complete — check the admin panel and storage/logs/core-update.log.',
            $healed['operation'],
            $healed['pid'],
            $healed['reason'],
            $healed['operation']
        ));

        return self::SUCCESS;
    }
}
