@extends('layouts.install')

@section('title', __('install.database_title'))
@section('header', __('install.database_header'))
@section('description', __('install.database_description'))

@section('content')
<form action="{{ route('install.database.store') }}" method="POST" class="space-y-6">
    @csrf

    <!-- データベース接続設定セクション -->
    <section aria-labelledby="db-connection-heading">
        <h2 id="db-connection-heading" class="sr-only">{{ __('install.database_connection_settings') }}</h2>
        
        <fieldset class="space-y-4">
            <legend class="sr-only">{{ __('install.database_connection_details') }}</legend>
            
            <div>
                <x-form-label for="db_connection" :text="__('install.db_connection')" :required="true" />
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
                    class="input-lg"
                />
            </div>

            @php
                $appEnv = session('install_data.app_env', 'local');
                $defaultDbHost = $appEnv === 'local' ? 'mysql' : '127.0.0.1';
                $defaultDbUser = $appEnv === 'local' ? 'dixlase' : '';
                $defaultDbPassword = $appEnv === 'local' ? 'dixlase' : '';
            @endphp

            <div>
                <x-form-label for="db_host" :text="__('install.db_host')" :required="true" />
                <x-form-text
                    name="db_host"
                    id="db_host"
                    :value="old('db_host', session('install_data.db_host', $defaultDbHost))"
                    :required="true"
                    class="input-full"
                />
            </div>

            <div>
                <x-form-label for="db_port" :text="__('install.db_port')" :required="true" />
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
                <x-form-label for="db_database" :text="__('install.db_database')" :required="true" />
                <x-form-text
                    name="db_database"
                    id="db_database"
                    :value="old('db_database', session('install_data.db_database', 'dixlase'))"
                    :required="true"
                    class="input-full"
                />
            </div>

            <div>
                <x-form-label for="db_username" :text="__('install.db_username')" :required="true" />
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
                <x-form-label for="db_password" :text="__('install.db_password')" :required="true" />
                <div class="relative">
                    <x-form-text
                        type="password"
                        name="db_password"
                        id="db_password"
                        :value="old('db_password', $defaultDbPassword)"
                        :required="true"
                        autocomplete="off"
                        class="input-full pr-10"
                    />
                    <button type="button" onclick="togglePassword()" 
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                        <i id="password-eye" class="fas fa-eye"></i>
                    </button>
                </div>
                <x-form-help-text :text="__('install.db_password_required')" />
            </div>
        </fieldset>
    </section>

    <!-- データ保持設定セクション -->
    <section aria-labelledby="data-preservation-heading">
        <h2 id="data-preservation-heading" class="sr-only">{{ __('install.data_preservation_settings') }}</h2>
        
        <fieldset>
            <legend class="sr-only">{{ __('install.database_preservation_options') }}</legend>
            
            <x-form-toggle
                name="preserve_data"
                id="preserve_data"
                :checked="old('preserve_data', session('install_data.preserve_data', false)) === true"
                :label="__('install.preserve_database')"
            />
            <x-form-help-text :text="__('install.preserve_database_help')" />
        </fieldset>
    </section>

    <!-- 接続テストセクション -->
    <section aria-labelledby="connection-test-heading">
        <h2 id="connection-test-heading" class="sr-only">{{ __('install.database_connection_test') }}</h2>
        
        <!-- 外部JSで使用するデータ -->
        <input type="hidden" id="db-test-url" value="{{ url('/install/test-db') }}">
        <input type="hidden" id="db-success-message" value="{{ __('install.db_test_success') }}">
        
        <div class="text-center space-y-3">
            <p id="db-test-result" class="text-sm text-red-600 dark:text-red-400" role="status" aria-live="polite">
                {{ __('install.db_test_required') }}
            </p>
            
            <x-form-button 
                type="button"
                variant="success"
                :label="__('install.test_db_connection')"
                icon="fas fa-plug"
                onclick="testDatabaseConnection()"
            />
        </div>
    </section>



    <!-- フォームナビゲーション -->
    <nav aria-label="{{ __('install.form_navigation') }}" class="flex justify-between mt-6">
        <a href="{{ route('install.environment') }}"
            class="inline-flex items-center justify-center font-semibold rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200 px-4 py-2 text-sm bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500">
            {{ __('install.back') }}
        </a>

        <div class="relative group">
            <x-form-button 
                type="submit"
                id="next-button"
                :label="__('install.next')"
                variant="primary"
                disabled
                class="bg-blue-400 dark:bg-blue-400 hover:bg-blue-400 dark:hover:bg-blue-400 cursor-not-allowed"
            />
            <div id="tooltip" 
                class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 opacity-0 group-hover:opacity-100 transition-opacity
                bg-gray-800 dark:bg-gray-700 text-white text-xs rounded px-3 py-2 whitespace-nowrap z-10"
                role="tooltip">
                {{ __('install.tooltip_test_db') }}
            </div>
        </div>
    </nav>
</form>

@endsection