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

namespace App\Http\Controllers\Admin\Settings\Systems;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\ApiKey;
use App\Contracts\Repositories\ApiSettingRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemApiUpdateRequest;
use App\Http\Requests\Admin\Settings\Systems\AdminSystemApiGenerateKeyRequest;

class AdminApiSettingsController extends AdminLoggedInController
{
    /**
     * API設定リポジトリ
     */
    protected ApiSettingRepositoryInterface $apiSettingRepository;

    /**
     * コンストラクタ
     */
    public function __construct(ApiSettingRepositoryInterface $apiSettingRepository)
    {
        parent::__construct();
        $this->apiSettingRepository = $apiSettingRepository;
    }

    /**
     * API設定画面を表示
     */
    public function index()
    {
        // APIキー一覧を取得
        $apiKeys = ApiKey::with('creator')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // API設定を取得
        $settings = [
            'api_enabled' => filter_var($this->apiSettingRepository->get('api_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'api_rate_limit' => (int) $this->apiSettingRepository->get('api_rate_limit', 60),
            'api_signature_required' => filter_var($this->apiSettingRepository->get('api_signature_required', true), FILTER_VALIDATE_BOOLEAN),
        ];
        
        // 利用可能なスコープ
        $availableScopes = ApiKey::availableScopes();
        
        $this->viewParams['apiKeys'] = $apiKeys;
        $this->viewParams['settings'] = $settings;
        $this->viewParams['availableScopes'] = $availableScopes;
        
        return view('admin.settings.systems.api.index', $this->viewParams);
    }

    /**
     * API設定を更新
     */
    public function update(AdminSystemApiUpdateRequest $request)
    {
        $validated = $request->validated();
        
        $this->apiSettingRepository->set('api_enabled', $validated['api_enabled'] ?? false);
        $this->apiSettingRepository->set('api_rate_limit', $validated['api_rate_limit'] ?? 60);
        $this->apiSettingRepository->set('api_signature_required', $validated['api_signature_required'] ?? true);
        
        Log::channel('admin_activity')->info('API設定を更新しました', [
            'member_id' => Auth::guard('member')->id(),
            'settings' => $validated,
        ]);
        
        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.update_success'));
    }

    /**
     * 新しいAPIキーを生成
     */
    public function generateKey(AdminSystemApiGenerateKeyRequest $request)
    {
        $validated = $request->validated();
        
        // 許可IPリストをパース
        $allowedIps = null;
        if (!empty($validated['allowed_ips'])) {
            $allowedIps = array_filter(
                array_map('trim', explode(',', $validated['allowed_ips']))
            );
        }
        
        // APIキー生成
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
        
        Log::channel('admin_activity')->info('APIキーを生成しました', [
            'member_id' => Auth::guard('member')->id(),
            'api_key_id' => $result['model']->id,
            'name' => $validated['name'],
            'environment' => $validated['environment'],
        ]);
        
        // 生成されたキーをセッションに保存（一度だけ表示）
        session()->flash('generated_key', $result['plain_key']);
        session()->flash('generated_key_id', $result['model']->id);
        
        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.key_generated'));
    }

    /**
     * APIキーを無効化（削除）
     */
    public function revokeKey(int $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        
        $keyName = $apiKey->name;
        $apiKey->delete();
        
        Log::channel('admin_activity')->info('APIキーを削除しました', [
            'member_id' => Auth::guard('member')->id(),
            'api_key_id' => $id,
            'name' => $keyName,
        ]);
        
        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.key_revoked'));
    }

    /**
     * APIキーを再生成
     */
    public function regenerateKey(int $id)
    {
        $apiKey = ApiKey::findOrFail($id);
        
        // 新しいキーを生成
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
        
        // 古いキーを削除
        $apiKey->delete();
        
        Log::channel('admin_activity')->info('APIキーを再生成しました', [
            'member_id' => Auth::guard('member')->id(),
            'old_api_key_id' => $id,
            'new_api_key_id' => $result['model']->id,
            'name' => $result['model']->name,
        ]);
        
        // 生成されたキーをセッションに保存（一度だけ表示）
        session()->flash('generated_key', $result['plain_key']);
        session()->flash('generated_key_id', $result['model']->id);
        
        return redirect()->route('admin.settings.systems.api')
            ->with('success', __('admin/settings/systems/api.key_regenerated'));
    }
}
