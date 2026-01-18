<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\Locale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ProfileBasicUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $member = Auth::guard('member')->user();
        
        $rules = [
            'account_name' => 'required|string|alpha_num|min:3|max:20',
            'display_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|string|email|max:255|unique:members,email,' . $member->id,
            'locale' => 'nullable|string|in:' . implode(',', Locale::values()),
        ];

        // メールアドレスが変更された場合は確認フィールドを必須に
        if ($this->input('email') !== $member->email) {
            $rules['email_confirmation'] = 'required|email|same:email';
        }

        return $rules;
    }
}
