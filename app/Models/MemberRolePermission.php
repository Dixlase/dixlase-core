<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberRolePermission extends Model
{
    protected $fillable = [
        'menu_key',
        'access_roles',
        'view_roles'
    ];

    // カンマ区切り文字列を配列に変換
    public function getAccessRolesAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getViewRolesAttribute($value)
    {
        return $value ? explode(',', $value) : [];
    }
}
