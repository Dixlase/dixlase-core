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

use App\Models\MemberLoginAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberLoginAttempt>
 */
class MemberLoginAttemptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MemberLoginAttempt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifier' => fake()->safeEmail(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'successful' => fake()->boolean(20), // 20% success rate
            'attempted_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the login attempt was successful.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'successful' => true,
        ]);
    }

    /**
     * Indicate that the login attempt failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'successful' => false,
        ]);
    }

    /**
     * Create old login attempts (for cleanup testing).
     */
    public function old(int $daysAgo = 35): static
    {
        return $this->state(fn (array $attributes) => [
            'attempted_at' => fake()->dateTimeBetween("-{$daysAgo} days", "-30 days"),
        ]);
    }

    /**
     * Create recent login attempts.
     */
    public function recent(int $daysAgo = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'attempted_at' => fake()->dateTimeBetween("-{$daysAgo} days", 'now'),
        ]);
    }
}
