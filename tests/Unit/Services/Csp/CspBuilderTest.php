<?php

namespace Tests\Unit\Services\Csp;

use App\Services\Csp\CspBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CspBuilderTest extends TestCase
{
    use RefreshDatabase;

    private CspBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = app(CspBuilder::class);
    }

    public function test_is_enabled_returns_boolean(): void
    {
        $result = $this->builder->isEnabled();

        $this->assertIsBool($result);
    }

    public function test_build_returns_string(): void
    {
        $csp = $this->builder->build();

        $this->assertIsString($csp);
    }

    public function test_build_contains_default_src(): void
    {
        $csp = $this->builder->build();

        // CSP が有効な場合は default-src が含まれる
        if (! empty($csp)) {
            $this->assertStringContainsString('default-src', $csp);
        } else {
            // CSP 無効時は空文字列
            $this->assertEquals('', $csp);
        }
    }

    public function test_set_context_returns_self(): void
    {
        $result = $this->builder->setContext('admin');

        $this->assertInstanceOf(CspBuilder::class, $result);
    }

    public function test_get_header_name_returns_string(): void
    {
        $name = $this->builder->getHeaderName();

        $this->assertIsString($name);
        $this->assertStringContainsString('Content-Security-Policy', $name);
    }

    public function test_build_with_admin_context(): void
    {
        $this->builder->setContext('admin');
        $csp = $this->builder->build();

        $this->assertIsString($csp);
    }

    public function test_build_with_front_context(): void
    {
        $this->builder->setContext('front');
        $csp = $this->builder->build();

        $this->assertIsString($csp);
    }
}
