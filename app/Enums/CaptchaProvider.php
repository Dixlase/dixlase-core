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

namespace App\Enums;

enum CaptchaProvider: string
{
    case TURNSTILE = 'turnstile';
    case GOOGLE = 'google';
    case GOOGLE_ENTERPRISE = 'google_enterprise';


    public function label(): string
    {
        return match ($this) {
            self::TURNSTILE => 'Cloudflare Turnstile',
            self::GOOGLE => 'Google reCAPTCHA',
            self::GOOGLE_ENTERPRISE => 'Google reCAPTCHA Enterprise',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::TURNSTILE => 'cloudflare_turnstile',
            self::GOOGLE => 'google_recaptcha',
            self::GOOGLE_ENTERPRISE => 'google_recaptcha_enterprise',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn($provider) => [
            $provider->value => $provider->label()
        ])->toArray();
    }

    public static function fromString(string $value): ?self
    {
        return self::tryFrom($value);
    }

    public static function getAllProviders(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getSetupUrl(): string
    {
        return match ($this) {
            self::TURNSTILE => 'https://dash.cloudflare.com/?to=/:account/turnstile',
            self::GOOGLE => 'https://www.google.com/recaptcha/admin/create',
            self::GOOGLE_ENTERPRISE => 'https://cloud.google.com/recaptcha-enterprise/docs/create-key',

        };
    }
}
