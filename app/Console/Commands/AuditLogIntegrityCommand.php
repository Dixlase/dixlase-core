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

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AuditLogIntegrityService;
use Illuminate\Console\Command;

/**
 * Audit log integrity management command
 *
 * Build and verify hash chains, create daily signatures
 */
class AuditLogIntegrityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:integrity
                            {action : Action to perform (build, verify, seal, stats)}
                            {--date= : Target date (YYYY-MM-DD) for seal/verify}
                            {--days=7 : Number of days for pending seals}
                            {--limit=1000 : Limit for build action}
                            {--from= : Start ID for verify action}
                            {--to= : End ID for verify action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage audit log integrity (hash chain and daily seals)';

    protected AuditLogIntegrityService $service;

    public function __construct(AuditLogIntegrityService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'build' => $this->handleBuild(),
            'verify' => $this->handleVerify(),
            'seal' => $this->handleSeal(),
            'stats' => $this->handleStats(),
            default => $this->handleUnknownAction($action),
        };
    }

    /**
     * Build hash chain
     */
    protected function handleBuild(): int
    {
        $limit = (int) $this->option('limit');

        $this->info(__('admin/command.audit.integrity.building_chains'));

        $result = $this->service->buildPendingChains($limit);

        $this->info(__('admin/command.audit.integrity.build_complete', [
            'processed' => $result['processed'],
            'remaining' => $result['remaining'],
        ]));

        if (! empty($result['errors'])) {
            $this->warn(__('admin/command.audit.integrity.build_errors', [
                'count' => count($result['errors']),
            ]));
            foreach ($result['errors'] as $error) {
                $this->line("  - ID {$error['id']}: {$error['error']}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Verify hash chain
     */
    protected function handleVerify(): int
    {
        $date = $this->option('date');
        $fromId = $this->option('from') ? (int) $this->option('from') : null;
        $toId = $this->option('to') ? (int) $this->option('to') : null;

        if ($date) {
            return $this->verifyDailySeal($date);
        }

        $this->info(__('admin/command.audit.integrity.verifying_chain'));

        $result = $this->service->verifyChain($fromId, $toId);

        $this->newLine();
        $this->table(
            [__('admin/command.audit.integrity.stat_name'), __('admin/command.audit.integrity.stat_value')],
            [
                [__('admin/command.audit.integrity.total'), $result['total']],
                [__('admin/command.audit.integrity.valid'), $result['valid']],
                [__('admin/command.audit.integrity.invalid'), $result['invalid']],
            ]
        );

        if ($result['is_valid']) {
            $this->info(__('admin/command.audit.integrity.chain_valid'));
        } else {
            $this->error(__('admin/command.audit.integrity.chain_invalid'));

            if (! empty($result['errors'])) {
                $this->newLine();
                $this->warn(__('admin/command.audit.integrity.tampered_records'));
                foreach (array_slice($result['errors'], 0, 10) as $error) {
                    $this->line("  - ID {$error['id']}: ".implode(', ', $error['errors']));
                }
                if (count($result['errors']) > 10) {
                    $this->line('  ... '.__('admin/command.audit.integrity.and_more', [
                        'count' => count($result['errors']) - 10,
                    ]));
                }
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Verify daily signatures
     */
    protected function verifyDailySeal(string $dateString): int
    {
        try {
            $date = \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            $this->error(__('admin/command.audit.integrity.invalid_date'));

            return self::FAILURE;
        }

        $this->info(__('admin/command.audit.integrity.verifying_seal', [
            'date' => $date->format('Y-m-d'),
        ]));

        $result = $this->service->verifyDailySeal($date);

        if (! $result['exists']) {
            $this->warn(__('admin/command.audit.integrity.seal_not_found'));

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            [__('admin/command.audit.integrity.check'), __('admin/command.audit.integrity.result')],
            [
                [__('admin/command.audit.integrity.signature'), $result['checks']['signature'] ? '✓' : '✗'],
                [__('admin/command.audit.integrity.log_count'), $result['checks']['log_count'] ? '✓' : '✗'],
                [__('admin/command.audit.integrity.final_hash'), $result['checks']['final_hash'] ? '✓' : '✗'],
                [__('admin/command.audit.integrity.chain'), $result['checks']['chain'] ? '✓' : '✗'],
            ]
        );

        if ($result['is_valid']) {
            $this->info(__('admin/command.audit.integrity.seal_valid'));
        } else {
            $this->error(__('admin/command.audit.integrity.seal_invalid'));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Create daily signatures
     */
    protected function handleSeal(): int
    {
        $date = $this->option('date');
        $days = (int) $this->option('days');

        if ($date) {
            return $this->createSingleSeal($date);
        }

        $this->info(__('admin/command.audit.integrity.creating_seals', ['days' => $days]));

        $results = $this->service->createPendingSeals($days);

        if (empty($results)) {
            $this->info(__('admin/command.audit.integrity.no_pending_seals'));
        } else {
            $this->table(
                [__('admin/command.audit.integrity.date'), __('admin/command.audit.integrity.log_count'), __('admin/command.audit.integrity.status')],
                array_map(fn ($r) => [$r['date'], $r['log_count'], $r['status']], $results)
            );
            $this->info(__('admin/command.audit.integrity.seals_created', ['count' => count($results)]));
        }

        return self::SUCCESS;
    }

    /**
     * Create signature for a single day
     */
    protected function createSingleSeal(string $dateString): int
    {
        try {
            $date = \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            $this->error(__('admin/command.audit.integrity.invalid_date'));

            return self::FAILURE;
        }

        $this->info(__('admin/command.audit.integrity.creating_seal', [
            'date' => $date->format('Y-m-d'),
        ]));

        // First build the hash chain
        $this->service->buildChainForDate($date);

        // Create seal
        $seal = $this->service->createDailySeal($date);

        if ($seal) {
            $this->info(__('admin/command.audit.integrity.seal_created', [
                'date' => $date->format('Y-m-d'),
                'log_count' => $seal->log_count,
            ]));
        } else {
            $this->warn(__('admin/command.audit.integrity.no_logs_for_date'));
        }

        return self::SUCCESS;
    }

    /**
     * Display statistics
     */
    protected function handleStats(): int
    {
        $stats = $this->service->getStats();

        $this->info(__('admin/command.audit.integrity.stats_title'));
        $this->newLine();

        $this->table(
            [__('admin/command.audit.integrity.stat_name'), __('admin/command.audit.integrity.stat_value')],
            [
                [__('admin/command.audit.integrity.total_logs'), $stats['total_logs']],
                [__('admin/command.audit.integrity.with_hash'), $stats['with_hash_chain']],
                [__('admin/command.audit.integrity.without_hash'), $stats['without_hash_chain']],
                [__('admin/command.audit.integrity.verified'), $stats['verified']],
                [__('admin/command.audit.integrity.tampered'), $stats['tampered']],
                [__('admin/command.audit.integrity.unverified'), $stats['unverified']],
            ]
        );

        $this->newLine();
        $this->info(__('admin/command.audit.integrity.daily_seals_title'));

        $this->table(
            [__('admin/command.audit.integrity.stat_name'), __('admin/command.audit.integrity.stat_value')],
            [
                [__('admin/command.audit.integrity.total_seals'), $stats['daily_seals']['total']],
                [__('admin/command.audit.integrity.valid_seals'), $stats['daily_seals']['valid']],
                [__('admin/command.audit.integrity.invalid_seals'), $stats['daily_seals']['invalid']],
                [__('admin/command.audit.integrity.sealed_logs'), $stats['daily_seals']['total_logs']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Unknown action
     */
    protected function handleUnknownAction(string $action): int
    {
        $this->error(__('admin/command.audit.integrity.unknown_action', ['action' => $action]));
        $this->line(__('admin/command.audit.integrity.available_actions'));
        $this->line('  - build   : '.__('admin/command.audit.integrity.action_build'));
        $this->line('  - verify  : '.__('admin/command.audit.integrity.action_verify'));
        $this->line('  - seal    : '.__('admin/command.audit.integrity.action_seal'));
        $this->line('  - stats   : '.__('admin/command.audit.integrity.action_stats'));

        return self::FAILURE;
    }
}
