<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;

/**
 * メンバー設定モデル
 * 
 * @deprecated 静的メソッドは非推奨です。MemberSettingRepositoryを使用してください。
 */
class MemberSetting extends Model
{
    protected $table = 'members_settings';
    protected $fillable = ['key', 'value'];

    /**
     * すべての設定を取得
     * 
     * @deprecated MemberSettingRepository::all() を使用してください
     * @return array
     */
    public static function getAllSettings(): array
    {
        return app(MemberSettingRepositoryInterface::class)->all();
    }

    /**
     * 設定値を取得
     * 
     * @deprecated MemberSettingRepository::get() を使用してください
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getValue(string $key, $default = null)
    {
        return app(MemberSettingRepositoryInterface::class)->get($key, $default);
    }

    /**
     * 設定値を保存
     * 
     * @deprecated MemberSettingRepository::set() を使用してください
     * @param string $key
     * @param mixed $value
     * @return MemberSetting
     */
    public static function setValue(string $key, $value)
    {
        return app(MemberSettingRepositoryInterface::class)->set($key, $value);
    }
}
