<?php

namespace Tests\Feature\Admin\Settings\Security;

use App\Helpers\ConfigHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ConfigHelper のセッション設定読み書きテスト
 *
 * DB値がconfig(.env)デフォルトより優先されることを検証する。
 */
class AdminSecuritySessionUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_and_get_session_lifetime_from_database(): void
    {
        // DB に値を書き込み
        ConfigHelper::setSessionLifetime(60);

        // DB から正しく読み取れること（config デフォルト 120 ではなく 60）
        $this->assertEquals(60, ConfigHelper::getSessionLifetime());
    }

    public function test_set_and_get_session_encrypt_from_database(): void
    {
        // DB に true を書き込み
        ConfigHelper::setSessionEncrypt(true);

        // DB から正しく読み取れること（config デフォルト false ではなく true）
        $this->assertTrue(ConfigHelper::getSessionEncrypt());
    }

    public function test_session_lifetime_falls_back_to_config_when_no_db_value(): void
    {
        // DB に値がない場合は config のデフォルト (120) を返す
        $lifetime = ConfigHelper::getSessionLifetime();
        $this->assertEquals(config('session.lifetime', 120), $lifetime);
    }

    public function test_session_encrypt_falls_back_to_config_when_no_db_value(): void
    {
        // DB に値がない場合は config のデフォルト (false) を返す
        $encrypt = ConfigHelper::getSessionEncrypt();
        $this->assertFalse($encrypt);
    }

    public function test_session_lifetime_update_overwrites_previous_value(): void
    {
        ConfigHelper::setSessionLifetime(30);
        $this->assertEquals(30, ConfigHelper::getSessionLifetime());

        ConfigHelper::setSessionLifetime(240);
        $this->assertEquals(240, ConfigHelper::getSessionLifetime());
    }

    public function test_session_encrypt_toggle(): void
    {
        ConfigHelper::setSessionEncrypt(false);
        $this->assertFalse(ConfigHelper::getSessionEncrypt());

        ConfigHelper::setSessionEncrypt(true);
        $this->assertTrue(ConfigHelper::getSessionEncrypt());

        ConfigHelper::setSessionEncrypt(false);
        $this->assertFalse(ConfigHelper::getSessionEncrypt());
    }
}
