<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace Database\Factories;

use App\Models\TrustedDevice;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrustedDevice>
 */
class TrustedDeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TrustedDevice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'token' => Str::random(40),
            'device_name' => fake()->randomElement([
                'Chrome on Windows',
                'Safari on iPhone',
                'Firefox on macOS',
                'Edge on Windows',
                'Chrome on Android',
                'Safari on iPad'
            ]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'last_used_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create old trusted devices (for cleanup testing).
     */
    public function old(int $daysAgo = 95): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => fake()->dateTimeBetween("-{$daysAgo} days", "-90 days"),
        ]);
    }

    /**
     * Create recently used trusted devices.
     */
    public function recent(int $daysAgo = 30): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => fake()->dateTimeBetween("-{$daysAgo} days", 'now'),
        ]);
    }

    /**
     * Create trusted devices that have never been used (only created_at).
     */
    public function neverUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_used_at' => null,
        ]);
    }
}
