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

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AuthenticationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Traits\TwoFa\TwoFactorEnableCheck;

class ProfileTwoFaUpdateRequest extends FormRequest
{
    use TwoFactorEnableCheck;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'two_fa_mode' => ['nullable', new Enum(AuthenticationMode::class)],
            'two_fa_passkey_enabled' => 'nullable|boolean',
            'two_fa_default_method' => 'nullable|integer|in:0,1',
        ];
    }

    /**
     * カスタムバリデーションルールを追加
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $twoFaMode = (int) $this->input('two_fa_mode', 0);
            
            // 2FAを有効化しようとしている場合（モード1または2）
            // プロフィール画面ではメールサーバーテスト済みかチェック
            if ($twoFaMode === 1 || $twoFaMode === 2) {
                if (!\App\Services\MailServerValidatorService::isMailServerTested()) {
                    $validator->errors()->add(
                        'two_fa_mode',
                        __('admin/profile/validation.two_fa_cannot_enable')
                    );
                }
            }
        });
    }
}
