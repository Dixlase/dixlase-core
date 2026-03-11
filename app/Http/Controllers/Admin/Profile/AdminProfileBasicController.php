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

namespace App\Http\Controllers\Admin\Profile;

use App\Enums\Locale;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfileBasicUpdateRequest;
use App\Services\MailServerValidatorService;
use Illuminate\Support\Facades\Auth;

class AdminProfileBasicController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the basic information edit page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();

        // 言語オプションの取得
        $this->viewParams['localeOptions'] = Locale::availableOptions();

        // pending_email がある場合の情報を渡す
        $this->viewParams['hasPendingEmail'] = ! empty($member->pending_email);
        $this->viewParams['pendingEmail'] = $member->pending_email;

        // メールサーバー設定状態を渡す
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        return view('admin.profile.basic', $this->viewParams);
    }

    /**
     * Update basic information.
     */
    public function update(ProfileBasicUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();

        // メールアドレスの変更を検知
        $emailChanged = $member->email !== $validated['email'];

        // メールサーバー設定状態を確認
        $isMailServerTested = MailServerValidatorService::isMailServerTested();

        // プロフィール更新
        $updateData = [
            'account_name' => $validated['account_name'],
            'display_name' => $validated['display_name'] ?? null,
            'description' => $validated['description'] ?? '',
            'locale' => $validated['locale'] ?? null,
        ];

        // メールアドレス変更の処理
        if ($emailChanged) {
            if ($isMailServerTested) {
                $updateData['pending_email'] = $validated['email'];
            } else {
                $updateData['email'] = $validated['email'];
                $updateData['pending_email'] = null;
                $updateData['email_verified_at'] = now();
            }
        } else {
            $updateData['pending_email'] = null;
        }

        $member->update($updateData);

        // メールアドレス変更時に認証メールを送信
        if ($emailChanged && $isMailServerTested) {
            try {
                $member->sendEmailVerificationNotification('email_change');
            } catch (\Exception $e) {
                \Log::error('Failed to send email verification', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 言語設定が変更された場合、即座に適用
        if (isset($validated['locale']) && $validated['locale']) {
            \Illuminate\Support\Facades\App::setLocale($validated['locale']);
        }

        // メッセージ
        if ($emailChanged && $isMailServerTested) {
            $message = __('admin/profile/common.updated_with_email_verification');
        } elseif ($emailChanged && ! $isMailServerTested) {
            $message = __('admin/profile/common.updated_email_immediate');
        } else {
            $message = __('admin/profile/common.updated');
        }

        return redirect()->route('admin.profile.basic')->with('success', $message);
    }
}
