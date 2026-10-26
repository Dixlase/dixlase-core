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

namespace App\Http\Controllers\Admin\Media;

use App\Actions\Media\CreateMediaTagAction;
use App\Actions\Media\DeleteMediaTagAction;
use App\Actions\Media\RenameMediaTagAction;
use App\Actions\Media\UpdateMediaTagsAction;
use App\Actors\MemberActor;
use App\Helpers\AdminHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\Media;
use App\Models\MediaTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tags of the media library (#766): the tag page (create, rename, delete),
 * the list the tag input and the selector's chips read, and bulk tagging
 * from the library's selection mode.
 */
class AdminMediaTagController extends AdminLoggedInController
{
    /**
     * The tag page: every tag with its file count, and for members who can
     * edit media, adding, renaming and deleting.
     */
    public function index(): View
    {
        $this->viewParams['tags'] = MediaTag::withMediaCount()->get();

        return view('admin.media.tags', $this->viewParams);
    }

    /**
     * Every tag of the site in name order, with the number of files that
     * carry it. Only these three fields leave the server.
     */
    public function list(): JsonResponse
    {
        $tags = [];
        foreach (MediaTag::withMediaCount()->get() as $tag) {
            $tags[] = [
                'id' => $tag->id,
                'name' => $tag->name,
                'count' => (int) $tag->media_count,
            ];
        }

        return response()->json([
            'success' => true,
            'tags' => $tags,
        ]);
    }

    /**
     * Add tags to, or remove one from, every selected file. Each file goes
     * through UpdateMediaTagsAction, so the audit trail and the permission
     * check are the same as for a file's own page.
     */
    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'add' => ['nullable', 'array', 'max:'.MediaTag::MAX_TAGS_PER_REQUEST],
            'add.*' => ['string', 'max:'.MediaTag::MAX_NAME_LENGTH],
            'remove' => ['nullable', 'array', 'max:'.MediaTag::MAX_TAGS_PER_REQUEST],
            'remove.*' => ['integer'],
        ]);

        $add = $validated['add'] ?? [];
        $remove = $validated['remove'] ?? [];
        if ($add === [] && $remove === []) {
            return back()->with('error', __('admin/media/tags.error.nothing_to_change'));
        }

        $actor = new MemberActor(AdminHelper::getMember());
        $changed = 0;
        $failed = 0;
        foreach (Media::whereIn('id', $validated['ids'])->get() as $media) {
            try {
                $result = (new UpdateMediaTagsAction($media))->execute($actor, ['add' => $add, 'remove' => $remove]);
                if ($result->metadata['changed'] ?? false) {
                    $changed++;
                }
            } catch (\Throwable $e) {
                $failed++;
                report($e);
            }
        }

        $redirect = back()->with('success', __('admin/media/tags.success.bulk_updated', ['count' => $changed]));
        if ($failed > 0) {
            $redirect = $redirect->with('error', __('admin/media/tags.error.bulk_partial', ['count' => $failed]));
        }

        return $redirect;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:'.MediaTag::MAX_NAME_LENGTH],
        ]);

        $actor = new MemberActor(AdminHelper::getMember());
        (new CreateMediaTagAction())->execute($actor, $validated);

        return back()->with('success', __('admin/media/tags.success.created'));
    }

    public function rename(Request $request, MediaTag $mediaTag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:'.MediaTag::MAX_NAME_LENGTH],
        ]);

        $actor = new MemberActor(AdminHelper::getMember());
        (new RenameMediaTagAction($mediaTag))->execute($actor, $validated);

        return back()->with('success', __('admin/media/tags.success.renamed'));
    }

    public function destroy(MediaTag $mediaTag): RedirectResponse
    {
        $actor = new MemberActor(AdminHelper::getMember());
        (new DeleteMediaTagAction($mediaTag))->execute($actor, []);

        return back()->with('success', __('admin/media/tags.success.deleted'));
    }
}
