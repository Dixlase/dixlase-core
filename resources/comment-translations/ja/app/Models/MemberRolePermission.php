<?php

return [
    'For the new approach, use the RolePermissionOverride model and PermissionRegistry service' => '新方式では RolePermissionOverride モデルと PermissionRegistry サービスを使用してください。',
    'See docs/role-permission-system.md for details' => '詳細は docs/role-permission-system.md を参照してください。',
    'Get access_roles as integer' => 'access_rolesを整数として取得',
    'The stored value means "users with this permission level or higher can access"' => '保存された値は「この権限値以上のユーザーがアクセス可能」を意味する',
    'If SUPER_ADMIN(10) is set, it is for privileged administrators only' => 'SUPER_ADMIN(10)が設定されている場合は特権管理者専用',
    'Return GUEST (lowest permission) if empty or null' => '空またはnullの場合はGUEST（最低権限）を返す',
    'Get view_roles as integer' => 'view_rolesを整数として取得',
    'The stored value means "users with this permission level or higher can view"' => '保存された値は「この権限値以上のユーザーが閲覧可能」を意味する',
    'Check if the specified user permission can access' => '指定されたユーザー権限がアクセス可能かチェック',
    'Check if the specified user permission can view' => '指定されたユーザー権限が閲覧可能かチェック',
    'Check if it is for privileged administrators only (edit permission)' => '特権管理者専用かどうかをチェック（編集権限）',
    'Check if it is for privileged administrators only (view permission)' => '特権管理者専用かどうかをチェック（閲覧権限）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'For the new approach, use the RolePermissionOverride model and PermissionRegistry service' => 'machine',
        'See docs/role-permission-system.md for details' => 'machine',
        'Get access_roles as integer' => 'machine',
        'The stored value means "users with this permission level or higher can access"' => 'machine',
        'If SUPER_ADMIN(10) is set, it is for privileged administrators only' => 'machine',
        'Return GUEST (lowest permission) if empty or null' => 'machine',
        'Get view_roles as integer' => 'machine',
        'The stored value means "users with this permission level or higher can view"' => 'machine',
        'Check if the specified user permission can access' => 'machine',
        'Check if the specified user permission can view' => 'machine',
        'Check if it is for privileged administrators only (edit permission)' => 'machine',
        'Check if it is for privileged administrators only (view permission)' => 'machine',
    ],
];
