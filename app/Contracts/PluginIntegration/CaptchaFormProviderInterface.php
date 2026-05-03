<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\PluginIntegration\CaptchaFormDTO;

/**
 * Contract for plugins that provide CAPTCHA forms
 *
 * To apply CAPTCHA to your plugin's forms, implement this interface and
 * register it with a tag in the service container via ServiceProvider
 *
 * ```php
 * $this->app->tag([MyCaptchaFormProvider::class], PluginServiceResolver::CAPABILITY_TAG);
 * ```
 *
 * Additionally, declare "captcha" in the "capabilities" array of plugin.json
 * The Core CaptchaService will aggregate form definitions from all plugin
 * implementations via PluginServiceResolver
 *
 * Validation and rendering continue to use the Core CaptchaHelper (to centrally
 * manage provider switching, failover, emergency bypass, etc.)
 */
interface CaptchaFormProviderInterface extends PluginCapabilityInterface
{
    /**
     * Return form definitions to which CAPTCHA should be applied in your plugin
     *
     * The returned DTO keys are converted to "{plugin_slug}.{key}" format, and
     * that full key is used in the captcha_enabled_forms table and admin panel
     *
     * @return CaptchaFormDTO[]
     */
    public function getCaptchaForms(): array;
}
