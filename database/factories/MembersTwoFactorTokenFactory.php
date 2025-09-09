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

use App\Models\MembersTwoFactorToken;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembersTwoFactorToken>
 */
class MembersTwoFactorTokenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MembersTwoFactorToken::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'code' => fake()->numerify('######'),
            'expires_at' => now()->addMinutes(5),
        ];
    }

    /**
     * Create expired tokens (for cleanup testing).
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 hour', '-1 minute'),
        ]);
    }

    /**
     * Create old tokens (for cleanup testing).
     */
    public function old(int $daysAgo = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween("-{$daysAgo} days", "-7 days"),
            'created_at' => fake()->dateTimeBetween("-{$daysAgo} days", "-7 days"),
        ]);
    }

    /**
     * Create valid (not expired) tokens.
     */
    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('now', '+10 minutes'),
        ]);
    }
}
