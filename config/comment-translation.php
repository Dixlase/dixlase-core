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

return [

    /*
    |--------------------------------------------------------------------------
    | Comment Translation (Core)
    |--------------------------------------------------------------------------
    |
    | Settings shared by the user-facing half of the comment-translation
    | pipeline: `dls:comment:build` and `dls:comment:status`, plus the
    | services they depend on (TranslationFileService, CommentBuilderService).
    |
    | These live in Core rather than in DixlaseCoreDevKit because the
    | user-facing commands must keep working in a release checkout, where
    | development plugins are not present. The authoring half of the pipeline
    | (`dls:comment:extract` / `dls:comment:translate`) stays in CoreDevKit
    | and keeps its own settings under `core-dev.comment_translation`.
    |
    */

    // Translation file storage root (relative to the Core project root).
    // Per-locale sub-directories (e.g. `ja/`, `zh/`) live under this path.
    'storage_path' => 'resources/comment-translations',

    // Default locale used by the translation pipeline when none is given
    // on the command line via `--locale=`.
    'default_locale' => 'ja',

];
