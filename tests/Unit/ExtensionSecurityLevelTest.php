<?php

namespace Tests\Unit;

use App\Enums\ExtensionSecurityLevel;
use Tests\TestCase;

class ExtensionSecurityLevelTest extends TestCase
{
    /**
     * 全てのケースが正しい値を持つことのテスト
     */
    public function test_all_cases_have_correct_values(): void
    {
        $this->assertEquals(0, ExtensionSecurityLevel::Healthy->value);
        $this->assertEquals(1, ExtensionSecurityLevel::Warning->value);
        $this->assertEquals(2, ExtensionSecurityLevel::NeedsAttention->value);
        $this->assertEquals(3, ExtensionSecurityLevel::NotVerified->value);
    }

    /**
     * 翻訳キーが正しい形式であることのテスト
     */
    public function test_translation_keys_have_correct_format(): void
    {
        foreach (ExtensionSecurityLevel::cases() as $case) {
            $key = $case->translationKey();
            $this->assertStringStartsWith('admin.settings.security.extension_security.health_level.', $key);
        }
    }

    /**
     * CSSクラスが返されることのテスト
     */
    public function test_css_class_returns_string(): void
    {
        foreach (ExtensionSecurityLevel::cases() as $case) {
            $cssClass = $case->cssClass();
            $this->assertIsString($cssClass);
            $this->assertNotEmpty($cssClass);
        }
    }

    /**
     * バッジクラスが返されることのテスト
     */
    public function test_badge_class_returns_string(): void
    {
        foreach (ExtensionSecurityLevel::cases() as $case) {
            $badgeClass = $case->badgeClass();
            $this->assertIsString($badgeClass);
            $this->assertNotEmpty($badgeClass);
        }
    }

    /**
     * allows()メソッドのテスト - 同じレベル
     */
    public function test_allows_same_level(): void
    {
        $this->assertTrue(ExtensionSecurityLevel::Healthy->allows(ExtensionSecurityLevel::Healthy));
        $this->assertTrue(ExtensionSecurityLevel::Warning->allows(ExtensionSecurityLevel::Warning));
        $this->assertTrue(ExtensionSecurityLevel::NeedsAttention->allows(ExtensionSecurityLevel::NeedsAttention));
        $this->assertTrue(ExtensionSecurityLevel::NotVerified->allows(ExtensionSecurityLevel::NotVerified));
    }

    /**
     * allows()メソッドのテスト - より低いレベルを許可
     */
    public function test_allows_lower_levels(): void
    {
        // Warningは Healthy を許可
        $this->assertTrue(ExtensionSecurityLevel::Warning->allows(ExtensionSecurityLevel::Healthy));

        // NeedsAttentionは Healthy と Warning を許可
        $this->assertTrue(ExtensionSecurityLevel::NeedsAttention->allows(ExtensionSecurityLevel::Healthy));
        $this->assertTrue(ExtensionSecurityLevel::NeedsAttention->allows(ExtensionSecurityLevel::Warning));

        // NotVerifiedは全てを許可
        $this->assertTrue(ExtensionSecurityLevel::NotVerified->allows(ExtensionSecurityLevel::Healthy));
        $this->assertTrue(ExtensionSecurityLevel::NotVerified->allows(ExtensionSecurityLevel::Warning));
        $this->assertTrue(ExtensionSecurityLevel::NotVerified->allows(ExtensionSecurityLevel::NeedsAttention));
    }

    /**
     * allows()メソッドのテスト - より高いレベルを拒否
     */
    public function test_denies_higher_levels(): void
    {
        // Healthyは他のレベルを拒否
        $this->assertFalse(ExtensionSecurityLevel::Healthy->allows(ExtensionSecurityLevel::Warning));
        $this->assertFalse(ExtensionSecurityLevel::Healthy->allows(ExtensionSecurityLevel::NeedsAttention));
        $this->assertFalse(ExtensionSecurityLevel::Healthy->allows(ExtensionSecurityLevel::NotVerified));

        // Warningは NeedsAttention と NotVerified を拒否
        $this->assertFalse(ExtensionSecurityLevel::Warning->allows(ExtensionSecurityLevel::NeedsAttention));
        $this->assertFalse(ExtensionSecurityLevel::Warning->allows(ExtensionSecurityLevel::NotVerified));

        // NeedsAttentionは NotVerified を拒否
        $this->assertFalse(ExtensionSecurityLevel::NeedsAttention->allows(ExtensionSecurityLevel::NotVerified));
    }

