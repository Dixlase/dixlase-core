<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

declare(strict_types=1);

namespace Tests\Unit\Support\Core;

use App\Models\AuditLog;
use App\Services\Core\CoreUpdater;
use App\Support\Core\CoreOperationContext;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The request a core update was started from, carried to the process that
 * performs it.
 *
 * @see \App\Support\Core\CoreOperationContext
 */
class CoreOperationContextTest extends TestCase
{
    private string $lockPath;

    /** Contents of a real lock file this test displaced, if any. */
    private ?string $savedLock = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lockPath = CoreUpdater::inProgressFlagPath();
        @mkdir(dirname($this->lockPath), 0775, true);

        // A core update could be in flight in the environment this runs
        // in. Put back whatever was there.
        if (is_file($this->lockPath)) {
            $saved = @file_get_contents($this->lockPath);
            $this->savedLock = is_string($saved) ? $saved : null;
            @unlink($this->lockPath);
        }

        CoreOperationContext::forget();
    }

    protected function tearDown(): void
    {
        @unlink($this->lockPath);

        if ($this->savedLock !== null) {
            @file_put_contents($this->lockPath, $this->savedLock);
        }

        CoreOperationContext::forget();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeLock(array $payload): void
    {
        file_put_contents($this->lockPath, (string) json_encode($payload));
    }

    #[Test]
    public function it_takes_the_request_s_own_values_for_the_lock_payload(): void
    {
        $request = Request::create('/admin/updates/core', 'POST', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (test)',
            'HTTP_X_REQUEST_ID' => 'req-abc',
        ]);

        $fields = CoreOperationContext::forLockPayload($request);

        $this->assertSame('203.0.113.7', $fields['ip_address']);
        $this->assertSame('Mozilla/5.0 (test)', $fields['user_agent']);
        $this->assertSame('req-abc', $fields['request_id']);
        // No session was attached to this request, so none is claimed.
        $this->assertArrayNotHasKey('session_id', $fields);
    }

    #[Test]
    public function it_caps_the_user_agent_the_client_chose(): void
    {
        $request = Request::create('/x', 'POST', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_USER_AGENT' => str_repeat('A', CoreOperationContext::USER_AGENT_MAX_LENGTH + 500),
        ]);

        $fields = CoreOperationContext::forLockPayload($request);

        $this->assertSame(
            CoreOperationContext::USER_AGENT_MAX_LENGTH,
            mb_strlen($fields['user_agent']),
            'The user agent comes from the client, so it is capped rather than stored whole.'
        );
    }

    #[Test]
    public function an_operation_started_from_the_panel_is_attributed_to_the_web(): void
    {
        $this->writeLock([
            'started_at' => time(),
            'target_version' => '0.3.0',
            'started_by_id' => 4,
            'ip_address' => '203.0.113.7',
            'user_agent' => 'Mozilla/5.0 (test)',
            'session_id' => 'sess-1',
            'request_id' => 'req-abc',
        ]);

        $fields = CoreOperationContext::auditFields();

        $this->assertSame('203.0.113.7', $fields['ip_address']);
        $this->assertSame('Mozilla/5.0 (test)', $fields['user_agent']);
        $this->assertSame('sess-1', $fields['session_id']);
        $this->assertSame('req-abc', $fields['request_id']);
        $this->assertSame(AuditLog::ACTOR_SOURCE_WEB, $fields['actor_source']);

        // The lock payload's own keys are not audit fields.
        $this->assertArrayNotHasKey('target_version', $fields);
        $this->assertArrayNotHasKey('started_by_id', $fields);
    }

    #[Test]
    public function an_operation_started_in_a_shell_claims_nothing(): void
    {
        // No lock file: nothing spawned this, so the entry must keep
        // saying CLI rather than be dressed up as a web action.
        $this->assertFileDoesNotExist($this->lockPath);

        $this->assertSame([], CoreOperationContext::auditFields());
    }

    #[Test]
    public function a_lock_file_with_only_the_old_keys_claims_nothing(): void
    {
        // The payload written by a version before this existed.
        $this->writeLock(['started_at' => time(), 'target_version' => '0.3.0', 'started_by_id' => 4]);

        $this->assertSame([], CoreOperationContext::auditFields());
    }

    #[Test]
    public function an_unreadable_payload_claims_nothing(): void
    {
        file_put_contents($this->lockPath, 'not json');

        $this->assertSame([], CoreOperationContext::auditFields());
    }

    #[Test]
    public function the_capture_survives_the_lock_file_being_removed(): void
    {
        $this->writeLock(['ip_address' => '203.0.113.7']);

        CoreOperationContext::capture();

        // This is what the update does in its finally block, and it
        // happens between audit writes.
        @unlink($this->lockPath);

        $this->assertSame('203.0.113.7', CoreOperationContext::auditFields()['ip_address']);
    }

    #[Test]
    public function a_capped_value_in_the_file_is_capped_again_on_the_way_out(): void
    {
        // The file is written by the panel, but it sits on disk between
        // two processes and its user agent came from a client.
        $this->writeLock(['user_agent' => str_repeat('B', 5000)]);

        $this->assertSame(
            CoreOperationContext::USER_AGENT_MAX_LENGTH,
            mb_strlen(CoreOperationContext::auditFields()['user_agent'])
        );
    }

    #[Test]
    public function forget_drops_the_capture(): void
    {
        $this->writeLock(['ip_address' => '203.0.113.7']);
        CoreOperationContext::capture();

        @unlink($this->lockPath);
        CoreOperationContext::forget();

        $this->assertSame([], CoreOperationContext::auditFields());
    }

    #[Test]
    public function both_panel_launch_paths_put_the_request_in_the_lock_payload(): void
    {
        $controller = (string) file_get_contents(
            app_path('Http/Controllers/Admin/Settings/Systems/AdminSystemUpdatesController.php')
        );

        $this->assertSame(
            2,
            substr_count($controller, 'CoreOperationContext::forLockPayload($request)'),
            'Both the update and the rollback button must carry the request across.'
        );
    }

    #[Test]
    public function both_operations_capture_before_anything_removes_the_lock(): void
    {
        $updater = (string) file_get_contents(app_path('Services/Core/CoreUpdater.php'));
        $rollback = (string) file_get_contents(app_path('Console/Commands/CoreRollback.php'));

        foreach (['CoreUpdater' => $updater, 'CoreRollback' => $rollback] as $name => $source) {
            $captureAt = strpos($source, 'CoreOperationContext::capture();');
            $unlinkAt = strpos($source, 'inProgressFlagPath());');

            $this->assertNotFalse($captureAt, "{$name} must capture the lock payload.");
            $this->assertNotFalse($unlinkAt, "{$name} is expected to remove the lock file.");
            $this->assertLessThan(
                $unlinkAt,
                $captureAt,
                "{$name} must read the lock payload before anything removes the file."
            );
        }
    }
}
