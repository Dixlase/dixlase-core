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

namespace App\Http\Middleware;

use App\Helpers\LocaleHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 言語設定ミドルウェア
 *
 * URLから言語を検出し、アプリケーションの言語を設定します。
 * サブディレクトリ方式: /ja/about, /en/contact
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 優先言語を取得
        $locale = $this->getPreferredLocale($request);

        // 言語を設定
        if ($locale && LocaleHelper::isSupported($locale)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }

        return $next($request);
    }

    /**
     * 優先言語を取得
     */
    protected function getPreferredLocale(Request $request): string
    {
        // 1. 管理メンバーのプロフィール言語設定を最優先（管理バー表示時）
        if (auth('member')->check()) {
            $member = auth('member')->user();

            if ($member && $member->locale) {
                $memberLocale = $member->locale;
                // Enumの場合は値を取得
                if ($memberLocale instanceof \App\Enums\Locale) {
                    $memberLocale = $memberLocale->value;
                }

                if (LocaleHelper::isSupported($memberLocale)) {
                    return $memberLocale;
                }
            }
        }

        // 2. セッションから取得
        $sessionLocale = LocaleHelper::getSessionLocale();
        if ($sessionLocale && LocaleHelper::isSupported($sessionLocale)) {
            return $sessionLocale;
        }

        // 3. ログイン中のユーザーの設定から取得
        $userLocale = LocaleHelper::getUserPreferredLocale();
        if ($userLocale && LocaleHelper::isSupported($userLocale)) {
            return $userLocale;
        }

        // 4. ブラウザの言語設定から取得
        $browserLocale = $this->getBrowserLocale($request);
        if ($browserLocale && LocaleHelper::isSupported($browserLocale)) {
            return $browserLocale;
        }

        // 5. デフォルト言語
        $defaultLocale = LocaleHelper::getDefaultLocale();

        return $defaultLocale;
    }

    /**
     * ブラウザの言語設定を取得
     */
    protected function getBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return null;
        }

        // Accept-Languageヘッダーをパース
        // 例: "ja,en-US;q=0.9,en;q=0.8"
        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $language) {
            // q値を削除
            $locale = trim(explode(';', $language)[0]);

            // 言語コードのみを抽出（ja-JP → ja）
            $locale = strtolower(substr($locale, 0, 2));

            if (LocaleHelper::isSupported($locale)) {
                return $locale;
            }
        }

        return null;
    }
}
