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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Contracts\Repositories\ApiSettingRepositoryInterface;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemApiGenerateKeyRequest;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemApiUpdateRequest;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminApiSettingsController extends AdminLoggedInController
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

        $this->viewParams['apiKeys'] = $apiKeys;
        $this->viewParams['settings'] = $settings;
        $this->viewParams['availableScopes'] = $availableScopes;

        return view('admin.settings.systems.api.index', $this->viewParams);
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

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_api_settings_controller.api_settings_updated'), [
            'member_id' => Auth::guard('member')->id(),
            'settings' => $request->validated(),
        ]);

        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.update_success'));
    }

    /**
     * Generate new API key
     */
    public function generateKey(AdminSystemApiGenerateKeyRequest $request)
    {
        $validated = $request->validated();

        // Parse allowed IP list
        $allowedIps = null;
        if (! empty($validated['allowed_ips'])) {
            $allowedIps = array_filter(
                array_map('trim', explode(',', $validated['allowed_ips']))
            );
        }

        // API key generation
        $result = ApiKey::generate(
            name: $validated['name'],
            environment: $validated['environment'],
            scopes: $validated['scopes'] ?? [],
            createdBy: Auth::guard('member')->id(),
            options: [
                'rate_limit' => $validated['rate_limit'] ?? null,
                'allowed_ips' => $allowedIps,
                'expires_at' => $validated['expires_at'] ?? null,
                'description' => $validated['description'] ?? null,
            ]
        );

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_api_settings_controller.api_key_generated'), [
            'member_id' => Auth::guard('member')->id(),
            'api_key_id' => $result['model']->id,
            'name' => $validated['name'],
            'environment' => $validated['environment'],
        ]);

        // Save generated key to session (display only once)
        session()->flash('generated_key', $result['plain_key']);
        session()->flash('generated_key_id', $result['model']->id);

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
        $apiKey->delete();

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_api_settings_controller.api_key_deleted'), [
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

        // Generate new key
        $result = ApiKey::generate(
            name: $apiKey->name,
            environment: $apiKey->environment,
            scopes: $apiKey->scopes ?? [],
            createdBy: Auth::guard('member')->id(),
            options: [
                'rate_limit' => $apiKey->rate_limit,
                'allowed_ips' => $apiKey->allowed_ips,
                'expires_at' => $apiKey->expires_at,
                'description' => $apiKey->description,
            ]
        );

        // Delete old key
        $apiKey->delete();

        Log::channel('admin_activity')->info(__('http/controllers/admin/settings/systems/admin_api_settings_controller.api_key_regenerated'), [
            'member_id' => Auth::guard('member')->id(),
            'old_api_key_id' => $id,
            'new_api_key_id' => $result['model']->id,
            'name' => $result['model']->name,
        ]);

        // Save generated key to session (display only once)
        session()->flash('generated_key', $result['plain_key']);
        session()->flash('generated_key_id', $result['model']->id);

        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.key_regenerated'));
    }
}
