/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * Loads the solid icon font as soon as a wizard page has loaded.
 *
 * A browser fetches a web font only when a glyph that uses it is first
 * drawn. The confirmation step keeps its icons hidden until Install is
 * pressed, so without this the font was requested together with the form
 * submission and the "Installing…" dialog opened with empty boxes (#935).
 *
 * The faces come from the Font Awesome stylesheet itself, so no built
 * file name is needed, and nothing is logged when the font is already
 * cached or the stylesheet declares no such face.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

const ICON_FAMILY = /^["']?Font Awesome/;
const SOLID_WEIGHT = '900';

function loadIconFont() {
    if (!document.fonts) {
        return;
    }

    document.fonts.forEach((face) => {
        if (ICON_FAMILY.test(face.family) && face.weight === SOLID_WEIGHT && face.status === 'unloaded') {
            face.load().catch(() => {});
        }
    });
}

if (document.readyState === 'complete') {
    loadIconFont();
} else {
    window.addEventListener('load', loadIconFont, { once: true });
}
