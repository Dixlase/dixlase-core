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

declare(strict_types=1);

namespace App\DTO\Action;

use Illuminate\Database\Eloquent\Model;

/**
 * Standardized result returned by all Actions
 *
 * Carries the outcome of an Action execution including
 * the affected model, audit target info, and optional message.
 */
final readonly class ActionResult
{
    /**
     * @param  bool  $success  Whether the action succeeded
     * @param  Model|null  $model  The created/updated/affected model
     * @param  string|null  $message  Optional human-readable message
     * @param  string|null  $targetType  Audit log target class name
     * @param  int|string|null  $targetId  Audit log target ID
     * @param  string|null  $targetLabel  Audit log target display label
     * @param  array<string, mixed>  $metadata  Additional data for callers
     */
    public function __construct(
        public bool $success,
        public ?Model $model = null,
        public ?string $message = null,
        public ?string $targetType = null,
        public int|string|null $targetId = null,
        public ?string $targetLabel = null,
        public array $metadata = [],
    ) {}

    /**
     * Create a success result from a model
     */
    public static function success(Model $model, ?string $message = null, ?string $label = null, array $metadata = []): self
    {
        return new self(
            success: true,
            model: $model,
            message: $message,
            targetType: get_class($model),
            targetId: $model->getKey(),
            targetLabel: $label,
            metadata: $metadata,
        );
    }

    /**
     * Create a failure result
     */
    public static function failure(string $message, array $metadata = []): self
    {
        return new self(
            success: false,
            message: $message,
            metadata: $metadata,
        );
    }
}
