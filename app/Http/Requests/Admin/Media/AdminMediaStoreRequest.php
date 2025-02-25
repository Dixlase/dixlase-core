<?php

namespace App\Http\Requests\Admin\Media;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\MediaSetting;
use Illuminate\Support\Facades\Log;

class AdminMediaStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedFileTypes = json_decode(MediaSetting::where('name', 'allowed_file_types')->value('value'), true);

        Log::info('許可されたファイルタイプ: ' . json_encode($allowedFileTypes));

        return [
            'file' => 'required|file|mimes:' . implode(',', $allowedFileTypes),
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'ファイルは必須です。',
            'file.file' => '有効なファイルをアップロードしてください。',
            'file.mimes' => '許可されているファイルタイプは ' . implode(', ', $this->allowedTypes()) . ' です。',
        ];
    }

    protected function allowedTypes()
    {
        return json_decode(MediaSetting::where('name', 'allowed_file_types')->value('value'), true);
    }

    public function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        Log::error('バリデーションエラー: ' . json_encode($validator->errors()->all()));

        parent::failedValidation($validator);
    }
}
