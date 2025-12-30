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
        \Log::debug('SetLocale: Start', [
            'path' => $request->path(),
            'current_locale' => app()->getLocale(),
            'member_authenticated' => auth('member')->check(),
        ]);

        // 優先言語を取得
        $locale = $this->getPreferredLocale($request);
        \Log::debug('SetLocale: Preferred locale', [
            'preferred_locale' => $locale,
        ]);

        // 言語を設定
        if ($locale && LocaleHelper::isSupported($locale)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);
            \Log::debug('SetLocale: Locale set', [
                'locale' => $locale,
                'new_app_locale' => app()->getLocale(),
            ]);
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
            \Log::debug('SetLocale: Member check', [
                'has_member' => !is_null($member),
                'member_id' => $member->id ?? null,
                'member_locale_raw' => $member->locale ?? null,
            ]);
            
            if ($member && $member->locale) {
                $memberLocale = $member->locale;
                // Enumの場合は値を取得
                if ($memberLocale instanceof \App\Enums\Locale) {
                    $memberLocale = $memberLocale->value;
                }
                \Log::debug('SetLocale: Member locale extracted', [
                    'locale' => $memberLocale,
                    'is_supported' => LocaleHelper::isSupported($memberLocale),
                ]);
                
                if (LocaleHelper::isSupported($memberLocale)) {
                    \Log::debug('SetLocale: Using member locale', ['locale' => $memberLocale]);
                    return $memberLocale;
                }
            }
        }

        // 2. セッションから取得
        $sessionLocale = LocaleHelper::getSessionLocale();
        \Log::debug('SetLocale: Session locale', ['locale' => $sessionLocale]);
        if ($sessionLocale && LocaleHelper::isSupported($sessionLocale)) {
            \Log::debug('SetLocale: Using session locale', ['locale' => $sessionLocale]);
            return $sessionLocale;
        }

        // 3. ログイン中のユーザーの設定から取得
        $userLocale = LocaleHelper::getUserPreferredLocale();
        \Log::debug('SetLocale: User locale', ['locale' => $userLocale]);
        if ($userLocale && LocaleHelper::isSupported($userLocale)) {
            \Log::debug('SetLocale: Using user locale', ['locale' => $userLocale]);
            return $userLocale;
        }

        // 4. ブラウザの言語設定から取得
        $browserLocale = $this->getBrowserLocale($request);
        \Log::debug('SetLocale: Browser locale', ['locale' => $browserLocale]);
        if ($browserLocale && LocaleHelper::isSupported($browserLocale)) {
            \Log::debug('SetLocale: Using browser locale', ['locale' => $browserLocale]);
            return $browserLocale;
        }

        // 5. デフォルト言語
        $defaultLocale = LocaleHelper::getDefaultLocale();
        \Log::debug('SetLocale: Using default locale', ['locale' => $defaultLocale]);
        return $defaultLocale;
    }

    /**
     * ブラウザの言語設定を取得
     */
    protected function getBrowserLocale(Request $request): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');
        
        if (!$acceptLanguage) {
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
