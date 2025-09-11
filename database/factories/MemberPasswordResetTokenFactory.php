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

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for members_password_reset_tokens table
 */
class MemberPasswordResetTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'token' => Str::random(64),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Create old password reset tokens (for cleanup testing).
     */
    public function old(int $daysAgo = 35): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween("-{$daysAgo} days", "-30 days"),
        ]);
    }

    /**
     * Create recent password reset tokens.
     */
    public function recent(int $daysAgo = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween("-{$daysAgo} days", 'now'),
        ]);
    }

    /**
     * Helper method to create records directly in database
     * (since this table doesn't have a corresponding Eloquent model)
     */
    public static function createInDatabase(int $count = 10, array $states = []): void
    {
        $factory = new static();
        
        for ($i = 0; $i < $count; $i++) {
            $data = $factory->definition();
            
            // Apply states if provided
            foreach ($states as $state) {
                if (method_exists($factory, $state)) {
                    $stateData = $factory->$state()->definition();
                    $data = array_merge($data, $stateData);
                }
            }
            
            \Illuminate\Support\Facades\DB::table('members_password_reset_tokens')->insert($data);
        }
    }
}
