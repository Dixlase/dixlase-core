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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaptchaEnabledForm extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'captcha_enabled_forms';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'form_key',
        'enabled',
        'provider',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get all enabled form keys.
     *
     * @return array<string>
     */
    public static function getEnabledFormKeys(): array
    {
        return static::where('enabled', true)
            ->pluck('form_key')
            ->toArray();
    }

    /**
     * Check if a form is enabled.
     *
     * @param string $formKey
     * @return bool
     */
    public static function isFormEnabled(string $formKey): bool
    {
        $form = static::where('form_key', $formKey)->first();
        
        return $form ? $form->enabled : false;
    }

    /**
     * Enable a form.
     *
     * @param string $formKey
     * @param string|null $provider
     * @return static
     */
    public static function enableForm(string $formKey, ?string $provider = null): static
    {
        return static::updateOrCreate(
            ['form_key' => $formKey],
            [
                'enabled' => true,
                'provider' => $provider,
            ]
        );
    }

    /**
     * Disable a form.
     *
     * @param string $formKey
     * @return bool
     */
    public static function disableForm(string $formKey): bool
    {
        return static::where('form_key', $formKey)->delete();
    }
}
