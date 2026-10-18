#!/usr/bin/env bash
# Shallow-clone every extension this repository integration-tests into
# plugins/<dir-name>/, and tell the rest of the job how to run them.
# Used by CI jobs that need the bundled extensions (test.yml's
# plugin-tests / migration / migration-lint / comment-translation-status,
# plugin-api-check.yml, generated-docs-drift.yml) to populate the source
# tree before composer / phpunit / artisan are invoked.
#
# Two sources, merged here:
#
#   1. .github/integration-plugins.tsv — the extensions this repository
#      distributes publicly. Tracked, readable, and all a fork needs.
#   2. $INTEGRATION_EXTENSIONS — rows for extensions that are not
#      distributed publicly, held in a repository or organisation
#      variable. A public repository should not name software its readers
#      cannot obtain (dixlase-core#619), and a fork has no token to clone
#      them with anyway, so the rows live outside the tree and are merged
#      at run time.
#
# Row format is the same for both: <directory>\t<clone-url>\t<tier>\t<flags>
# Tier (1-3) picks which plugin-test step runs the extension's suite;
# anything else, or absent, means tier 3. Flags are comma-separated:
#
#   no-api-scan     exclude it from the @api boundary scan
#                   (development tooling reaches into core on purpose)
#   doc-generators  its ServiceProvider carries the document generators
#                   that the generated-documents check has to register
#
# Every directory name and URL that came from the variable is passed to
# ::add-mask:: before it is used, because a public repository's workflow
# logs are public as well. That is also why nothing here echoes a row.
#
# What the script leaves behind for later steps, when running in Actions:
#
#   phpunit.xml                  a <testsuite> appended per variable row,
#                                so `--testsuite=<name>` keeps working
#                                without the name being tracked
#   $GITHUB_ENV EXTRA_SUITES_TIER1..3   comma-separated suite names
#   $GITHUB_ENV API_SCAN_EXCLUDE        space-separated directory names
#   $GITHUB_ENV DOC_GENERATOR_PROVIDER  fully-qualified provider class
#
# Auth: when an extension is not public, callers should pre-configure
# `git config --global url."https://x-access-token:<PAT>@github.com/".insteadOf "https://github.com/"`
# so each clone picks up the token. The wrapping workflow step does this
# from secrets.SUBMODULE_PAT before invoking this script.
#
# Failure policy: a clone that fails (network blip, missing repo, etc.)
# logs a WARN and the script continues. CI jobs that strictly need every
# extension can post-check with `ls -d plugins/*/` after this runs.
set -u

LIST="${INTEGRATION_PLUGINS_LIST:-.github/integration-plugins.tsv}"
PHPUNIT_CONFIG="${INTEGRATION_PHPUNIT_CONFIG:-phpunit.xml}"

if [ ! -f "$LIST" ]; then
    echo "::warning::Integration extension list not found at $LIST — nothing to fetch"
    exit 0
fi

# Rows from the variable, with the directory name and URL masked before
# anything else can print them.
extra_rows=""
if [ -n "${INTEGRATION_EXTENSIONS:-}" ]; then
    while IFS=$'\t' read -r dir url tier flags; do
        [ -z "${dir:-}" ] && continue
        case "$dir" in '#'*) continue ;; esac
        echo "::add-mask::$dir"
        [ -n "${url:-}" ] && echo "::add-mask::$url"
        extra_rows="${extra_rows}${dir}	${url:-}	${tier:-3}	${flags:-}
"
    done <<EOF
$INTEGRATION_EXTENSIONS
EOF
fi

cloned=0
failed=0
skipped=0

clone_row() {
    dir="$1"
    url="$2"
    target="plugins/$dir"

    # If something is already present at the target — pre-populated by a
    # prior fetch, or carried in from a cache — skip the clone. The
    # existing tree is good enough for the downstream CI steps.
    if [ -d "$target/.git" ] || [ -e "$target/composer.json" ]; then
        skipped=$((skipped + 1))
        return 0
    fi

    rm -rf "$target"
    if git clone --depth=1 "$url" "$target" >/dev/null 2>&1; then
        cloned=$((cloned + 1))
    else
        failed=$((failed + 1))
        echo "::warning::Failed to clone $dir (continuing)"
    fi
}

echo "=== Cloning integration extensions ==="

