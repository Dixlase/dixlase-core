<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Contracts\Repositories\MemberSettingRepositoryInterface;
use App\Models\Traits\UsesSettingRepositoryTrait;

/**
 * メンバー設定モデル
 * 
 * @deprecated 静的メソッドは非推奨です。MemberSettingRepositoryを使用してください。
 */
class MemberSetting extends Model
{
    use UsesSettingRepositoryTrait;

    protected $table = 'members_settings';
    protected $fillable = ['key', 'value'];

    /**
     * {@inheritDoc}
     */
    protected static function getRepositoryInterface(): string
    {
        return MemberSettingRepositoryInterface::class;
    }
}
