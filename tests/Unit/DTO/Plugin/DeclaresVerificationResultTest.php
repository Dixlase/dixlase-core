<?php

namespace Tests\Unit\DTO\Plugin;

use App\DTO\Plugin\DeclaresVerificationResult;
use Tests\TestCase;

class DeclaresVerificationResultTest extends TestCase
{
    /**
     * 問題がない場合は isClean() が true を返すテスト
     */
    public function test_is_clean_when_no_issues(): void
    {
        $result = new DeclaresVerificationResult(
            issues: [],
            declaredCount: 3,
            actualCount: 3,
        );

        $this->assertTrue($result->isClean());
        $this->assertEquals(0, $result->issueCount());
    }

    /**
     * 問題がある場合は isClean() が false を返すテスト
     */
    public function test_is_not_clean_when_issues_exist(): void
    {
        $result = new DeclaresVerificationResult(
            issues: [
                ['key' => 'configs.roles', 'type' => 'declared_but_missing', 'description' => 'test'],
            ],
        );

        $this->assertFalse($result->isClean());
        $this->assertEquals(1, $result->issueCount());
    }

    /**
     * declared_but_missing の減点が -5 であるテスト
     */
    public function test_deduction_for_declared_but_missing(): void
    {
        $result = new DeclaresVerificationResult(
            issues: [
                ['key' => 'configs.roles', 'type' => 'declared_but_missing', 'description' => 'test'],
            ],
        );

        $this->assertEquals(-5, $result->totalDeduction());
    }

    /**
     * exists_but_undeclared の減点が -2 であるテスト
     */
    public function test_deduction_for_exists_but_undeclared(): void
    {
        $result = new DeclaresVerificationResult(
            issues: [
                ['key' => 'configs.roles', 'type' => 'exists_but_undeclared', 'description' => 'test'],
            ],
        );

        $this->assertEquals(-2, $result->totalDeduction());
    }

    /**
     * 複合減点のテスト
     */
    public function test_combined_deduction(): void
    {
        $result = new DeclaresVerificationResult(
            issues: [
                ['key' => 'configs.roles', 'type' => 'declared_but_missing', 'description' => 'test'],
                ['key' => 'migrations', 'type' => 'exists_but_undeclared', 'description' => 'test'],
            ],
        );

        $this->assertEquals(-7, $result->totalDeduction());
    }

    /**
     * toArray() のテスト
     */
    public function test_to_array(): void
    {
        $result = new DeclaresVerificationResult(
            issues: [],
            declaredCount: 2,
            actualCount: 3,
        );

        $array = $result->toArray();

        $this->assertTrue($array['is_clean']);
        $this->assertEquals(0, $array['issue_count']);
        $this->assertEquals(2, $array['declared_count']);
        $this->assertEquals(3, $array['actual_count']);
        $this->assertEquals(0, $array['total_deduction']);
    }

    /**
     * json_encode が正しく動作するテスト
     */
    public function test_json_serialize(): void
    {
        $result = new DeclaresVerificationResult();
        $json = json_encode($result);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['is_clean']);
    }
}
