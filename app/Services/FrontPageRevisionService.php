<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace App\Services;

use App\Models\FrontPage;
use App\Models\FrontPageRevision;

/**
 * Front page specific revision service (thin facade)
 *
 * Delegates actual logic to the generic {@see RevisionService}
 * Exists only to maintain backward compatibility for existing calls (Actions, Controllers, tests)
 */
class FrontPageRevisionService
{
    /** @deprecated See {@see RevisionService::SETTING_KEY_RETENTION} */
    public const SETTING_KEY_RETENTION = RevisionService::SETTING_KEY_RETENTION;

    /** @deprecated See {@see RevisionService::DEFAULT_RETENTION} */
    public const DEFAULT_RETENTION = RevisionService::DEFAULT_RETENTION;

    /** @deprecated See {@see RevisionService::MAX_RETENTION} */
    public const MAX_RETENTION = RevisionService::MAX_RETENTION;

    public function __construct(protected RevisionService $service) {}

    public function record(
        FrontPage $frontPage,
        string $type = FrontPageRevision::TYPE_AUTO,
        ?int $userId = null,
        ?string $note = null,
    ): ?FrontPageRevision {
        /** @var FrontPageRevision|null $revision */
        $revision = $this->service->record($frontPage, $type, $userId, $note);

        return $revision;
    }

    public function restore(FrontPageRevision $revision, ?int $userId = null): FrontPage
    {
        /** @var FrontPage $frontPage */
        $frontPage = $this->service->restore($revision, $userId);

        return $frontPage;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSnapshot(FrontPage $frontPage): array
    {
        return $this->service->buildSnapshot($frontPage);
    }

    public function countProtected(FrontPage $frontPage): int
    {
        return $this->service->countProtected($frontPage);
    }

    public function getRetentionCount(): int
    {
        return $this->service->getRetentionCount();
    }
}
