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
     * SecuritySettingに移動済みの設定キー
     * これらのキーへのアクセスはエラーを出す
     */
    protected static array $movedToSecuritySettings = [
        // パスワード設定
        'password_min_length',
        'password_min_length_default',
        'password_require_uppercase',
        'password_require_lowercase',
        'password_require_number',
        'password_require_symbol',
        
        // ログイン通知設定
        'login_notification_mode',
        'login_notification_send_to_system',
        'login_notification_system_email',
        
        // ログイン試行制限設定
        'login_attempt_limit_enabled',
        'login_attempt_max_attempts',
        'login_attempt_max_attempts_ip',
        'login_attempt_time_window',
        'login_attempt_lockout_duration',
        'login_attempt_lockout_notification_enabled',
        'lockout_notification_enabled',
        
        // セッション管理設定
        'session_driver',
        'session_encrypt',
        'session_lifetime',
        'session_member_lifetime',
        
        // 二段階認証詳細設定
        'two_fa_expire_minutes',
        'two_fa_resend_interval_seconds',
        'two_fa_max_attempts',
        'two_fa_attempt_window',
        'two_fa_lockout_duration',
        'two_fa_lockout_notification_enabled',
        'two_fa_recovery_codes_count',
        'two_fa_recovery_code_regenerate_interval',
    ];

    /**
     * {@inheritDoc}
     */
    protected static function getRepositoryInterface(): string
    {
        return MemberSettingRepositoryInterface::class;
    }

    /**
     * 設定値を取得（移動済み設定のチェック付き）
     * 
     * @param string $name 設定名
     * @param mixed $default デフォルト値
     * @return mixed
     * @throws \RuntimeException SecuritySettingに移動済みの設定にアクセスした場合
     */
    public static function getValue(string $name, mixed $default = null): mixed
    {
        if (in_array($name, static::$movedToSecuritySettings)) {
            throw new \RuntimeException(
                "設定キー '{$name}' は MemberSetting から SecuritySetting に移動されました。\n" .
                "SecuritySetting::getValue('{$name}') を使用してください。\n" .
                "ファイル: " . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['file'] ?? 'unknown' . "\n" .
                "行: " . debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['line'] ?? 'unknown'
            );
        }
        
        return app(static::getRepositoryInterface())->get($name, $default);
    }

    /**
     * SecuritySetting互換: get()メソッド（移動済み設定のチェック付き）
     * 
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed
     * @throws \RuntimeException SecuritySettingに移動済みの設定にアクセスした場合
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::getValue($key, $default);
    }
}
