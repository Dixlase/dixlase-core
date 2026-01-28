<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AuthenticationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Traits\TwoFa\TwoFactorEnableCheck;

class ProfileTwoFactorUpdateRequest extends FormRequest
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
            $member = $this->user();
            $twoFaMode = (int) $this->input('two_fa_mode', 0);
            
            // 2FAを有効化しようとしている場合（モード1または2）
            if ($twoFaMode === 1 || $twoFaMode === 2) {
                if ($member && !$member->canEnableTwoFa()) {
                    $validator->errors()->add(
                        'two_fa_mode',
                        __('admin/profile/validation.two_fa_cannot_enable')
                    );
                }
            }
        });
    }
}
