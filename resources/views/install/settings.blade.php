<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>インストール</title>
</head>
<body>
    <h1>インストール画面</h1>

    @if(session('error'))
        <div>{{ session('error') }}</div>
    @endif

    <!-- バリデーションエラーメッセージの表示 -->
    @if(isset($errors) && $errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('/install/settings') }}" method="POST">
        @csrf
        <div>
            <label for="site_name">サイト名:</label>
            <input type="text" name="site_name" id="site_name" value="{{ old('site_name') }}" required>
        </div>
        <div>
            <label for="admin_email">管理者メールアドレス:</label>
            <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" required>
        </div>
        <div>
            <label for="admin_password">管理者パスワード:</label>
            <input type="password" name="admin_password" id="admin_password" required>
        </div>
        <div>
            <label for="admin_password_confirmation">管理者パスワード確認:</label>
            <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" required>
        </div>

        <!-- サイト設定の入力フィールド -->
        <div id="db_type">
            <label for="db_connection">データベースの種類:</label>
            <select name="db_connection" id="db_connection" onchange="toggleDbFields(this.value)">
                <option value="mysql">MySQL</option>
                <option value="sqlite">SQLite</option>
            </select>
        </div>

        <div id="mysql_fields">
            <div>
                <label for="db_host">データベースホスト:</label>
                <input type="text" name="db_host" id="db_host" value="127.0.0.1">
            </div>

            <div>
                <label for="db_database">データベース名:</label>
                <input type="text" name="db_database" id="db_database" value="my_database">
            </div>

            <div>
                <label for="db_username">データベースユーザー名:</label>
                <input type="text" name="db_username" id="db_username" value="root">
            </div>

            <div>
                <label for="db_password">データベースパスワード:</label>
                <input type="password" name="db_password" id="db_password">
            </div>
        </div>

        <div id="sqlite_fields" style="display: none;">
            <label for="db_database_sqlite">DBファイルパス:</label>
            <input type="text" name="db_database_sqlite" id="db_database_sqlite" value="{{ database_path('database.sqlite') }}">
        </div>

        {{--
        <div>
            <label for="mail_host">メールホスト:</label>
            <input type="text" name="mail_host" id="mail_host" value="{{ old('mail_host') }}" required>
        </div>
        <div>
            <label for="mail_port">メールポート:</label>
            <input type="text" name="mail_port" id="mail_port" value="{{ old('mail_port') }}" required>
        </div>
        <div>
            <label for="mail_username">メールユーザー名:</label>
            <input type="text" name="mail_username" id="mail_username" value="{{ old('mail_username') }}" required>
        </div>
        <div>
            <label for="mail_password">メールパスワード:</label>
            <input type="password" name="mail_password" id="mail_password">
        </div>
        --}}
        <div>
            <button type="submit">インストール</button>
        </div>
    </form>
    <script>
        function toggleDbFields(value) {
            document.getElementById('mysql_fields').style.display = value === 'mysql' ? 'block' : 'none';
            document.getElementById('sqlite_fields').style.display = value === 'sqlite' ? 'block' : 'none';
        }
    </script>
</body>
</html>
