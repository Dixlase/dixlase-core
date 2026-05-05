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
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Tests\Unit\Events;

use App\Events\DixlaseEvents;
use Tests\TestCase;

class DixlaseEventsTest extends TestCase
{
    public function test_url_slug_changed_constant_is_defined(): void
    {
        $this->assertSame('dixlase.url.slug_changed', DixlaseEvents::URL_SLUG_CHANGED);
    }

    public function test_admin_url_changed_constant_is_defined(): void
    {
        $this->assertSame('dixlase.url.admin_url_changed', DixlaseEvents::ADMIN_URL_CHANGED);
    }

    public function test_url_category_returns_only_url_events(): void
    {
        $events = DixlaseEvents::byCategory('url');

        $this->assertContains(DixlaseEvents::URL_SLUG_CHANGED, $events);
        $this->assertContains(DixlaseEvents::ADMIN_URL_CHANGED, $events);
        $this->assertNotContains(DixlaseEvents::BACKUP_STARTED, $events);

        foreach ($events as $event) {
            $this->assertStringStartsWith('dixlase.url.', $event);
        }
    }

    public function test_all_includes_url_events(): void
    {
        $all = DixlaseEvents::all();

        $this->assertContains(DixlaseEvents::URL_SLUG_CHANGED, $all);
        $this->assertContains(DixlaseEvents::ADMIN_URL_CHANGED, $all);
    }
}
