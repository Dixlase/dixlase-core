<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Tests\Unit\Services;

use App\Contracts\Repositories\BaseSettingRepositoryInterface;
use App\Services\LegalPageService;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Tests\TestCase;

class LegalPageServiceTest extends TestCase
{
    private BaseSettingRepositoryInterface&\Mockery\MockInterface $repository;

    private LegalPageService $service;

    /**
     * テスト用のページ種別設定
     *
     * @var array<string, array{name: string, description: string, required: bool, icon: string}>
     */
    private array $testPageTypes = [
        'privacy-policy' => [
            'name' => 'Privacy Policy',
            'description' => 'Privacy policy page.',
            'required' => false,
            'icon' => 'fas fa-shield-alt',
        ],
        'terms-of-service' => [
            'name' => 'Terms of Service',
            'description' => 'Terms of service page.',
            'required' => false,
            'icon' => 'fas fa-file-contract',
        ],
        'site-policy' => [
            'name' => 'Site Policy',
            'description' => 'Site policy page.',
            'required' => false,
            'icon' => 'fas fa-globe',
        ],
        'cookie-policy' => [
            'name' => 'Cookie Policy',
            'description' => 'Cookie policy page.',
            'required' => false,
            'icon' => 'fas fa-cookie-bite',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のページ種別を明示的にセット（コア config が空のため）
        config(['admin.legal-pages' => $this->testPageTypes]);

        $this->repository = Mockery::mock(BaseSettingRepositoryInterface::class);
        $this->service = new LegalPageService($this->repository);
    }

    /**
     * getPageTypes が設定されたページ種別を返すことを検証
     */
    public function test_get_page_types_returns_configured_types(): void
    {
        $types = $this->service->getPageTypes();

        $this->assertArrayHasKey('privacy-policy', $types);
        $this->assertArrayHasKey('terms-of-service', $types);
        $this->assertArrayHasKey('site-policy', $types);
        $this->assertArrayHasKey('cookie-policy', $types);
        $this->assertCount(4, $types);
    }

    /**
     * isRequired がデフォルトで false を返すことを検証
     */
    public function test_is_required_returns_false_by_default(): void
    {
        $this->assertFalse($this->service->isRequired('privacy-policy'));
        $this->assertFalse($this->service->isRequired('terms-of-service'));
        $this->assertFalse($this->service->isRequired('site-policy'));
        $this->assertFalse($this->service->isRequired('cookie-policy'));
    }

    /**
     * exists が URL 未設定時に false を返すことを検証
     */
    public function test_exists_returns_false_when_no_url_set(): void
    {
        $this->repository
            ->shouldReceive('get')
            ->with('legal_page_url:privacy-policy')
            ->andReturn(null);

        $this->assertFalse($this->service->exists('privacy-policy'));
    }

    /**
     * exists が URL 設定済み時に true を返すことを検証
     */
    public function test_exists_returns_true_when_url_is_set(): void
    {
        $this->repository
            ->shouldReceive('get')
            ->with('legal_page_url:privacy-policy')
            ->andReturn('https://example.com/privacy');

        $this->assertTrue($this->service->exists('privacy-policy'));
    }

    /**
     * url が未設定時に null を返すことを検証
     */
    public function test_url_returns_null_when_not_configured(): void
    {
        $this->repository
            ->shouldReceive('get')
            ->with('legal_page_url:terms-of-service')
            ->andReturn(null);

        $this->assertNull($this->service->url('terms-of-service'));
    }

    /**
     * url が設定済み URL を返すことを検証
     */
    public function test_url_returns_configured_url(): void
    {
        $this->repository
            ->shouldReceive('get')
            ->with('legal_page_url:terms-of-service')
            ->andReturn('https://example.com/terms');

        $this->assertSame('https://example.com/terms', $this->service->url('terms-of-service'));
    }

    /**
     * missingRequired が必須ページなし時に空配列を返すことを検証
     */
    public function test_missing_required_returns_empty_when_none_required(): void
    {
        // デフォルトではすべて required: false なので空配列
        $this->assertEmpty($this->service->missingRequired());
    }

    /**
     * setUrl が値を保存することを検証
     */
    public function test_set_url_stores_value(): void
    {
        $model = Mockery::mock(Model::class);

        $this->repository
            ->shouldReceive('set')
            ->once()
            ->with('legal_page_url:privacy-policy', 'https://example.com/privacy')
            ->andReturn($model);

        $this->service->setUrl('privacy-policy', 'https://example.com/privacy');
    }

    /**
     * setUrl に null を渡すと削除されることを検証
     */
    public function test_set_url_deletes_when_null(): void
    {
        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with('legal_page_url:privacy-policy')
            ->andReturn(true);

        $this->service->setUrl('privacy-policy', null);
    }

    /**
     * exists が空文字の場合に false を返すことを検証
     */
    public function test_exists_returns_false_when_url_is_empty_string(): void
    {
        $this->repository
            ->shouldReceive('get')
            ->with('legal_page_url:cookie-policy')
            ->andReturn('');

        $this->assertFalse($this->service->exists('cookie-policy'));
    }

    /**
     * setUrl に空文字を渡すと削除されることを検証
     */
    public function test_set_url_deletes_when_empty_string(): void
    {
        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with('legal_page_url:site-policy')
            ->andReturn(true);

        $this->service->setUrl('site-policy', '');
    }
}
