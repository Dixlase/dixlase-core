<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Artisan;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class EnvHelper
{
    // snake_case => ENV_KEY
    protected static array $envMap = [
        'app_name' => 'APP_NAME',
        'app_env' => 'APP_ENV',
        'app_debug' => 'APP_DEBUG',
        'locale' => 'APP_LOCALE',
        'fallback_locale' => 'APP_FALLBACK_LOCALE',
        'faker_locale' => 'APP_FAKER_LOCALE',
        'timezone' => 'APP_TIMEZONE',
        'mail_from_address' => 'MAIL_FROM_ADDRESS',
        'mail_mailer' => 'MAIL_MAILER',
        'mail_host' => 'MAIL_HOST',
        'mail_port' => 'MAIL_PORT',
        'mail_username' => 'MAIL_USERNAME',
        'mail_password' => 'MAIL_PASSWORD',
        'mail_encryption' => 'MAIL_ENCRYPTION',
        'maintenance_mode' => 'MAINTENANCE_MODE',
        // Session settings
        'session_driver' => 'SESSION_DRIVER',
        'session_lifetime' => 'SESSION_LIFETIME',
        'session_encrypt' => 'SESSION_ENCRYPT',
    ];

    public static function toEnvKey(string $snakeCaseKey): string
    {
        return static::$envMap[$snakeCaseKey] ?? strtoupper($snakeCaseKey);
    }

    public static function isEnvKey(string $key): bool
    {
        return array_key_exists($key, static::$envMap);
    }

    public static function getEnvMap(): array
    {
        return static::$envMap;
    }

    public static function update(array $data): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $envKey = static::toEnvKey($key);
            $escapedKey = preg_quote($envKey, '/');
            $value = str_replace(["\r", "\n"], '', $value);

            // 値を適切にエスケープ・クォート
            $formattedValue = static::formatEnvValue($value);

            // 既存のキーを置換、なければ追記
            $pattern = "/^{$escapedKey}=.*/m";
            $replacement = "{$envKey}={$formattedValue}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        file_put_contents($envPath, $envContent);

        sleep(1);

        Artisan::call('config:clear');
    }

    /**
     * .env用に値を適切にフォーマット
     *
     * @param  mixed  $value
     */
    protected static function formatEnvValue($value): string
    {
        // null値の処理
        if ($value === null || $value === '') {
            return '';
        }

        // 文字列に変換
        $value = (string) $value;

        // true/falseの処理
        if (in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value);
        }

        // 数値のみの場合はクォート不要
        if (is_numeric($value)) {
            return $value;
        }

        // スペース、特殊文字、#を含む場合はダブルクォートで囲む
        if (preg_match('/[\s#\$\(\)\[\]\{\}\|\&\;\<\>\?\*\'\"]/', $value)) {
            // 既存のダブルクォートとバックスラッシュをエスケープ
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return "\"{$escaped}\"";
        }

        // それ以外はそのまま
        return $value;
    }
}
