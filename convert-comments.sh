#!/usr/bin/env bash
#
# This file is part of Dixlase.
#
# Copyright (C) 2026 exc-D inc.
# https://exc-d.com
#
# Dixlase is dual-licensed. You may use this file under either:
#
#   (a) the GNU Affero General Public License version 3 or later, as
#       published by the Free Software Foundation, together with the
#       Dixlase Plugin and Theme Exception (see LICENSE-EXCEPTIONS for
#       full exception terms); or
#
#   (b) a commercial license agreement obtained from exc-D inc.
#       (see LICENSE.commercial, or contact office@exc-d.com).
#
# Unless you have entered into a commercial license agreement, this
# file is governed by the AGPL terms above.
#
# ====================================================================
# convert-comments.sh — flip Dixlase source comments between English
# (canonical) and a locale archive shipped with the source.
#
# Dictionaries live under resources/comment-translations/{locale}/ and
# their `_glossary.php` companion files. Plugins/themes may carry their
# own dictionaries at the same relative path.
#
# Common usage:
#   ./convert-comments.sh ja                  # EN → JA, in-place
#   ./convert-comments.sh ja --reverse        # JA → EN, in-place (revert)
#   ./convert-comments.sh ja --dry-run        # preview only, no writes
#   ./convert-comments.sh --list              # list available locales
#   ./convert-comments.sh --no-plugins ja     # core only (skip plugins)
#   ./convert-comments.sh --no-themes ja      # core only (skip themes)
#   ./convert-comments.sh ja --strict         # exit 1 on pending entries
#
# This is a thin host-side wrapper around the core Artisan command:
#   php artisan dls:comment:build --locale={locale} --in-place
#       --include-plugins --include-themes
#
# It runs that command inside the Dixlase PHP container. Override the
# container name with DIXLASE_PHP_CONTAINER if your environment differs
# (the default is `dixlase-dev-app`).
# ====================================================================

set -e

PHP_CONTAINER="${DIXLASE_PHP_CONTAINER:-dixlase-dev-app}"
APP_DIR_IN_CONTAINER="${DIXLASE_PHP_APP_DIR:-/var/www/html}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

print_usage() {
    cat <<'EOF'
Usage: ./convert-comments.sh <locale> [options]
       ./convert-comments.sh --list

Arguments:
  <locale>              Target locale (e.g., ja). Sub-directories under
                        resources/comment-translations/ are valid locales.

Options:
  --reverse             Revert from <locale> back to English.
  --dry-run             Preview substitutions without writing any file.
  --strict              Exit 1 if any pending (untranslated) entry exists
                        in the dictionary for <locale>.
  --no-plugins          Do not walk plugins/* dictionaries.
  --no-themes           Do not walk themes/* dictionaries.
  --path=<rel>          Restrict the source scan to a sub-path within
                        each extension (default: app).
  --list                Print available locales and exit.
  -h, --help            Print this message.

Environment:
  DIXLASE_PHP_CONTAINER Override the container name (default: dixlase-dev-app).
  DIXLASE_PHP_APP_DIR   Override the app dir inside the container
                        (default: /var/www/html).

Examples:
  ./convert-comments.sh ja
  ./convert-comments.sh ja --reverse
  ./convert-comments.sh ja --dry-run --no-plugins
EOF
}

list_locales() {
    local dict_root="${SCRIPT_DIR}/resources/comment-translations"
    if [ ! -d "$dict_root" ]; then
        echo "No locale directories found at: $dict_root" >&2
        return 1
    fi

    local found=0
    for entry in "$dict_root"/*/; do
        [ -d "$entry" ] || continue
        local name
        name=$(basename "$entry")
        # Skip metadata directories (underscore-prefixed).
        case "$name" in
            _*) continue ;;
        esac
        echo "  $name"
        found=1
    done

    if [ "$found" -eq 0 ]; then
        echo "  (no locales found)" >&2
        return 1
    fi
}

# ---------------------------------------------------------- arg parsing

LOCALE=""
REVERSE=false
DRY_RUN=false
STRICT=false
INCLUDE_PLUGINS=true
INCLUDE_THEMES=true
SCAN_PATH="app"