    /**
     * all()メソッドが全ケースを返すことのテスト
     */
    public function test_all_returns_all_cases(): void
    {
        $all = ExtensionSecurityLevel::all();

        $this->assertCount(4, $all);
        $this->assertContains(ExtensionSecurityLevel::Healthy, $all);
        $this->assertContains(ExtensionSecurityLevel::Warning, $all);
        $this->assertContains(ExtensionSecurityLevel::NeedsAttention, $all);
        $this->assertContains(ExtensionSecurityLevel::NotVerified, $all);
    }

    /**
     * デフォルト値のテスト
     */
    public function test_default_returns_warning(): void
    {
        $default = ExtensionSecurityLevel::default();

        $this->assertEquals(ExtensionSecurityLevel::Warning, $default);
    }

    /**
     * getRangeLabels()が正しい形式を返すことのテスト
     */
    public function test_get_range_labels_returns_correct_format(): void
    {
        $labels = ExtensionSecurityLevel::getRangeLabels();

        $this->assertIsArray($labels);
        $this->assertCount(4, $labels);
        $this->assertArrayHasKey(0, $labels);
        $this->assertArrayHasKey(1, $labels);
        $this->assertArrayHasKey(2, $labels);
        $this->assertArrayHasKey(3, $labels);
    }

    /**
     * getRangeLabelColors()が正しい色を返すことのテスト
     */
    public function test_get_range_label_colors_returns_correct_colors(): void
    {
        $colors = ExtensionSecurityLevel::getRangeLabelColors();

        $this->assertIsArray($colors);
        $this->assertEquals('green', $colors[ExtensionSecurityLevel::Healthy->value]);
        $this->assertEquals('yellow', $colors[ExtensionSecurityLevel::Warning->value]);
        $this->assertEquals('orange', $colors[ExtensionSecurityLevel::NeedsAttention->value]);
        $this->assertEquals('red', $colors[ExtensionSecurityLevel::NotVerified->value]);
    }

    /**
     * tryFrom()で有効な値から変換できることのテスト
     */
    public function test_try_from_valid_values(): void
    {
        $this->assertEquals(ExtensionSecurityLevel::Healthy, ExtensionSecurityLevel::tryFrom(0));
        $this->assertEquals(ExtensionSecurityLevel::Warning, ExtensionSecurityLevel::tryFrom(1));
        $this->assertEquals(ExtensionSecurityLevel::NeedsAttention, ExtensionSecurityLevel::tryFrom(2));
        $this->assertEquals(ExtensionSecurityLevel::NotVerified, ExtensionSecurityLevel::tryFrom(3));
    }

    /**
     * tryFrom()で無効な値はnullを返すことのテスト
     */
    public function test_try_from_invalid_values(): void
    {
        $this->assertNull(ExtensionSecurityLevel::tryFrom(-1));
        $this->assertNull(ExtensionSecurityLevel::tryFrom(4));
        $this->assertNull(ExtensionSecurityLevel::tryFrom(100));
    }

    /**
     * 健全性レベルの順序が正しいことのテスト（低い値 = より健全）
     */
    public function test_health_level_ordering(): void
    {
        $this->assertLessThan(
            ExtensionSecurityLevel::Warning->value,
            ExtensionSecurityLevel::Healthy->value
        );
        $this->assertLessThan(
            ExtensionSecurityLevel::NeedsAttention->value,
            ExtensionSecurityLevel::Warning->value
        );
        $this->assertLessThan(
            ExtensionSecurityLevel::NotVerified->value,
            ExtensionSecurityLevel::NeedsAttention->value
        );
    }
}
