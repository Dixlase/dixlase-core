<?php

/**
 * This file is part of Dixlase.
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

use App\Models\MembersTwoFactorDevice;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembersTwoFactorDevice>
 */
class MembersTwoFactorDeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MembersTwoFactorDevice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'token' => hash('sha256', Str::random(64)),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'approved' => false,
            'approved_at' => null,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    /**
     * Create approved device challenges.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => true,
            'approved_at' => fake()->dateTimeBetween('-10 minutes', 'now'),
        ]);
    }

    /**
     * Create expired device challenges (for cleanup testing).
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 hour', '-1 minute'),
        ]);
    }

    /**
     * Create old expired device challenges (for cleanup testing).
     */
    public function old(int $daysAgo = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween("-{$daysAgo} days", "-7 days"),
        ]);
    }

    /**
     * Create recently created device challenges.
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('now', '+10 minutes'),
        ]);
    }

    /**
     * Create pending device challenges (not yet approved).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => false,
            'approved_at' => null,
            'expires_at' => now()->addMinutes(10),
        ]);
    }
}
