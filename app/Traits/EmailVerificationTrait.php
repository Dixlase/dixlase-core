<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * メール認証の共通ロジックを提供するTrait
 *
 * このTraitは、メンバーとユーザーのメール認証通知で共通する
 * 最小限のロジックのみを提供します。
 */
trait EmailVerificationTrait
{
    /**
     * @var string コンテキスト（'create', 'email_change', または 'resend'）
     */
    protected $context;

    /**
     * コンテキストに応じた件名キーを取得
     *
     * @param  string  $prefix  翻訳キーのプレフィックス（例: 'mail.member_verify_email', 'dixlase-users::mail.verify_email'）
     * @return string 件名の翻訳キー
     */
    protected function getSubjectKey(string $prefix): string
    {
        return $this->context === 'email_change'
            ? "{$prefix}.subject"
            : "{$prefix}.subject_account";
    }

    /**
     * コンテキストに応じたメッセージキーを取得
     *
     * @param  string  $prefix  翻訳キーのプレフィックス
     * @return string メッセージの翻訳キー
     */
    protected function getMessageKey(string $prefix): string
    {
        return match ($this->context) {
            'email_change' => "{$prefix}.message_email_change",
            'resend' => "{$prefix}.message_resend",
            default => "{$prefix}.message_create",
        };
    }

    /**
     * コンテキストに応じたアクションキーを取得
     *
     * @param  string  $prefix  翻訳キーのプレフィックス
     * @return string アクションボタンの翻訳キー
     */
    protected function getActionKey(string $prefix): string
    {
        return $this->context === 'email_change'
            ? "{$prefix}.action_change_email"
            : "{$prefix}.action_verify_account";
    }

    /**
     * メール認証用の署名付き一時URLを生成
     *
     * @param  object  $notifiable  通知対象のモデル
     * @param  string  $routeName  ルート名
     * @param  string  $logContext  ログ用のコンテキスト名（例: 'member', 'user'）
     * @return string 署名付きURL
     */
    protected function generateVerificationUrl(object $notifiable, string $routeName, string $logContext = 'entity'): string
    {
        $url = URL::temporarySignedRoute(
            $routeName,
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        \Log::info("Email verification URL generated for {$logContext}", [
            "{$logContext}_id" => $notifiable->getKey(),
            'email' => $notifiable->getEmailForVerification(),
            'url' => $url,
            'context' => $this->context,
        ]);

        return $url;
    }

    /**
     * 認証メールの有効期限（分）を取得
     *
     * @return int 有効期限（分）
     */
    protected function getExpirationMinutes(): int
    {
        return Config::get('auth.verification.expire', 60);
    }
}
