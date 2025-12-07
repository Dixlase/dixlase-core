{{--
This file is part of Dixlase.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
<div class="mx-auto" x-data="apiSettings()">

    {{-- 生成されたキーの表示（一度だけ） --}}
    @if(session('generated_key'))
    <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-600 rounded-lg">
        <div class="flex items-start">
            <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-3"></i>
            <div class="flex-1">
                <h3 class="font-bold text-yellow-800 dark:text-yellow-200">{{ __('admin.settings.api.key_generated_warning') }}</h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">{{ __('admin.settings.api.key_generated_warning_detail') }}</p>
                <div class="mt-3 flex items-center gap-2">
                    <code id="generated-key" class="flex-1 p-3 bg-white dark:bg-gray-800 border border-yellow-400 rounded font-mono text-sm break-all">{{ session('generated_key') }}</code>
                    <button type="button" onclick="copyToClipboard('generated-key')" class="btn btn-secondary">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- API設定フォーム --}}
    <form action="{{ route('admin.settings.api.update') }}" method="POST">
        @csrf

        <section>
            <h2>{{ __('admin.settings.api.general_settings') }}</h2>

            <fieldset>
                <legend>{{ __('admin.settings.api.api_enabled') }}</legend>
                <x-form.radio-group
                    name="api_enabled"
                    :options="[
                        '1' => __('common.enabled'),
                        '0' => __('common.disabled'),
                    ]"
                    :value="old('api_enabled', $settings['api_enabled'] ? '1' : '0')"
                />
                <p>{{ __('admin.settings.api.api_enabled_help') }}</p>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin.settings.api.signature_required') }}</legend>
                <x-form.radio-group
                    name="api_signature_required"
                    :options="[
                        '1' => __('common.required'),
                        '0' => __('common.optional'),
                    ]"
                    :value="old('api_signature_required', $settings['api_signature_required'] ? '1' : '0')"
                />
                <p>{{ __('admin.settings.api.signature_required_help') }}</p>
            </fieldset>

            <fieldset>
                <legend>{{ __('admin.settings.api.default_rate_limit') }}</legend>
                <div class="flex items-center gap-2">
                    <x-form.text
                        name="api_rate_limit"
                        type="number"
                        :value="old('api_rate_limit', $settings['api_rate_limit'])"
                        class="input-sm"
                        min="1"
                        max="10000"
                    />
                    <span class="text-gray-600 dark:text-gray-400">{{ __('admin.settings.api.requests_per_minute') }}</span>
                </div>
                <p>{{ __('admin.settings.api.rate_limit_help') }}</p>
            </fieldset>

            <div class="flex justify-end mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>{{ __('common.save') }}
                </button>
            </div>
        </section>
    </form>

    {{-- APIキー管理 --}}
    <section class="mt-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="mb-0">{{ __('admin.settings.api.api_keys') }}</h2>
            <button type="button" @click="showCreateModal = true" class="btn btn-primary">
                <i class="fas fa-plus mr-2"></i>{{ __('admin.settings.api.create_key') }}
            </button>
        </div>

        @if($apiKeys->isEmpty())
        <div class="p-8 text-center bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <i class="fas fa-key text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">{{ __('admin.settings.api.no_keys') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="text-left">{{ __('admin.settings.api.key_name') }}</th>
                        <th class="text-left">{{ __('admin.settings.api.key_prefix') }}</th>
                        <th class="text-left">{{ __('admin.settings.api.environment') }}</th>
                        <th class="text-left">{{ __('admin.settings.api.status') }}</th>
                        <th class="text-left">{{ __('admin.settings.api.last_used') }}</th>
                        <th class="text-left">{{ __('admin.settings.api.usage_count') }}</th>
                        <th class="text-right">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($apiKeys as $key)
                    <tr class="{{ session('generated_key_id') == $key->id ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}">
                        <td>
                            <div class="font-medium">{{ $key->name }}</div>
                            @if($key->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($key->description, 50) }}</div>
                            @endif
                        </td>
                        <td>
                            <code class="text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $key->getMaskedKey() }}</code>
                        </td>
                        <td>
                            @if($key->environment === 'live')
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                <i class="fas fa-circle text-[6px] mr-1"></i>Live
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                <i class="fas fa-circle text-[6px] mr-1"></i>Test
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($key->isExpired())
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                {{ __('admin.settings.api.expired') }}
                            </span>
                            @elseif($key->is_active)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                {{ __('common.active') }}
                            </span>
                            @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ __('common.inactive') }}
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($key->last_used_at)
                            <span class="text-sm">{{ $key->last_used_at->diffForHumans() }}</span>
                            @else
                            <span class="text-sm text-gray-400">{{ __('admin.settings.api.never_used') }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="text-sm">{{ number_format($key->usage_count) }}</span>
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" @click="viewKey({{ $key->id }})" class="btn btn-sm btn-secondary" title="{{ __('common.view') }}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <form action="{{ route('admin.settings.api.regenerate-key', $key->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('admin.settings.api.regenerate_confirm') }}')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning" title="{{ __('admin.settings.api.regenerate') }}">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                                <form action="{{ route('admin.settings.api.revoke-key', $key->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('admin.settings.api.revoke_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="{{ __('common.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>

    {{-- APIキー作成モーダル --}}
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showCreateModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showCreateModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showCreateModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.settings.api.generate-key') }}" method="POST">
                    @csrf
                    <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('admin.settings.api.create_key') }}</h3>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.api.key_name') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="name" required class="input-full" placeholder="{{ __('admin.settings.api.key_name_placeholder') }}">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.api.environment') }}</label>
                                <select name="environment" class="input-full">
                                    <option value="live">Live ({{ __('admin.settings.api.env_live_desc') }})</option>
                                    <option value="test">Test ({{ __('admin.settings.api.env_test_desc') }})</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.api.scopes') }}</label>
                                <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-200 dark:border-gray-600 rounded p-3">
                                    @foreach($availableScopes as $scope => $label)
                                    <label class="flex items-center">
                                        <input type="checkbox" name="scopes[]" value="{{ $scope }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.api.rate_limit') }}</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="rate_limit" class="input-sm" min="1" max="10000" placeholder="{{ __('admin.settings.api.unlimited') }}">
                                    <span class="text-sm text-gray-500">{{ __('admin.settings.api.requests_per_minute') }}</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.api.allowed_ips') }}</label>
                                <input type="text" name="allowed_ips" class="input-full" placeholder="{{ __('admin.settings.api.allowed_ips_placeholder') }}">
                                <p class="text-xs text-gray-500 mt-1">{{ __('admin.settings.api.allowed_ips_help') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.api.expires_at') }}</label>
                                <input type="date" name="expires_at" class="input-full" min="{{ now()->addDay()->format('Y-m-d') }}">
                                <p class="text-xs text-gray-500 mt-1">{{ __('admin.settings.api.expires_at_help') }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.settings.api.description') }}</label>
                                <textarea name="description" rows="2" class="input-full" placeholder="{{ __('admin.settings.api.description_placeholder') }}"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-key mr-2"></i>{{ __('admin.settings.api.generate') }}
                        </button>
                        <button type="button" @click="showCreateModal = false" class="btn btn-secondary">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- APIキー詳細モーダル --}}
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showViewModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showViewModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">{{ __('admin.settings.api.key_details') }}</h3>
                    <div x-html="keyDetails" class="space-y-3"></div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex justify-end">
                    <button type="button" @click="showViewModal = false" class="btn btn-secondary">
                        {{ __('common.close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function apiSettings() {
    return {
        showCreateModal: false,
        showViewModal: false,
        keyDetails: '',
        
        viewKey(id) {
            const keys = @json($apiKeys);
            const key = keys.find(k => k.id === id);
            if (!key) return;
            
            let scopesHtml = '';
            if (key.scopes && key.scopes.length > 0) {
                scopesHtml = key.scopes.map(s => `<span class="inline-block px-2 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded mr-1 mb-1">${s}</span>`).join('');
            } else {
                scopesHtml = '<span class="text-gray-400">{{ __('admin.settings.api.no_scopes') }}</span>';
            }
            
            let allowedIpsHtml = '';
            if (key.allowed_ips && key.allowed_ips.length > 0) {
                allowedIpsHtml = key.allowed_ips.join(', ');
            } else {
                allowedIpsHtml = '<span class="text-gray-400">{{ __('admin.settings.api.all_ips_allowed') }}</span>';
            }
            
            this.keyDetails = `
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="text-gray-500 dark:text-gray-400">{{ __('admin.settings.api.key_name') }}</div>
                    <div class="font-medium">${key.name}</div>
                    
                    <div class="text-gray-500 dark:text-gray-400">{{ __('admin.settings.api.environment') }}</div>
                    <div>${key.environment === 'live' ? '<span class="text-green-600">Live</span>' : '<span class="text-orange-600">Test</span>'}</div>
                    
                    <div class="text-gray-500 dark:text-gray-400">{{ __('admin.settings.api.key_prefix') }}</div>
                    <div><code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">${key.key_prefix}********...</code></div>
                    
                    <div class="text-gray-500 dark:text-gray-400">{{ __('admin.settings.api.rate_limit') }}</div>
                    <div>${key.rate_limit ? key.rate_limit + ' {{ __('admin.settings.api.requests_per_minute') }}' : '{{ __('admin.settings.api.unlimited') }}'}</div>
                    
                    <div class="text-gray-500 dark:text-gray-400">{{ __('admin.settings.api.expires_at') }}</div>
                    <div>${key.expires_at ? new Date(key.expires_at).toLocaleDateString() : '{{ __('admin.settings.api.no_expiry') }}'}</div>
                    
                    <div class="text-gray-500 dark:text-gray-400">{{ __('admin.settings.api.usage_count') }}</div>
                    <div>${key.usage_count.toLocaleString()}</div>
                    
                    <div class="text-gray-500 dark:text-gray-400">{{ __('admin.settings.api.created_at') }}</div>
                    <div>${new Date(key.created_at).toLocaleString()}</div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">{{ __('admin.settings.api.scopes') }}</div>
                    <div>${scopesHtml}</div>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">{{ __('admin.settings.api.allowed_ips') }}</div>
                    <div class="text-sm">${allowedIpsHtml}</div>
                </div>
                
                ${key.description ? `
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                    <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">{{ __('admin.settings.api.description') }}</div>
                    <div class="text-sm">${key.description}</div>
                </div>
                ` : ''}
            `;
            
            this.showViewModal = true;
        }
    };
}

function copyToClipboard(elementId) {
    const element = document.getElementById(elementId);
    const text = element.textContent || element.innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('{{ __('admin.settings.api.copied_to_clipboard') }}');
    });
}
</script>
@endpush
