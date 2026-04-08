{{--
This file is part of Dixlase.

Copyright (C) 2026 exc-D inc.
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

@extends('layouts.install')

@section('title', __('install/step3.database_title'))
@section('header', __('install/step3.database_header'))
@section('description', __('install/step3.database_description'))

@section('content')
<div x-data="{
    connectionSuccess: false,
    testResult: '{{ __('install/step3.db_test_required') }}',
    testResultClass: 'text-red-600 dark:text-red-400',
    async testDatabaseConnection() {
        const formData = new FormData();
        formData.append('db_connection', document.getElementById('db_connection').value);
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_database', document.getElementById('db_database').value);
        formData.append('db_username', document.getElementById('db_username').value);
        formData.append('db_password', document.getElementById('db_password').value);
        formData.append('_token', document.querySelector('input[name=_token]').value);

        try {
            const response = await fetch(document.getElementById('db-test-url').value, {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                this.testResult = document.getElementById('db-success-message').value;
                this.testResultClass = 'text-green-600 dark:text-green-400';
                this.connectionSuccess = true;
            } else {
                this.testResult = data.message || 'Connection failed';
                this.testResultClass = 'text-red-600 dark:text-red-400';
                this.connectionSuccess = false;
            }
        } catch (error) {
            this.testResult = 'Connection test failed';
            this.testResultClass = 'text-red-600 dark:text-red-400';
            this.connectionSuccess = false;
        }
    }
}">
<form action="{{ route('install.database.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- データベース接続設定セクション -->
    <section aria-labelledby="db-connection-heading">
        <h2 id="db-connection-heading" class="sr-only">{{ __('install/step3.database_connection_settings') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install/step3.database_connection_details') }}</legend>
            
            <div>
                <x-form-label for="db_connection" :text="__('install/step3.db_connection')" :required="true" />
                @php
                    $dbConnectionOptions = [
                        'mysql' => 'MySQL',
                        'sqlite' => 'SQLite',
                    ];
                @endphp
                <x-form-select
                    id="db_connection"
                    name="db_connection"
                    :options="$dbConnectionOptions"
                    :value="old('db_connection', session('install_data.db_connection', 'mysql'))"
                />
            </div>

            @php
                $appEnv = session('install_data.app_env', 'local');
                $defaultDbHost = $appEnv === 'local' ? 'mysql' : '127.0.0.1';
                $defaultDbUser = $appEnv === 'local' ? 'dixlase' : '';
                $defaultDbPassword = $appEnv === 'local' ? 'dixlase' : '';
            @endphp

            <div>
                <x-form-label for="db_host" :text="__('install/step3.db_host')" :required="true" />
                <x-form-text
                    name="db_host"
                    id="db_host"
                    :value="old('db_host', session('install_data.db_host', $defaultDbHost))"
                    :required="true"
                    class="input-full"
                />
            </div>

            <div>
                <x-form-label for="db_port" :text="__('install/step3.db_port')" :required="true" />
                <x-form-text
                    type="number"
                    name="db_port"
                    id="db_port"
                    :value="old('db_port', session('install_data.db_port', '3306'))"
                    :required="true"
                    class="input-full"
                />
            </div>

            <div>
                <x-form-label for="db_database" :text="__('install/step3.db_database')" :required="true" />
                <x-form-text
                    name="db_database"
                    id="db_database"
                    :value="old('db_database', session('install_data.db_database', 'dixlase'))"
                    :required="true"
                    class="input-full"
                />
            </div>

            <div>
                <x-form-label for="db_username" :text="__('install/step3.db_username')" :required="true" />
                <x-form-text
                    name="db_username"
                    id="db_username"
                    :value="old('db_username', session('install_data.db_username', $defaultDbUser))"
                    :required="true"
                    autocomplete="off"
                    class="input-full"
                />
            </div>

            <div>
                <x-form-label for="db_password" :text="__('install/step3.db_password')" :required="true" />
                <x-form-text
                    type="password"
                    name="db_password"
                    id="db_password"
                    :value="old('db_password', $defaultDbPassword)"
                    :required="true"
                    autocomplete="off"
                    :showPasswordToggle="true"
                    class="input-full"
                />
                <x-form-help-text :text="__('install/step3.db_password_required')" />
            </div>
        </fieldset>
    </section>

    <!-- データ保持設定セクション -->
    <section aria-labelledby="data-preservation-heading">
        <h2 id="data-preservation-heading" class="sr-only">{{ __('install/step3.data_preservation_settings') }}</h2>
        
        <fieldset>
            <legend class="sr-only">{{ __('install/step3.database_preservation_options') }}</legend>
            
            <x-form-toggle
                name="preserve_data"
                id="preserve_data"
                :checked="old('preserve_data', session('install_data.preserve_data', false)) === true"
                :label="__('install/step3.preserve_database')"
            />
            <x-form-help-text :text="__('install/step3.preserve_database_help')" />
        </fieldset>
    </section>

    <!-- 接続テストセクション -->
    <section aria-labelledby="connection-test-heading">
        <h2 id="connection-test-heading" class="sr-only">{{ __('install/step3.database_connection_test') }}</h2>
        
        <!-- 外部JSで使用するデータ -->
        <input type="hidden" id="db-test-url" value="{{ url('/install/test-db') }}">
        <input type="hidden" id="db-success-message" value="{{ __('install/step3.db_test_success') }}">
        
        <div class="text-center space-y-3">
            <p id="db-test-result" class="text-sm" :class="testResultClass" role="status" aria-live="polite" x-text="testResult">
            </p>
            
            <button 
                type="button"
                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition"
                x-on:click="testDatabaseConnection()">
                <i class="fas fa-plug mr-2"></i>{{ __('install/step3.test_db_connection') }}
            </button>
        </div>
    </section>



    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-center mt-6">
        <a href="{{ route('install.environment') }}"
            class="inline-flex items-center justify-center font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-4 py-2 text-sm bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500 mx-4">
            {{ __('install/common.back') }}
        </a>

        <div class="relative group mx-4">
            <button
                type="submit"
                id="next-button"
                x-bind:disabled="!connectionSuccess"
                :class="connectionSuccess
                    ? 'bg-blue-600 dark:bg-blue-500 hover:bg-blue-700 dark:hover:bg-blue-600 cursor-pointer'
                    : 'bg-blue-400 dark:bg-blue-400 hover:bg-blue-400 dark:hover:bg-blue-400 cursor-not-allowed'"
                class="inline-flex items-center justify-center font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-4 py-2 text-sm text-white focus:ring-blue-500"
            >
                {{ __('install/common.next') }}
            </button>
            <div id="tooltip"
                x-show="!connectionSuccess"
                class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 group-hover:opacity-100 transition-opacity
                bg-gray-800 dark:bg-gray-700 text-white text-xs rounded px-3 py-2 whitespace-nowrap z-10"
                role="tooltip">
                {{ __('install/step3.db_test_required') }}
            </div>
        </div>
    </nav>
</form>
</div>

@endsection