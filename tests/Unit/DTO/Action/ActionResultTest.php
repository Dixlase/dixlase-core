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

namespace Tests\Unit\DTO\Action;

use App\DTO\Action\ActionResult;
use App\Models\Member;
use Tests\TestCase;

class ActionResultTest extends TestCase
{
    public function test_success_factory_creates_successful_result(): void
    {
        $member = new Member();
        $member->id = 1;
        $member->account_name = 'admin';

        $result = ActionResult::success($member, 'Created successfully', 'admin');

        $this->assertTrue($result->success);
        $this->assertSame($member, $result->model);
        $this->assertSame('Created successfully', $result->message);
        $this->assertSame(Member::class, $result->targetType);
        $this->assertSame(1, $result->targetId);
        $this->assertSame('admin', $result->targetLabel);
        $this->assertSame([], $result->metadata);
    }

    public function test_failure_factory_creates_failed_result(): void
    {
        $result = ActionResult::failure('Something went wrong', ['code' => 'E001']);

        $this->assertFalse($result->success);
        $this->assertNull($result->model);
        $this->assertSame('Something went wrong', $result->message);
        $this->assertNull($result->targetType);
        $this->assertNull($result->targetId);
        $this->assertSame(['code' => 'E001'], $result->metadata);
    }

    public function test_success_factory_without_optional_params(): void
    {
        $member = new Member();
        $member->id = 5;

        $result = ActionResult::success($member);

        $this->assertTrue($result->success);
        $this->assertNull($result->message);
        $this->assertNull($result->targetLabel);
    }

    public function test_constructor_allows_full_customization(): void
    {
        $result = new ActionResult(
            success: true,
            message: 'Custom',
            targetType: 'App\\Models\\FrontPage',
            targetId: 99,
            targetLabel: 'Home Page',
            metadata: ['diff' => ['title' => ['old' => 'A', 'new' => 'B']]],
        );

        $this->assertTrue($result->success);
        $this->assertSame('App\\Models\\FrontPage', $result->targetType);
        $this->assertSame(99, $result->targetId);
        $this->assertSame('Home Page', $result->targetLabel);
        $this->assertArrayHasKey('diff', $result->metadata);
    }
}
