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

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Install;

use Illuminate\Routing\Controller;

/**
 * インストールコントローラー
 */
class InstallController extends Controller
{
    // 利用可能な言語のリスト
    protected $availableLocales;

    private $total_steps = 5;

    public function __construct()
    {
        $this->availableLocales = array_keys(config('language.languages', []));
    }

    // 最初の画面
    public function index()
    {
        // ✅ インストール開始時にセッションデータを削除（言語設定は保持）
        $installData = session('install_data', []);
        session()->forget('install_data');
        session(['install_data' => $installData]);

        // 言語設定をセッション/クッキーから取得、デフォルトはブラウザの言語設定を考慮

        $browserLocale = substr(request()->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        $cookieLocale = request()->cookie('install_locale');
        $sessionLocale = session('install_locale');
        $candidate = $sessionLocale ?: $cookieLocale;
        $locale = $candidate && in_array($candidate, $this->availableLocales)
            ? $candidate
            : (in_array($browserLocale, $this->availableLocales) ? $browserLocale : 'en');
        app()->setLocale($locale);

        $requirements = $this->checkServerRequirements();

        return view('install.index', [
            'requirements' => $requirements,
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
        ]);
    }

    /**
     * サーバーが Laravel 12 の要件を満たしているか確認
     *
     * @return array
     */
    protected function checkServerRequirements()
    {
        // 必須の拡張機能（不足時はインストール不可）
        $requiredExtensions = [
            'Ctype' => extension_loaded('ctype'),
            'cURL' => extension_loaded('curl'),
            'DOM' => extension_loaded('dom'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'JSON' => extension_loaded('json'),
            'Mbstring' => extension_loaded('mbstring'),
            'OpenSSL' => extension_loaded('openssl'),
            'PCRE' => extension_loaded('pcre'),
            'PDO' => extension_loaded('pdo'),
            'PDO MySQL' => extension_loaded('pdo_mysql'),
            'Tokenizer' => extension_loaded('tokenizer'),
            'XML' => extension_loaded('xml'),
            'GD' => extension_loaded('gd'),
            'Intl' => extension_loaded('intl'),
            'Zip' => extension_loaded('zip'),
        ];

        // 推奨の拡張機能（不足時も続行可能だが、パフォーマンスや機能に影響）
        $recommendedExtensions = [
            'Redis' => extension_loaded('redis'),
            'OPcache' => extension_loaded('Zend OPcache'),
        ];

        // オプションの拡張機能（あれば便利）
        $optionalExtensions = [
            'BCMath' => extension_loaded('bcmath'),
        ];

        // ストレージサブディレクトリの存在確認と自動作成
        $storageDirs = [
            'framework/views',
            'framework/cache/data',
            'framework/sessions',
            'logs',
        ];
        foreach ($storageDirs as $dir) {
            $path = storage_path($dir);
            if (! is_dir($path)) {
                @mkdir($path, 0777, true);
            }
        }

        // パーミッションチェック
        $permissions = [
            'storage' => is_writable(storage_path()),
            'storage/framework/views' => is_writable(storage_path('framework/views')),
            'storage/framework/cache' => is_writable(storage_path('framework/cache')),
            'storage/framework/sessions' => is_writable(storage_path('framework/sessions')),
            'storage/logs' => is_writable(storage_path('logs')),
            'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            '.env' => is_writable(base_path()) || is_writable(base_path('.env')),
            'public' => is_writable(base_path('public')),
        ];

        // PHP設定チェック
        $phpSettings = [
            'memory_limit' => $this->checkMemoryLimit(128),
            'max_execution_time' => $this->checkMaxExecutionTime(60),
        ];

        // テーマの存在チェック（theme.json を持つディレクトリが1つ以上あるか）
        $themesPath = base_path('themes');
        $hasTheme = false;
        if (is_dir($themesPath)) {
            foreach (new \DirectoryIterator($themesPath) as $dir) {
                if ($dir->isDot() || ! $dir->isDir()) {
                    continue;
                }
                if (file_exists($dir->getPathname().'/theme.json')) {
                    $hasTheme = true;
                    break;
                }
            }
        }

        return [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'required_extensions' => $requiredExtensions,
            'recommended_extensions' => $recommendedExtensions,
            'optional_extensions' => $optionalExtensions,
            'permissions' => $permissions,
            'php_settings' => $phpSettings,
            'has_theme' => $hasTheme,
        ];
    }

    /**
     * memory_limit が最低値を満たしているかチェック
     */
    private function checkMemoryLimit(int $requiredMb): array
    {
        $limit = ini_get('memory_limit');
        if ($limit === '-1') {
            return ['ok' => true, 'current' => __('install/index.php_settings.unlimited'), 'required' => $requiredMb.'M'];
        }
        $currentMb = (int) $limit;
        if (str_contains(strtolower($limit), 'g')) {
            $currentMb = (int) $limit * 1024;
        }

        return ['ok' => $currentMb >= $requiredMb, 'current' => $limit, 'required' => $requiredMb.'M'];
    }

    /**
     * max_execution_time が最低値を満たしているかチェック
     */
    private function checkMaxExecutionTime(int $requiredSeconds): array
    {
        $current = (int) ini_get('max_execution_time');
        // 0 は無制限
        $ok = ($current === 0) || ($current >= $requiredSeconds);

        return [
            'ok' => $ok,
            'current' => $current === 0 ? __('install/index.php_settings.unlimited') : $current.'s',
            'required' => $requiredSeconds.'s',
        ];
    }
}
