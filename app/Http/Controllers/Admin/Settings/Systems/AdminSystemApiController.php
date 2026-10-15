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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Actions\Api\GenerateApiKeyAction;
use App\Actions\Api\RegenerateApiKeyAction;
use App\Actions\Api\RevokeApiKeyAction;
use App\Actors\MemberActor;
use App\Contracts\Repositories\ApiSettingRepositoryInterface;
use App\Helpers\AdminHelper;
use App\Helpers\AdminModeHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemApiGenerateKeyRequest;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemApiUpdateRequest;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminSystemApiController extends AdminLoggedInController
{
    /** @var string[] */
    protected const SETTING_KEYS = ['api_enabled', 'api_rate_limit', 'api_signature_required'];

    /**
     * API settings repository
     */
    protected ApiSettingRepositoryInterface $apiSettingRepository;

    /**
     * Constructor
     */
    public function __construct(ApiSettingRepositoryInterface $apiSettingRepository)
    {
        parent::__construct();
        $this->apiSettingRepository = $apiSettingRepository;
    }

    /**
     * Display API settings screen
     */
    public function index()
    {
        // Get API key list
        $apiKeys = ApiKey::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get API settings
        $settings = [
            'api_enabled' => filter_var($this->apiSettingRepository->get('api_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'api_rate_limit' => (int) $this->apiSettingRepository->get('api_rate_limit', 60),
            'api_signature_required' => filter_var($this->apiSettingRepository->get('api_signature_required', true), FILTER_VALIDATE_BOOLEAN),
        ];

        // Available scopes
        $availableScopes = ApiKey::availableScopes();

        // JSON settings data for Alpine.js
        $apiConfig = [
            'apiEnabled' => old('api_enabled', $settings['api_enabled']) ? '1' : '0',
            'apiKeys' => $apiKeys,
            'translations' => [
                'key_name' => __('admin/settings/systems/api.key_name'),
                'environment' => __('admin/settings/systems/api.environment'),
                'key_prefix' => __('admin/settings/systems/api.key_prefix'),
                'rate_limit' => __('admin/settings/systems/api.rate_limit'),
                'requests_per_minute' => __('admin/settings/systems/api.requests_per_minute'),
                'unlimited' => __('admin/settings/systems/api.unlimited'),
                'expires_at' => __('admin/settings/systems/api.expires_at'),
                'no_expiry' => __('admin/settings/systems/api.no_expiry'),
                'usage_count' => __('admin/settings/systems/api.usage_count'),
                'created_at' => __('admin/settings/systems/api.created_at'),
                'scopes' => __('admin/settings/systems/api.scopes'),
                'no_scopes' => __('admin/settings/systems/api.no_scopes'),
                'allowed_ips' => __('admin/settings/systems/api.allowed_ips'),
                'all_ips_allowed' => __('admin/settings/systems/api.all_ips_allowed'),
                'key_description' => __('admin/settings/systems/api.key_description'),
                'copied_to_clipboard' => __('admin/settings/systems/api.copied_to_clipboard'),
            ],
        ];

        $this->viewParams['apiKeys'] = $apiKeys;
        $this->viewParams['settings'] = $settings;
        $this->viewParams['availableScopes'] = $availableScopes;
        $this->viewParams['apiConfig'] = $apiConfig;
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.systems.api');

        return view('admin/settings/systems/api.index', $this->viewParams);
    }

    /**
     * Update API settings
     */
    public function update(AdminSystemApiUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->apiSettingRepository,
            settingsPage: 'system.api',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                $repo->set('api_enabled', $data['api_enabled'] ?? false);
                $repo->set('api_rate_limit', $data['api_rate_limit'] ?? 60);
                $repo->set('api_signature_required', $data['api_signature_required'] ?? true);
            },
            permission: \App\Enums\Permission::SETTINGS_API,
        )->execute($actor, $request->validated());

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_system_api_controller.api_settings_updated'), [
            'member_id' => Auth::guard('member')->id(),
            'settings' => $request->validated(),
        ]);

        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.update_success'));
    }

    /**
     * Generate new API key
     *
     * The key is created through an action so the operation reaches the audit
     * log; the admin_activity line stays as the request trace it always was.
     */
    public function generateKey(AdminSystemApiGenerateKeyRequest $request)
    {
        $validated = $request->validated();

        $actor = new MemberActor(AdminHelper::getMember());

        $result = (new GenerateApiKeyAction())->execute($actor, $validated);

        /** @var ApiKey $apiKey */
        $apiKey = $result->model;

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_system_api_controller.api_key_generated'), [
            'member_id' => Auth::guard('member')->id(),
            'api_key_id' => $apiKey->id,
            'name' => $validated['name'],
            'environment' => $validated['environment'],
        ]);

        // Save generated key to session (display only once)
        session()->flash('generated_key', $result->metadata['plain_key'] ?? null);
        session()->flash('generated_key_id', $apiKey->id);

        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.key_generated'));
    }

    /**
     * Disable (delete) API key
     */
    public function revokeKey(int $id)
    {
        $apiKey = ApiKey::findOrFail($id);

        $keyName = $apiKey->name;

        $actor = new MemberActor(AdminHelper::getMember());

        (new RevokeApiKeyAction())->execute($actor, ['api_key' => $apiKey]);

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_system_api_controller.api_key_deleted'), [
            'member_id' => Auth::guard('member')->id(),
            'api_key_id' => $id,
            'name' => $keyName,
        ]);

        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.key_revoked'));
    }

    /**
     * Regenerate API key
     */
    public function regenerateKey(int $id)
    {
        $apiKey = ApiKey::findOrFail($id);

        $actor = new MemberActor(AdminHelper::getMember());

        $result = (new RegenerateApiKeyAction())->execute($actor, ['api_key' => $apiKey]);

        /** @var ApiKey $newKey */
        $newKey = $result->model;

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_system_api_controller.api_key_regenerated'), [
            'member_id' => Auth::guard('member')->id(),
            'old_api_key_id' => $id,
            'new_api_key_id' => $newKey->id,
            'name' => $newKey->name,
        ]);

        // Save generated key to session (display only once)
        session()->flash('generated_key', $result->metadata['plain_key'] ?? null);
        session()->flash('generated_key_id', $newKey->id);

        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.key_regenerated'));
    }
}
