<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Models\BaseSetting;
use App\Helpers\EnvHelper;
use Exception;

class BaseSettings extends Facade
{



    public static function get(string $key, $default = null)
    {
        $envKey = EnvHelper::toEnvKey($key);
        if (in_array($envKey, EnvHelper::getEnvMap())) {
            return env($envKey, $default);
        }

        return BaseSetting::getValue($key, $default);
    }

    public static function set(string $key, $value): void
    {
        // .env 設定の場合は例外を投げる
        if (EnvHelper::isEnvKey($key)) {
            throw new Exception("{$key} は .env 設定のため、BaseSettings では変更できません");
        }

        BaseSetting::setValue($key, $value);
    }
}
