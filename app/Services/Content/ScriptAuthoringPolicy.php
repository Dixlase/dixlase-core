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

namespace App\Services\Content;

use App\Enums\ContentEditorType;
use App\Enums\MemberRole;
use App\Models\Member;
use App\Services\PermissionService;

/**
 * Who may put script-capable content on the front page.
 *
 * The HTML editor and the front page's custom CSS / JS are served from the
 * site's own origin, so whatever an author writes there runs in every
 * visitor's browser -- including an administrator's, with their session.
 * That makes authoring them equivalent to acting as an administrator, so it
 * is limited to ADMIN and above. Other authors keep the Markdown editor.
 *
 * Only the role is checked here. The Pages extension additionally hides the
 * HTML editor in simple mode, which core cannot copy: core's `admin_mode`
 * defaults to simple, so the same rule would stop every site creating an
 * HTML front page. Whether the HTML editor should be off by default is a
 * product decision, not part of this rule.
 *
 * The official Pages and Blog extensions each carry the same rule for their
 * own content. This is core's copy for the front page, which is the one
 * place the rule was missing. Converging the three on one shared contract
 * is worth doing separately; duplicating the policy is not worth leaving
 * the front page open for.
 */
final class ScriptAuthoringPolicy
{
    public const MINIMUM_ROLE = MemberRole::ADMIN;

    /** Editor types every author may use. */
    public const SAFE_EDITOR_SLUGS = ['markdown'];

    public static function canAuthorScripts(?Member $member): bool
    {
        if ($member === null) {
            return false;
        }

        // Through PermissionService, so the role hierarchy is read in one
        // place. MemberActor::hasRole() answers from the same function, and
        // the actions ask the actor -- so the form and the server agree by
        // construction rather than by both doing their own comparison.
        return PermissionService::memberHasRole($member, self::MINIMUM_ROLE);
    }

    /**
     * Editor type slugs this member may choose for a new front page.
     *
     * @return list<string>
     */
    public static function creatableEditorSlugs(?Member $member): array
    {
        $slugs = self::SAFE_EDITOR_SLUGS;

        if (self::canAuthorScripts($member)) {
            $slugs[] = ContentEditorType::HTML->slug();
        }

        return $slugs;
    }

    /**
     * Whether this member may edit a front page stored with this editor
     * type. An HTML body is script-capable, so only members who may author
     * scripts may change one.
     */
    public static function canEditEditorType(?Member $member, ContentEditorType $editorType): bool
    {
        if (in_array($editorType->slug(), self::SAFE_EDITOR_SLUGS, true)) {
            return true;
        }

        return self::canAuthorScripts($member);
    }

    /**
     * Whether a front page of this editor type carries its own CSS: HTML,
     * and Markdown, whose id and class attributes it styles.
     */
    public static function carriesCustomCss(?ContentEditorType $editorType): bool
    {
        return $editorType === ContentEditorType::HTML || $editorType === ContentEditorType::MARKDOWN;
    }

    /**
     * Whether a front page of this editor type carries its own JavaScript:
     * HTML alone. A Markdown front page never runs a script of its own.
     */
    public static function carriesCustomJs(?ContentEditorType $editorType): bool
    {
        return $editorType === ContentEditorType::HTML;
    }
}
