<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\MemberRole;

/**
 * @deprecated このモデルは廃止されました。
 *             新方式では RolePermissionOverride モデルと PermissionRegistry サービスを使用してください。
 *             詳細は docs/role-permission-system.md を参照してください。
 */
class MemberRolePermission extends Model
{
    protected $table = 'members_role_permissions';
    protected $fillable = [
        'menu_key',
        'access_roles',
        'view_roles'
    ];

    /**
     * access_rolesを整数として取得
     * 保存された値は「この権限値以上のユーザーがアクセス可能」を意味する
     * SUPER_ADMIN(10)が設定されている場合は特権管理者専用
     * 
     * @param mixed $value
     * @return int
     */
    public function getAccessRolesAttribute($value): int
    {
        // 空またはnullの場合はGUEST（最低権限）を返す
        if ($value === null || $value === '') {
            return MemberRole::GUEST->value;
        }
        
        return (int) $value;
    }

    /**
     * view_rolesを整数として取得
     * 保存された値は「この権限値以上のユーザーが閲覧可能」を意味する
     * SUPER_ADMIN(10)が設定されている場合は特権管理者専用
     * 
     * @param mixed $value
     * @return int
     */
    public function getViewRolesAttribute($value): int
    {
        // 空またはnullの場合はGUEST（最低権限）を返す
        if ($value === null || $value === '') {
            return MemberRole::GUEST->value;
        }
        
        return (int) $value;
    }

    /**
     * 指定されたユーザー権限がアクセス可能かチェック
     * 
     * @param MemberRole $userRole
     * @return bool
     */
    public function canAccess(MemberRole $userRole): bool
    {
        return $userRole->value >= $this->access_roles;
    }

    /**
     * 指定されたユーザー権限が閲覧可能かチェック
     * 
     * @param MemberRole $userRole
     * @return bool
     */
    public function canView(MemberRole $userRole): bool
    {
        return $userRole->value >= $this->view_roles;
    }

    /**
     * 特権管理者専用かどうかをチェック（編集権限）
     * 
     * @return bool
     */
    public function isSuperAdminOnlyAccess(): bool
    {
        return $this->access_roles === MemberRole::SUPER_ADMIN->value;
    }

    /**
     * 特権管理者専用かどうかをチェック（閲覧権限）
     * 
     * @return bool
     */
    public function isSuperAdminOnlyView(): bool
    {
        return $this->view_roles === MemberRole::SUPER_ADMIN->value;
    }
}
