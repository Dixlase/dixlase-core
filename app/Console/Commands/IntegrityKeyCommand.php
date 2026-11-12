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

use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\Security\IntegrityKeyring;
use App\Support\Install\EnvFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Create or rotate the site's integrity key (dixlase-core#665).
 *
 * The key is written to `.env` only by this command, which an operator
 * runs on purpose; nothing writes it during an update. A rotation keeps
 * the old key in INTEGRITY_PREVIOUS_KEYS, so everything it signed still
 * verifies.
 */
class IntegrityKeyCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'dls:integrity:key
                            {--rotate : Replace the current key with a new version and keep the old one for verification}
                            {--show : Print the .env lines instead of writing them}';

    /**
     * @var string
     */
    protected $description = 'Create or rotate the integrity key that signs the audit log hash chain and its daily seals';

    public function handle(): int
    {
        $envPath = $this->laravel->environmentFilePath();
        $show = (bool) $this->option('show');
        $rotate = (bool) $this->option('rotate');

        if (! $show && ! File::exists($envPath)) {
            $this->error(__('admin/command/integrity-key.env_missing', ['path' => $envPath]));

            return self::FAILURE;
        }

        $current = EnvFile::read('INTEGRITY_KEY', $envPath);
        $previous = EnvFile::read('INTEGRITY_PREVIOUS_KEYS', $envPath);
        $currentVersion = $current === '' ? null : IntegrityKeyring::versionOf($current);

        if ($current !== '' && $currentVersion === null) {
            $this->error(__('admin/command/integrity-key.current_invalid'));

            return self::FAILURE;
        }

        if (! $rotate && $currentVersion !== null) {
            $this->info(__('admin/command/integrity-key.already_configured', ['version' => $currentVersion]));
            $this->line(__('admin/command/integrity-key.rotate_hint'));

            return self::SUCCESS;
        }

        if ($rotate && $currentVersion === null) {
            $this->error(__('admin/command/integrity-key.nothing_to_rotate'));

            return self::FAILURE;
        }

        $versions = [IntegrityKeyring::FIRST_VERSION - 1];
        if ($currentVersion !== null) {
            $versions[] = $currentVersion;
        }
        foreach (array_filter(array_map('trim', explode(',', $previous))) as $entry) {
            $version = IntegrityKeyring::versionOf($entry);
            if ($version !== null) {
                $versions[] = $version;
            }
        }
        $newVersion = max($versions) + 1;

        if ($newVersion > IntegrityKeyring::MAX_VERSION) {
            $this->error(__('admin/command/integrity-key.versions_exhausted'));

            return self::FAILURE;
        }

        $values = ['INTEGRITY_KEY' => IntegrityKeyring::generate($newVersion)];
        if ($rotate) {
            $values['INTEGRITY_PREVIOUS_KEYS'] = implode(',', array_filter([$current, $previous], fn ($v) => $v !== ''));
        }

        if ($show) {
            $this->line(__('admin/command/integrity-key.show_intro'));
            foreach ($values as $name => $value) {
                $this->line($name.'='.EnvFile::formatValue($value));
            }

            return self::SUCCESS;
        }

        EnvFile::update($values, $envPath);

        $action = $rotate ? AuditLog::ACTION_INTEGRITY_KEY_ROTATED : AuditLog::ACTION_INTEGRITY_KEY_CREATED;
        app(AuditService::class)->log([
            'action' => $action,
            'category' => AuditLog::getCategoryForAction($action),
            'severity' => AuditLog::getDefaultSeverity($action),
            'context' => [
                'key_version' => $newVersion,
                'previous_version' => $currentVersion,
                'triggered_by' => 'cli',
            ],
        ]);

        $this->info(__($rotate ? 'admin/command/integrity-key.rotated' : 'admin/command/integrity-key.created', [
            'version' => $newVersion,
        ]));

        if ($this->laravel->configurationIsCached()) {
            $this->warn(__('admin/command/integrity-key.config_cached'));
        }
        $this->line(__('admin/command/integrity-key.restart_hint'));

        return self::SUCCESS;
    }
}
