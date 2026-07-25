<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\DTO\Action;

use Illuminate\Database\Eloquent\Model;

/**
 * Standardized result returned by all Actions.
 *
 * Carries the outcome of an Action execution including the affected model,
 * audit target info, and optional message.
 *
 * ## Reserved `$metadata` keys (Plugin API contract)
 *
 * The metadata array is intentionally untyped, but the following keys are
 * **reserved** and have a fixed shape within `^0.1`. Plugins should write to
 * them when applicable and may read them from results returned by core actions.
 *
 * | Key            | Shape                                                                                | Purpose                                                                       |
 * |----------------|--------------------------------------------------------------------------------------|-------------------------------------------------------------------------------|
 * | `diff`         | `array<string, array{from: mixed, to: mixed}>`                                       | Field-level diff for UPDATE actions.                                          |
 * | `before`       | `array<string, mixed>`                                                               | Pre-change attribute snapshot.                                                |
 * | `after`        | `array<string, mixed>`                                                               | Post-change attribute snapshot.                                               |
 * | `changes`      | `array<string, mixed>`                                                               | Subset of `after` limited to the changed attributes.                          |
 * | `warnings`     | `array<int, string>`                                                                 | Non-fatal issues raised during `handle()` (e.g. "skipped 2 invalid rows").    |
 * | `side_effects` | `array<int, array{type: string, target: string, ...}>`                               | Secondary operations performed (cache invalidations, notifications, …).       |
 *
 * Additional keys are allowed but should be prefixed with the plugin slug
 * (for example `dixlase_pages.revision_id`) to avoid future collisions when
 * core introduces new reserved keys.
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
     * @param  array<string, mixed>  $metadata  Additional data for callers; see the class docblock for reserved keys
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
