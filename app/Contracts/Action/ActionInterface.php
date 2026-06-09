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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

declare(strict_types=1);

namespace App\Contracts\Action;

use App\DTO\Action\ActionResult;

/**
 * Contract for all CMS business operations
 *
 * Actions encapsulate a single business operation with its authorization,
 * validation, execution, and side effects. Every state-changing operation
 * in the CMS should be represented as an Action.
 *
 * Actions are the single entry point for business logic, callable from
 * controllers, CLI commands, queue jobs, and (in the future) external APIs.
 */
interface ActionInterface
{
    /**
     * Execute the action
     *
     * @param  Actor  $actor  The entity performing the operation
     * @param  array<string, mixed>  $data  Validated input data
     * @return ActionResult The result of the operation
     */
    public function execute(Actor $actor, array $data): ActionResult;
}
