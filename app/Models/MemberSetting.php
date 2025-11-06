<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MemberSetting extends Model
{
    protected $table = 'members_settings';
    protected $fillable = ['key', 'value'];

    public static function getAllSettings(): array
    {
        return Cache::remember('members_settings_all', now()->addMinutes(10), function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("member_setting_{$key}", now()->addMinutes(10), function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function setValue(string $key, $value)
    {
        // DB更新
        $record = static::updateOrCreate(['key' => $key], ['value' => $value]);

        // キャッシュ削除（個別キャッシュと全体キャッシュ）
        Cache::forget("member_setting_{$key}");
        Cache::forget('members_settings_all');

        return $record;
    }
}
