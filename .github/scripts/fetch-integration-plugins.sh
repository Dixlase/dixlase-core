#!/usr/bin/env bash
# Shallow-clone every plugin listed in .github/integration-plugins.tsv into
# plugins/<dir-name>/. Used by CI jobs that need the bundled plugins
# (test.yml's plugin-tests / migration / migration-lint /
# comment-translation-status, plugin-api-check.yml) to populate the source
# tree before composer / phpunit / artisan are invoked.
#
# Auth: when the plugins are private, callers should pre-configure
# `git config --global url."https://x-access-token:<PAT>@github.com/".insteadOf "https://github.com/"`
# so each clone picks up the token. The wrapping workflow step does this
# from secrets.SUBMODULE_PAT before invoking this script.
#
# Failure policy: a clone that fails (network blip, missing repo, etc.)
# logs a WARN and the script continues. CI jobs that strictly need every
# plugin can post-check with `ls -d plugins/Dixlase*/` after this runs.
# This mirrors the previous "git submodule update --depth=1 --force" loop
# the workflows used, which also continued past per-submodule failures.
set -u

LIST="${INTEGRATION_PLUGINS_LIST:-.github/integration-plugins.tsv}"

if [ ! -f "$LIST" ]; then
    echo "::warning::Integration plugin list not found at $LIST — nothing to fetch"
    exit 0
fi

echo "=== Cloning integration plugins from $LIST ==="
while IFS=$'\t' read -r dir url _rest; do
    # Skip blanks and comment lines.
    [ -z "${dir:-}" ] && continue
    case "$dir" in
        '#'*) continue ;;
    esac
    if [ -z "${url:-}" ]; then
        echo "::warning::Skipping row with missing URL: '$dir'"
        continue
    fi

    target="plugins/$dir"

    # If something is already present at the target — either pre-populated by
    # a prior `git submodule init/update` from a not-yet-updated workflow, or
    # carried in from a cache — skip the clone. The existing tree is good
    # enough for the downstream CI steps to operate on.
    if [ -d "$target/.git" ] || [ -e "$target/composer.json" ]; then
        echo "--- $dir already present at $target, skipping clone ---"
        continue
    fi

    echo "--- Cloning $dir from $url ---"
    rm -rf "$target"
    if ! git clone --depth=1 "$url" "$target"; then
        echo "::warning::Failed to clone $dir from $url (continuing)"
    fi
done <"$LIST"

echo "=== Plugin directories now present ==="
ls -d plugins/*/ 2>/dev/null || echo "No plugin directories found"
