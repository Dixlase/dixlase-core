<?php

namespace Tests\Unit\Services;

use App\Enums\SafeMode;
use App\Services\SafeModeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SafeModeServiceTest extends TestCase
{
    use RefreshDatabase;

    private SafeModeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SafeModeService::class);
    }

    public function test_activate_sets_session_flag(): void
    {
        $this->service->activate(SafeMode::Csp);

        $this->assertTrue($this->service->isActive(SafeMode::Csp));
    }

    public function test_deactivate_clears_session_flag(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->deactivate(SafeMode::Csp);

        $this->assertFalse($this->service->isActive(SafeMode::Csp));
    }

    public function test_multiple_modes_can_be_active(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->activate(SafeMode::Plugins);

        $this->assertTrue($this->service->isActive(SafeMode::Csp));
        $this->assertTrue($this->service->isActive(SafeMode::Plugins));
    }

    public function test_deactivate_all_clears_all_modes(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->activate(SafeMode::Plugins);

        $this->service->deactivateAll();

        $this->assertFalse($this->service->isActive(SafeMode::Csp));
        $this->assertFalse($this->service->isActive(SafeMode::Plugins));
    }

    public function test_has_any_active(): void
    {
        $this->assertFalse($this->service->hasAnyActive());

        $this->service->activate(SafeMode::Csp);

        $this->assertTrue($this->service->hasAnyActive());
    }

    public function test_get_active_modes(): void
    {
        $this->service->activate(SafeMode::Csp);
        $this->service->activate(SafeMode::Plugins);

        $modes = $this->service->getActiveModes();

        $this->assertCount(2, $modes);
    }

    public function test_is_active_returns_false_for_inactive_mode(): void
    {
        $this->assertFalse($this->service->isActive(SafeMode::Csp));
    }
}
