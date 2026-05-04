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

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Front page activity log recording trait
 */
trait FrontActivityLogTrait
{
    /**
     * Log front page operations
     *
     * @param  string  $action  Operation details
     * @param  array  $details  Detailed information
     * @param  string|null  $userId  User ID (if logged in)
     * @param  Request|null  $request  Request information
     */
    protected function logFrontActivity(string $action, array $details = [], ?string $userId = null, ?Request $request = null): void
    {
        $request = $request ?? request();

        $logData = [
            'action' => $action,
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toDateTimeString(),
        ];

        if (! empty($details)) {
            $logData['details'] = $details;
        }

        Log::channel('front_activity')->info('フロント操作', $logData);
    }

    /**
     * Log front page errors
     *
     * @param  string  $error  Error details
     * @param  array  $context  Error context
     * @param  string|null  $userId  User ID (if logged in)
     * @param  Request|null  $request  Request information
     */
    protected function logFrontError(string $error, array $context = [], ?string $userId = null, ?Request $request = null): void
    {
        $request = $request ?? request();

        $logData = [
            'error' => $error,
            'user_id' => $userId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'timestamp' => now()->toDateTimeString(),
        ];

        if (! empty($context)) {
            $logData['context'] = $context;
        }

        Log::channel('front_error')->error('フロントエラー', $logData);
    }

    /**
     * Log page views
     *
     * @param  string  $page  Page name
     * @param  string|null  $userId  User ID (if logged in)
     * @param  Request|null  $request  Request information
     */
    protected function logPageView(string $page, ?string $userId = null, ?Request $request = null): void
    {
        $this->logFrontActivity('ページビュー', [
            'page' => $page,
        ], $userId, $request);
    }

    /**
     * Log form submissions
     *
     * @param  string  $formType  Form type
     * @param  array  $formData  Form data (excluding sensitive information)
     * @param  string|null  $userId  User ID (if logged in)
     * @param  Request|null  $request  Request information
     */
    protected function logFormSubmission(string $formType, array $formData = [], ?string $userId = null, ?Request $request = null): void
    {
        // Remove sensitive information such as passwords
        $sanitizedData = $this->sanitizeFormData($formData);

        $this->logFrontActivity('フォーム送信', [
            'form_type' => $formType,
            'form_data' => $sanitizedData,
        ], $userId, $request);
    }

    /**
     * Log downloads
     *
     * @param  string  $fileName  File name
     * @param  string|null  $userId  User ID (if logged in)
     * @param  Request|null  $request  Request information
     */
    protected function logDownload(string $fileName, ?string $userId = null, ?Request $request = null): void
    {
        $this->logFrontActivity('ダウンロード', [
            'file_name' => $fileName,
        ], $userId, $request);
    }

    /**
     * Log searches
     *
     * @param  string  $query  Search query
     * @param  int  $resultCount  Number of search results
     * @param  string|null  $userId  User ID (if logged in)
     * @param  Request|null  $request  Request information
     */
    protected function logSearch(string $query, int $resultCount = 0, ?string $userId = null, ?Request $request = null): void
    {
        $this->logFrontActivity('検索', [
            'query' => $query,
            'result_count' => $resultCount,
        ], $userId, $request);
    }

    /**
     * Remove sensitive information from form data
     *
     * @param  array  $data  Form data
     * @return array Sanitized data
     */
    private function sanitizeFormData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'csrf_token',
            '_token',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
        ];

        $sanitized = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[FILTERED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeFormData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