# The publicly distributed ones: named in the tracked list, so their
# progress is worth printing.
while IFS=$'\t' read -r dir url _rest; do
    [ -z "${dir:-}" ] && continue
    case "$dir" in '#'*) continue ;; esac
    if [ -z "${url:-}" ]; then
        echo "::warning::Skipping row with missing URL: '$dir'"
        continue
    fi
    echo "--- $dir ---"
    clone_row "$dir" "$url"
done <"$LIST"

# The rest: masked, so neither the name nor the URL reaches the log.
suites_1=""
suites_2=""
suites_3=""
api_exclude=""
doc_provider=""

if [ -n "$extra_rows" ]; then
    while IFS=$'\t' read -r dir url tier flags; do
        [ -z "${dir:-}" ] && continue
        if [ -z "${url:-}" ]; then
            echo "::warning::Skipping a variable row with no URL"
            continue
        fi
        clone_row "$dir" "$url"

        # Only a directory that is actually on disk gets a suite. PHPUnit
        # answers an unknown --testsuite with "No tests found." and exit 0,
        # so a row that never arrived would otherwise turn into a tier that
        # silently tests nothing and passes.
        if [ -d "plugins/$dir/tests" ]; then
            case "${tier:-3}" in
                1) suites_1="${suites_1:+$suites_1,}$dir" ;;
                2) suites_2="${suites_2:+$suites_2,}$dir" ;;
                *) suites_3="${suites_3:+$suites_3,}$dir" ;;
            esac
        else
            echo "::warning::An integration extension has no tests/ directory after the clone; its suite is not registered"
        fi

        case ",${flags:-}," in
            *,no-api-scan,*) api_exclude="${api_exclude:+$api_exclude }$dir" ;;
        esac
        case ",${flags:-}," in
            *,doc-generators,*) doc_provider="Plugins\\${dir}\\App\\Providers\\${dir}ServiceProvider" ;;
        esac
    done <<EOF
$extra_rows
EOF
fi

echo "=== $cloned cloned, $skipped already present, $failed failed ==="

# The variable being set means this run is expected to test against more
# than the public list. Coming out of it with no suite at all is either a
# misconfigured variable or a total clone failure, and "no tests found" is
# a passing exit code — so say so here instead of going green on nothing.
if [ -n "$extra_rows" ] && [ -z "$suites_1$suites_2$suites_3" ]; then
    echo "::error::INTEGRATION_EXTENSIONS is set, but no extension from it produced a test suite. Check the variable's rows and the clone credentials."
    exit 1
fi

# Nothing below this point applies outside Actions.
[ -n "${GITHUB_ENV:-}" ] || exit 0

{
    echo "EXTRA_SUITES_TIER1=$suites_1"
    echo "EXTRA_SUITES_TIER2=$suites_2"
    echo "EXTRA_SUITES_TIER3=$suites_3"
    echo "API_SCAN_EXCLUDE=$api_exclude"
    echo "DOC_GENERATOR_PROVIDER=$doc_provider"
} >>"$GITHUB_ENV"

# Give each variable row a <testsuite>, so `--testsuite=<name>` resolves
# without phpunit.xml naming an extension this repository does not
# distribute. The tracked file stays hand-written; only the runner's
# throwaway copy is touched.
if [ -n "$suites_1$suites_2$suites_3" ] && [ -f "$PHPUNIT_CONFIG" ]; then
    # The fragment goes through a file, not -v: BSD awk refuses a variable
    # whose value contains a newline, so the one-liner form is not portable.
    fragfile="$(mktemp)"
    for name in $(printf '%s\n%s\n%s\n' "$suites_1" "$suites_2" "$suites_3" | tr ',' '\n' | grep -v '^$'); do
        printf '        <testsuite name="%s">\n            <directory>plugins/%s/tests</directory>\n        </testsuite>\n' "$name" "$name" >>"$fragfile"
    done
    awk -v fragfile="$fragfile" '
        /<\/testsuites>/ && !inserted {
            while ((getline line < fragfile) > 0) { print line }
            close(fragfile)
            inserted = 1
        }
        { print }
    ' "$PHPUNIT_CONFIG" >"$PHPUNIT_CONFIG.tmp" && mv "$PHPUNIT_CONFIG.tmp" "$PHPUNIT_CONFIG"
    rm -f "$fragfile"
    registered=$(printf '%s\n%s\n%s\n' "$suites_1" "$suites_2" "$suites_3" | tr ',' '\n' | grep -vc '^$')
    echo "Registered $registered further integration test suite(s) in $PHPUNIT_CONFIG"
fi
