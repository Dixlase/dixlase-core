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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Http\Controllers\Install;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\File;

/**
 * インストールコントローラーの基底クラス
 */
abstract class BaseInstallController extends Controller
{
    /**
     * 利用可能な言語のリスト
     */
    protected $availableLocales;

    /**
     * インストールの総ステップ数
     */
    protected $total_steps = 5;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->availableLocales = array_keys(config('language.languages', []));
    }

    /**
     * 現在のロケールを取得
     */
    protected function getCurrentLocale(): string
    {
        $browserLocale = substr(request()->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        $cookieLocale = request()->cookie('install_locale');
        $sessionLocale = session('install_locale');
        $candidate = $sessionLocale ?: $cookieLocale;

        return $candidate && in_array($candidate, $this->availableLocales)
            ? $candidate
            : (in_array($browserLocale, $this->availableLocales) ? $browserLocale : 'en');
    }

    /**
     * ビューに渡す共通データを取得
     */
    protected function getViewData(int $currentStep): array
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        return [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
            'current_step' => $currentStep,
            'total_steps' => $this->total_steps,
        ];
    }

    /**
     * 言語切り替えと.envの更新
     *
     * @param  string  $locale
     * @return \Illuminate\Http\JsonResponse
     */
    public function setLanguage($locale)
    {
        // 有効なロケールのみを許可
        if (in_array($locale, $this->availableLocales)) {
            // セッションに保存（複数のキーで保存して確実に保持）
            session([
                'install_locale' => $locale,
                'app.locale' => $locale,
                'locale' => $locale,
            ]);

            // 現在のリクエストのロケールも即時変更
            app()->setLocale($locale);

            // .envファイルを同期的に更新
            $envPath = base_path('.env');
            if (file_exists($envPath) && is_writable($envPath)) {
                $updates = [
                    'APP_LOCALE' => $locale,
                    'APP_FALLBACK_LOCALE' => $locale,
                    'APP_FAKER_LOCALE' => $locale.'_'.strtoupper($locale),
                ];

                $this->updateEnv($updates);
            }

            // レスポンス用の設定
            $response = [
                'success' => true,
                'locale' => $locale,
                'message' => __('install/common.language_changed'),
            ];

            // 常にJSONで返す（リダイレクトなし）＋ クッキーで永続化
            return response()->json($response)
                ->cookie('install_locale', $locale, 60 * 24 * 30);
        } else {
            return response()->json([
                'success' => false,
                'message' => '無効な言語が選択されました。',
            ], 400);
        }
    }

    /**
     * .envファイルを更新する
     */
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        // .envがない場合は.env.exampleからコピー
        if (! File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $env = File::get($envPath);

        foreach ($values as $key => $value) {
            // EnvHelperのformatEnvValueを使用したいが、protectedなので独自実装
            $formattedValue = $this->formatEnvValue($value);

            if (preg_match("/^{$key}=/m", $env)) {
                // 既存の値を更新
                $env = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$formattedValue}",
                    $env
                );
            } else {
                // .envに存在しない場合は末尾に追加
                $env .= "\n{$key}={$formattedValue}";
            }
        }

        File::put($envPath, $env);
    }

    /**
     * .env用に値をフォーマットする
     *
     * @param  mixed  $value
     */
    protected function formatEnvValue($value): string
    {
        // nullの場合は空文字列
        if ($value === null) {
            return '';
        }

        // booleanの場合は文字列に変換
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        // 空文字列、スペース、特殊文字を含む場合は引用符で囲む
        if ($value === '' ||
            preg_match('/[\s"\'#$]/', $value) ||
            str_contains($value, '=')) {
            // 既に引用符で囲まれている場合はそのまま
            if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                return $value;
            }

            // ダブルクォートで囲む（内部のダブルクォートはエスケープ）
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
