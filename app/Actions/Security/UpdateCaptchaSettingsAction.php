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

namespace App\Actions\Security;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;
use App\Services\CaptchaService;
use App\Services\CaptchaTestService;

/**
 * Update CAPTCHA settings with driver change detection and test result management
 */
class UpdateCaptchaSettingsAction extends AbstractAction
{
    protected const SETTING_KEYS = [
        'captcha_enabled', 'captcha_driver', 'captcha_google_version',
        'captcha_google_min_score', 'captcha_google_project_id',
        'captcha_google_site_key', 'captcha_google_secret_key',
        'captcha_google_enterprise_site_key', 'captcha_google_enterprise_secret_key',
        'captcha_turnstile_site_key', 'captcha_turnstile_secret_key',
    ];

    protected const SENSITIVE_KEYS = [
        'captcha_google_secret_key', 'captcha_google_enterprise_secret_key',
        'captcha_turnstile_secret_key',
    ];

    public function __construct(
        protected readonly SecuritySettingRepositoryInterface $repository,
        protected readonly CaptchaService $captchaService,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_SECURITY;
    }

    protected function auditAction(): string
    {
        return 'security.captcha';
    }

    protected function auditCategory(): string
    {
        return 'system';
    }

    /**
     * @param  array{
     *     captcha_enabled?: bool,
     *     captcha_driver?: string,
     *     captcha_site_key?: string,
     *     captcha_secret_key?: string,
     *     captcha_google_version?: string,
     *     captcha_google_min_score?: string,
     *     captcha_google_project_id?: string,
     *     captcha_authentication_result?: bool,
     *     forms?: array,
     * }  $data
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $before = $this->repository->getMultiple(static::SETTING_KEYS);

        // Detect setting changes
        $currentDriver = $this->repository->get('captcha_driver', 'google');
        $currentSiteKey = $this->repository->get('captcha_site_key', '');
        $currentSecretKey = $this->repository->get('captcha_secret_key', '');
        $currentVersion = $this->repository->get('captcha_google_version', 'v3');
        $currentMinScore = $this->repository->get('captcha_google_min_score', '0.5');

        $newDriver = $data['captcha_driver'] ?? 'google';
        $newSiteKey = $data['captcha_site_key'] ?? '';
        $newSecretKey = $data['captcha_secret_key'] ?? '';
        $newVersion = $data['captcha_google_version'] ?? 'v3';
        $newMinScore = $data['captcha_google_min_score'] ?? '0.5';

        $settingsChanged = (
            $currentDriver !== $newDriver ||
            $currentSiteKey !== $newSiteKey ||
            $currentSecretKey !== $newSecretKey ||
            $currentVersion !== $newVersion ||
            (string) $currentMinScore !== (string) $newMinScore
        );

        // Handle test result based on changes
        $submittedTestResult = (bool) ($data['captcha_authentication_result'] ?? false);
        $captchaTestService = app(CaptchaTestService::class);

        if ($settingsChanged && ! $submittedTestResult) {
            $captchaTestService->resetCaptchaTestResults();
        } elseif ($submittedTestResult) {
            $captchaTestService->saveCaptchaTestResult($newDriver, true);
        }

        // Write settings
        $this->repository->set('captcha_enabled', $data['captcha_enabled'] ?? false);
        $this->repository->set('captcha_driver', $newDriver);
        $this->saveProviderKeys($newDriver, $newSiteKey, $newSecretKey);
        $this->repository->set('captcha_google_version', $newVersion);
        $this->repository->set('captcha_google_min_score', $newMinScore);
        $this->repository->set('captcha_google_project_id', $data['captcha_google_project_id'] ?? '');

        // Update form settings
        if (isset($data['forms']) && is_array($data['forms'])) {
            $this->captchaService->bulkUpdateFormSettings($data['forms']);
        }

        $after = $this->repository->getMultiple(static::SETTING_KEYS);

        return new ActionResult(
            success: true,
            metadata: ['before' => $before, 'after' => $after],
        );
    }

    /**
     * Override audit to use bulk settings change format with sensitive keys
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        if (! $result->success) {
            return;
        }

        Audit::logBulkSettingsChange(
            'security.captcha',
            $result->metadata['before'] ?? [],
            $result->metadata['after'] ?? [],
            $actor->toAuditMorph(),
            static::SENSITIVE_KEYS,
        );
    }

    /**
     * Save provider-specific keys
     */
    private function saveProviderKeys(string $driver, string $siteKey, string $secretKey): void
    {
        $keyPrefix = match ($driver) {
            'google' => 'captcha_google',
            'google_enterprise' => 'captcha_google_enterprise',
            'turnstile' => 'captcha_turnstile',
            default => 'captcha_google',
        };

        $this->repository->set("{$keyPrefix}_site_key", $siteKey);
        $this->repository->set("{$keyPrefix}_secret_key", $secretKey);
    }
}
