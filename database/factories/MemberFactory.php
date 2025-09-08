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

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class MemberFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'description' => fake()->optional()->sentence(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement([
                \App\Enums\MemberRole::ADMIN->value,
                \App\Enums\MemberRole::EDITOR->value,
                \App\Enums\MemberRole::AUTHOR->value,
                \App\Enums\MemberRole::CONTRIBUTOR->value,
                \App\Enums\MemberRole::RECEPTIONIST->value,
            ]),
            'status' => fake()->randomElement([
                \App\Enums\MemberStatus::Active->value,
                \App\Enums\MemberStatus::Inactive->value,
            ]),
            'appearance' => fake()->randomElement([
                \App\Enums\AppearanceMode::Auto->value,
                \App\Enums\AppearanceMode::Light->value,
                \App\Enums\AppearanceMode::Dark->value,
            ]),
            'login_notification_mode' => fake()->randomElement([
                \App\Enums\LoginNotificationMode::Disabled->value,
                \App\Enums\LoginNotificationMode::OnlyNewDevice->value,
                \App\Enums\LoginNotificationMode::Always->value,
            ]),
            'two_factor_mode' => fake()->randomElement([
                \App\Enums\TwoFactorMode::Disabled->value,
                \App\Enums\TwoFactorMode::OnlyNewDevice->value,
                \App\Enums\TwoFactorMode::Always->value,
            ]),
            'two_factor_method' => fake()->randomElement([
                \App\Enums\TwoFactorMethod::EMAIL->value,
                \App\Enums\TwoFactorMethod::DEVICE->value,
                \App\Enums\TwoFactorMethod::BIOMETRIC->value,
            ]),
            'last_login_ip' => fake()->optional()->ipv4(),
            'last_login_ua' => fake()->optional()->userAgent(),
            'last_login_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
