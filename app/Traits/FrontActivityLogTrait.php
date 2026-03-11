<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * フロントページのアクティビティログ記録トレイト
 */
trait FrontActivityLogTrait
{
    /**
     * フロントページの操作をログに記録
     *
     * @param  string  $action  操作内容
     * @param  array  $details  詳細情報
     * @param  string|null  $userId  ユーザーID（ログイン済みの場合）
     * @param  Request|null  $request  リクエスト情報
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
     * フロントページのエラーをログに記録
     *
     * @param  string  $error  エラー内容
     * @param  array  $context  エラーコンテキスト
     * @param  string|null  $userId  ユーザーID（ログイン済みの場合）
     * @param  Request|null  $request  リクエスト情報
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
     * ページビューをログに記録
     *
     * @param  string  $page  ページ名
     * @param  string|null  $userId  ユーザーID（ログイン済みの場合）
     * @param  Request|null  $request  リクエスト情報
     */
    protected function logPageView(string $page, ?string $userId = null, ?Request $request = null): void
    {
        $this->logFrontActivity('ページビュー', [
            'page' => $page,
        ], $userId, $request);
    }

    /**
     * フォーム送信をログに記録
     *
     * @param  string  $formType  フォームタイプ
     * @param  array  $formData  フォームデータ（機密情報は除く）
     * @param  string|null  $userId  ユーザーID（ログイン済みの場合）
     * @param  Request|null  $request  リクエスト情報
     */
    protected function logFormSubmission(string $formType, array $formData = [], ?string $userId = null, ?Request $request = null): void
    {
        // パスワードなどの機密情報を除去
        $sanitizedData = $this->sanitizeFormData($formData);

        $this->logFrontActivity('フォーム送信', [
            'form_type' => $formType,
            'form_data' => $sanitizedData,
        ], $userId, $request);
    }

    /**
     * ダウンロードをログに記録
     *
     * @param  string  $fileName  ファイル名
     * @param  string|null  $userId  ユーザーID（ログイン済みの場合）
     * @param  Request|null  $request  リクエスト情報
     */
    protected function logDownload(string $fileName, ?string $userId = null, ?Request $request = null): void
    {
        $this->logFrontActivity('ダウンロード', [
            'file_name' => $fileName,
        ], $userId, $request);
    }

    /**
     * 検索をログに記録
     *
     * @param  string  $query  検索クエリ
     * @param  int  $resultCount  検索結果数
     * @param  string|null  $userId  ユーザーID（ログイン済みの場合）
     * @param  Request|null  $request  リクエスト情報
     */
    protected function logSearch(string $query, int $resultCount = 0, ?string $userId = null, ?Request $request = null): void
    {
        $this->logFrontActivity('検索', [
            'query' => $query,
            'result_count' => $resultCount,
        ], $userId, $request);
    }

    /**
     * フォームデータから機密情報を除去
     *
     * @param  array  $data  フォームデータ
     * @return array サニタイズされたデータ
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
