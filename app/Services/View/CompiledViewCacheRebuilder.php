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

namespace App\Services\View;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Clear the compiled Blade cache and immediately pre-compile every
 * view so the next request does not write to
 * `storage/framework/views/` during navigation.
 *
 * Why the pair, not just `view:clear`.
 * ------------------------------------
 * In development (`npm run dev`), Vite watches
 * `storage/framework/views/`. If Laravel writes a newly compiled
 * Blade file into that directory while a page navigation is in
 * flight, Vite sends a `[vite] page reload` signal — and the
 * navigation the operator just initiated is cancelled. The
 * user-visible symptom is "I clicked a menu item and the same
 * page came back". Extension install / update / rollback runs
 * `view:clear` after swapping .blade.php files (the compiled
 * cache is now stale); if the operator clicks anything before
 * the on-demand recompile completes, they hit this cancel.
 *
 * Pre-compiling with `view:cache` after the clear makes the
 * directory fully populated before the operator's next click,
 * so no writes happen during navigation. In production it does
 * a modest amount of extra work (a few hundred ms for a full
 * view tree) that would otherwise land on the first inbound
 * request — a net wash in latency, and a strict improvement in
 * cold-start responsiveness.
 *
 * Failure handling.
 * -----------------
 * `view:cache` compiles every view in the codebase. If the
 * freshly-swapped extension has a broken Blade file, or a plugin
 * references a symbol that was removed by the just-completed
 * update, the compile can throw — but by that point the file
 * swap and any migrations have already committed, so failing
 * the whole update on a cache miss would leave the operator on
 * a half-applied state for no benefit. We catch and log at
 * warning level instead: the next inbound request will compile
 * the affected view on demand (recreating the dev-env symptom
 * only for that one navigation), and the operator sees the log
 * entry pointing at the real problem.
 */
final class CompiledViewCacheRebuilder
{
    /**
     * Clear then rebuild `storage/framework/views/`.
     *
     * The clear step is unconditional — stale compiled Blade
     * from a pre-swap version of the extension must not remain.
     * The rebuild step is best-effort; a failure only reduces
     * cold-start quality on the affected views, never
     * corrupts state.
     */
    public static function rebuild(): void
    {
        Artisan::call('view:clear');

        try {
            Artisan::call('view:cache');
        } catch (\Throwable $e) {
            Log::warning('CompiledViewCacheRebuilder: view:cache after view:clear failed', [
                'error' => $e->getMessage(),
                'hint' => 'The next inbound request will compile each view on demand. '
                    .'If this fires immediately after an extension swap, the swapped '
                    .'source likely contains a broken Blade file — investigate the '
                    .'exception message for the offending template path.',
            ]);
        }
    }
}
