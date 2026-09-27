<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Queue\Console\WorkCommand;
use Symfony\Component\Console\Input\StringInput;
use Tests\TestCase;

/**
 * The queue drain in routes/console.php must compile to a command line that
 * queue:work accepts. Passing the VALUE_NONE flag --stop-when-empty as a
 * key => true pair compiled it to --stop-when-empty='1', and every scheduled
 * run then failed with 'The "--stop-when-empty" option does not accept a value'.
 */
class QueueDrainScheduleTest extends TestCase
{
    private function queueDrainEvent(): Event
    {
        $this->app->make(Kernel::class)->bootstrap();

        foreach ($this->app->make(Schedule::class)->events() as $event) {
            if ($event->description === 'dixlase-queue-drain') {
                return $event;
            }
        }

        $this->fail('The dixlase-queue-drain schedule entry is not registered.');
    }

    public function test_stop_when_empty_is_compiled_as_a_bare_flag(): void
    {
        $command = $this->queueDrainEvent()->command;

        $this->assertStringContainsString(' --stop-when-empty', $command);
        $this->assertStringNotContainsString('--stop-when-empty=', $command);
    }

    public function test_compiled_command_line_parses_against_the_queue_work_definition(): void
    {
        $command = $this->queueDrainEvent()->command;
        $arguments = substr($command, strpos($command, 'queue:work') + strlen('queue:work'));

        $input = new StringInput($arguments);
        $input->bind($this->app->make(WorkCommand::class)->getDefinition());

        $this->assertTrue($input->getOption('stop-when-empty'));
        $this->assertSame('webhooks,default', $input->getOption('queue'));
        $this->assertSame('50', (string) $input->getOption('max-time'));
        $this->assertSame('1', (string) $input->getOption('tries'));
    }
}