if [ $# -eq 0 ]; then
    print_usage
    exit 1
fi

while [ $# -gt 0 ]; do
    case "$1" in
        --reverse)     REVERSE=true ;;
        --dry-run)     DRY_RUN=true ;;
        --strict)      STRICT=true ;;
        --no-plugins)  INCLUDE_PLUGINS=false ;;
        --no-themes)   INCLUDE_THEMES=false ;;
        --path=*)      SCAN_PATH="${1#*=}" ;;
        --list)        echo "Available locales:"; list_locales; exit $?  ;;
        -h|--help)     print_usage; exit 0 ;;
        --*)           echo "Unknown option: $1" >&2; print_usage; exit 1 ;;
        *)
            if [ -z "$LOCALE" ]; then
                LOCALE="$1"
            else
                echo "Unexpected argument: $1" >&2
                print_usage
                exit 1
            fi
            ;;
    esac
    shift
done

if [ -z "$LOCALE" ]; then
    print_usage
    exit 1
fi

# ---------------------------------------------- preflight: container up

if ! command -v docker >/dev/null 2>&1; then
    echo "Error: 'docker' command not found." >&2
    echo "       Install Docker Desktop or run the equivalent Artisan call directly:" >&2
    echo "       php artisan dls:comment:build --locale=$LOCALE --in-place ..." >&2
    exit 1
fi

if ! docker ps --format '{{.Names}}' 2>/dev/null | grep -qE "^${PHP_CONTAINER}\$"; then
    echo "Error: container '${PHP_CONTAINER}' is not running." >&2
    echo "       Start it with 'docker compose up -d', or override the name:" >&2
    echo "         DIXLASE_PHP_CONTAINER=<name> ./convert-comments.sh $LOCALE" >&2
    exit 1
fi

# ---------------------------------------- preflight: locale exists

if [ ! -d "${SCRIPT_DIR}/resources/comment-translations/${LOCALE}" ]; then
    echo "Error: locale '${LOCALE}' has no dictionary." >&2
    echo "       Available locales:" >&2
    list_locales >&2
    exit 1
fi

# ---------------------------------------------------- run pre-check

if [ "$STRICT" = true ]; then
    STATUS_FLAGS=("dls:comment:status" "--locale=${LOCALE}" "--strict")
    if [ "$INCLUDE_PLUGINS" = true ]; then
        STATUS_FLAGS+=("--include-plugins")
    fi
    if [ "$INCLUDE_THEMES" = true ]; then
        STATUS_FLAGS+=("--include-themes")
    fi
    echo "[strict] Checking translation completeness..."
    if ! docker exec "$PHP_CONTAINER" php "${APP_DIR_IN_CONTAINER}/artisan" "${STATUS_FLAGS[@]}"; then
        echo "Error: --strict refused to convert: pending entries exist." >&2
        exit 1
    fi
fi

# ---------------------------------------------- run the build command

BUILD_FLAGS=("dls:comment:build" "--locale=${LOCALE}" "--in-place" "--path=${SCAN_PATH}")
if [ "$REVERSE" = true ]; then
    BUILD_FLAGS+=("--reverse")
fi
if [ "$DRY_RUN" = true ]; then
    BUILD_FLAGS+=("--dry-run")
fi
if [ "$INCLUDE_PLUGINS" = true ]; then
    BUILD_FLAGS+=("--include-plugins")
fi
if [ "$INCLUDE_THEMES" = true ]; then
    BUILD_FLAGS+=("--include-themes")
fi

DIRECTION="EN → ${LOCALE}"
if [ "$REVERSE" = true ]; then
    DIRECTION="${LOCALE} → EN"
fi

echo
echo "Direction:  ${DIRECTION}"
echo "Scan path:  ${SCAN_PATH} (within each extension)"
[ "$DRY_RUN" = true ] && echo "Mode:       DRY RUN (no writes)"
[ "$INCLUDE_PLUGINS" = false ] && echo "Plugins:    skipped"
[ "$INCLUDE_THEMES" = false ] && echo "Themes:     skipped"
echo

exec docker exec "$PHP_CONTAINER" php "${APP_DIR_IN_CONTAINER}/artisan" "${BUILD_FLAGS[@]}"
