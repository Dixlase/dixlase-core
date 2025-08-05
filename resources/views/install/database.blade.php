@extends('layouts.install')

@section('title', __('install.database_title'))
@section('header', __('install.database_header'))
@section('description', __('install.database_description'))

@section('content')
<form action="{{ route('install.database.store') }}" method="POST" class="space-y-4">
    @csrf

    <div>
        <label class="block text-gray-700">{{ __('install.db_connection') }}</label>
        <select name="db_connection" id="db_connection" class="w-full p-2 border rounded-lg">
            <option value="mysql" {{ old('db_connection', session('install_data.db_connection', 'mysql')) == 'mysql' ? 'selected' : '' }}>MySQL</option>
            <option value="sqlite" {{ old('db_connection', session('install_data.db_connection', 'mysql')) == 'sqlite' ? 'selected' : '' }}>SQLite</option>
        </select>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.db_host') }}</label>
        @php
            $defaultDbHost = '127.0.0.1';
            $appEnv = session('install_data.app_env', 'local');
            if ($appEnv === 'local') {
                $defaultDbHost = 'mysql';
            }
        @endphp
        <input type="text" name="db_host" id="db_host"
            value="{{ old('db_host', session('install_data.db_host', $defaultDbHost)) }}"
            class="w-full p-2 border rounded-lg" required>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.db_port') }}</label>
        <input type="number" name="db_port" id="db_port"
            value="{{ old('db_port', session('install_data.db_port', '3306')) }}"
            class="w-full p-2 border rounded-lg" required>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.db_database') }}</label>
        <input type="text" name="db_database" id="db_database"
            value="{{ old('db_database', session('install_data.db_database', 'dixlase')) }}"
            class="w-full p-2 border rounded-lg" required>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.db_username') }}</label>
        @php
            $defaultDbUser = '';
            if ($appEnv === 'local') {
                $defaultDbUser = 'dixlase';
            }
        @endphp
        <input type="text" name="db_username" id="db_username"
            value="{{ old('db_username', session('install_data.db_username', $defaultDbUser)) }}"
            class="w-full p-2 border rounded-lg" required>
    </div>

    <div>
        <label class="block text-gray-700">{{ __('install.db_password') }}</label>
        @php
            $defaultDbPassword = '';
            if ($appEnv === 'local') {
                $defaultDbPassword = 'dixlase';
            }
        @endphp
        <div class="relative">
            <input type="password" name="db_password" id="db_password"
                value="{{ old('db_password', session('install_data.db_password', $defaultDbPassword)) }}"
                class="w-full p-2 border rounded-lg" required>
            <button type="button" onclick="togglePassword()" class="absolute right-3 top-3">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <small class="text-gray-500">{{ __('install.db_password_required') }}</small>
    </div>

    <div class="flex items-center">
        <input type="checkbox" name="preserve_data" id="preserve_data" value="1" 
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            {{ old('preserve_data', session('install_data.preserve_data', false)) ? 'checked' : '' }}>
        <label for="preserve_data" class="ml-2 block text-sm text-gray-700">
            {{ __('install.preserve_database') }}
        </label>
    </div>
    <p class="text-sm text-gray-500 mb-4">{{ __('install.preserve_database_help') }}</p>

    <!-- ✅ DB接続テストボタン -->
    <button type="button" onclick="testDatabaseConnection()"
        class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition">
        {{ __('install.test_db_connection') }}
    </button>

    <p id="db-test-result" class="text-sm mt-2 text-red-500">
        {{ __('install.db_test_required') }}
    </p>

    <!-- ✅ ナビゲーションボタン（戻る・次へ） -->
    <div class="flex justify-between mt-6">
        <a href="{{ route('install.security') }}"
            class="bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600">
            {{ __('install.back') }}
        </a>

        <!-- ✅ ツールチップ付き `次へ` ボタン -->
        <div class="relative group">
            <button type="submit" id="next-button" disabled
                class="bg-blue-400 text-white py-2 px-4 rounded-lg cursor-not-allowed">
                {{ __('install.next') }}
            </button>
            <div id="tooltip" class="absolute bottom-full left-1/2 transform -translate-x-1/2 opacity-0 group-hover:opacity-100
                bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap">
                {{ __('install.tooltip_test_db') }}
            </div>
        </div>
    </div>
</form>

<script>
    let isDbTestSuccessful = false;

    function togglePassword() {
        let passwordField = document.getElementById("db_password");
        let type = passwordField.type === "password" ? "text" : "password";
        passwordField.type = type;
    }

    function testDatabaseConnection() {
        let dbHost = document.getElementById('db_host').value;
        let dbPort = document.getElementById('db_port').value;
        let dbDatabase = document.getElementById('db_database').value;
        let dbUsername = document.getElementById('db_username').value;
        let dbPassword = document.getElementById('db_password').value;
        let dbConnection = document.getElementById('db_connection').value;

        fetch("{{ url('/install/test-db') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                db_connection: dbConnection,
                db_host: dbHost,
                db_port: dbPort,
                db_database: dbDatabase,
                db_username: dbUsername,
                db_password: dbPassword
            })
        })
        .then(response => response.json())
        .then(data => {
            let resultElement = document.getElementById('db-test-result');
            resultElement.innerText = data.message;
            resultElement.style.color = data.success ? "green" : "red";

            isDbTestSuccessful = data.success;
            updateNextButtonState();
        })
        .catch(error => {
            console.error('Error:', error);
            isDbTestSuccessful = false;
            updateNextButtonState();
        });
    }

    function updateNextButtonState() {
        let nextButton = document.getElementById('next-button');
        let resultMessage = document.getElementById('db-test-result');
        let tooltip = document.getElementById('tooltip');

        if (isDbTestSuccessful) {
            nextButton.disabled = false;
            nextButton.classList.remove("bg-blue-400", "cursor-not-allowed");
            nextButton.classList.add("bg-blue-600", "hover:bg-blue-700");

            resultMessage.innerText = "{{ __('install.db_test_success') }}";
            resultMessage.classList.remove("text-red-500");
            resultMessage.classList.add("text-green-500");

            tooltip.classList.add("hidden"); // ツールチップを非表示
        } else {
            nextButton.disabled = true;
            nextButton.classList.remove("bg-blue-600", "hover:bg-blue-700");
            nextButton.classList.add("bg-blue-400", "cursor-not-allowed");

            tooltip.classList.remove("hidden"); // ツールチップを表示
        }
    }
</script>
@endsection