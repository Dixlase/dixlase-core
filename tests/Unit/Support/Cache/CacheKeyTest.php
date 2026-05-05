<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

namespace Tests\Unit\Support\Cache;

use App\Support\Cache\CacheKey;
use App\Support\Cache\SiteScopedCacheKey;
use PHPUnit\Framework\TestCase;

class CacheKeyTest extends TestCase
{
    public function test_prefix_constant_is_dixlase(): void
    {
        $this->assertSame('dixlase', CacheKey::PREFIX);
    }

    public function test_core_builds_4_segment_key(): void
    {
        $this->assertSame(
            'dixlase:core:routes:list',
            CacheKey::core('routes', 'list'),
        );
    }

    public function test_plugin_builds_5_segment_key_with_slug(): void
    {
        $this->assertSame(
            'dixlase:plugin:dixlase-pages:manifest:v1',
            CacheKey::plugin('dixlase-pages', 'manifest', 'v1'),
        );
    }

    public function test_theme_builds_5_segment_key_with_slug(): void
    {
        $this->assertSame(
            'dixlase:theme:dixlase-onepage:view:home',
            CacheKey::theme('dixlase-onepage', 'view', 'home'),
        );
    }

    public function test_tag_builds_4_segment_tag(): void
    {
        $this->assertSame(
            'dixlase:tag:plugin:dixlase-pages',
            CacheKey::tag('plugin', 'dixlase-pages'),
        );
    }

    public function test_site_returns_site_scoped_builder(): void
    {
        $this->assertInstanceOf(SiteScopedCacheKey::class, CacheKey::site(1));
    }

    public function test_site_scoped_key_uses_site_id_as_owner(): void
    {
        $this->assertSame(
            'dixlase:site:1:nav:public',
            CacheKey::site(1)->key('nav', 'public'),
        );
    }

    public function test_site_scoped_core_inserts_core_after_site(): void
    {
        $this->assertSame(
            'dixlase:site:1:core:routes:list',
            CacheKey::site(1)->core('routes', 'list'),
        );
    }

    public function test_site_scoped_plugin_inserts_plugin_after_site(): void
    {
        $this->assertSame(
            'dixlase:site:1:plugin:dixlase-pages:manifest:v1',
            CacheKey::site(1)->plugin('dixlase-pages', 'manifest', 'v1'),
        );
    }

    public function test_site_scoped_theme_inserts_theme_after_site(): void
    {
        $this->assertSame(
            'dixlase:site:1:theme:dixlase-onepage:view:home',
            CacheKey::site(1)->theme('dixlase-onepage', 'view', 'home'),
        );
    }

    public function test_site_scoped_tag_format(): void
    {
        $this->assertSame(
            'dixlase:tag:site:1:plugin:dixlase-pages',
            CacheKey::site(1)->tag('plugin', 'dixlase-pages'),
        );
    }

    public function test_site_id_can_be_string(): void
    {
        $this->assertSame(
            'dixlase:site:main:nav:public',
            CacheKey::site('main')->key('nav', 'public'),
        );
    }

    public function test_site_id_integer_renders_without_quotes(): void
    {
        $this->assertSame(
            'dixlase:site:42:plugin:dixlase-pages:manifest:v1',
            CacheKey::site(42)->plugin('dixlase-pages', 'manifest', 'v1'),
        );
    }

    public function test_each_segment_is_separated_by_colon_only(): void
    {
        $key = CacheKey::plugin('dixlase-pages', 'manifest', 'v1');
        $this->assertCount(5, explode(':', $key));
    }

    public function test_site_scoped_plugin_has_seven_segments(): void
    {
        $key = CacheKey::site(1)->plugin('dixlase-pages', 'manifest', 'v1');
        $this->assertCount(7, explode(':', $key));
    }
}
