<?php

namespace Tests\Feature\Admin\Settings\Security;

use App\Helpers\ConfigHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\EncryptedStore;
use Tests\TestCase;

/**
 * ConfigHelper のセッション設定読み書き＋ランタイム反映テスト
 *
 * - レベル0: DB値がconfig(.env)デフォルトより優先されることを検証
 * - レベル1: applySessionConfig()が config() を正しく上書きするか検証
 * - レベル2: セッション暗号化が EncryptedStore に反映されるか検証
 */
class AdminSecuritySessionUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
    }

    protected function tearDown(): void
    {
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        unset($_SERVER['INSTALLED']);
        parent::tearDown();
    }

    // ========================================
    // レベル0: ConfigHelper の読み書き
    // ========================================

    public function test_set_and_get_session_lifetime_from_database(): void
    {
        ConfigHelper::setSessionLifetime(60);

        $this->assertEquals(60, ConfigHelper::getSessionLifetime());
    }

    public function test_set_and_get_session_encrypt_from_database(): void
    {
        ConfigHelper::setSessionEncrypt(true);

        $this->assertTrue(ConfigHelper::getSessionEncrypt());
    }

    public function test_session_lifetime_falls_back_to_config_when_no_db_value(): void
    {
        $lifetime = ConfigHelper::getSessionLifetime();
        $this->assertEquals(config('session.lifetime', 120), $lifetime);
    }

    public function test_session_encrypt_falls_back_to_config_when_no_db_value(): void
    {
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

    // ========================================
    // レベル1: applySessionConfig による config() 反映
    // ========================================

    public function test_apply_session_config_reflects_lifetime(): void
    {
        ConfigHelper::setSessionLifetime(45);

        ConfigHelper::applySessionConfig();

        $this->assertEquals(45, config('session.lifetime'));
    }

    public function test_apply_session_config_member_guard_uses_session_lifetime(): void
    {
        // member ガードも session_lifetime を共有する
        ConfigHelper::setSessionLifetime(90);

        ConfigHelper::applySessionConfig('member');

        $this->assertEquals(90, config('session.lifetime'));
    }

    public function test_effective_lifetime_same_for_all_guards(): void
    {
        ConfigHelper::setSessionLifetime(30);

        $this->assertEquals(30, ConfigHelper::getEffectiveSessionLifetime('member'));
        $this->assertEquals(30, ConfigHelper::getEffectiveSessionLifetime('web'));
        $this->assertEquals(30, ConfigHelper::getEffectiveSessionLifetime(null));
    }

    public function test_apply_session_config_reflects_encrypt_to_config(): void
    {
        ConfigHelper::setSessionEncrypt(true);

        ConfigHelper::applySessionConfig();

        $this->assertTrue(config('session.encrypt'));
    }

    public function test_apply_session_config_reflects_all_settings(): void
    {
        ConfigHelper::setSessionLifetime(300);
        ConfigHelper::setSessionEncrypt(true);

        ConfigHelper::applySessionConfig();

        $this->assertEquals(300, config('session.lifetime'));
        $this->assertTrue(config('session.encrypt'));
        $this->assertEquals('guard-aware-database', config('session.driver'));
    }

    public function test_middleware_applies_session_config_on_request(): void
    {
        ConfigHelper::setSessionLifetime(77);
        ConfigHelper::setSessionEncrypt(true);

        $this->get('/');

        $this->assertEquals(77, config('session.lifetime'));
        $this->assertTrue(config('session.encrypt'));
    }

    // ========================================
    // レベル2: セッション暗号化の実効性
    // ========================================

    public function test_encrypted_store_is_used_when_encrypt_enabled(): void
    {
        config(['session.encrypt' => true]);

        $manager = app('session');
        $manager->forgetDrivers();
        $store = $manager->driver();

        $this->assertInstanceOf(EncryptedStore::class, $store);
    }

    public function test_plain_store_is_used_when_encrypt_disabled(): void
    {
        config(['session.encrypt' => false]);

        $manager = app('session');
        $manager->forgetDrivers();
        $store = $manager->driver();

        $this->assertNotInstanceOf(EncryptedStore::class, $store);
    }
}
