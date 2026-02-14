#!/bin/bash
# =============================================================================
# setup-claude-sessions.sh
# Dixlase CMS - Parallel Claude Code Development Environment Setup
#
# Sets up Claude Code configuration (CLAUDE.md, .claude/, .mcp.json) for
# individual plugins and themes so they can run independent Claude Code sessions.
#
# Usage:
#   ./scripts/setup-claude-sessions.sh --all
#   ./scripts/setup-claude-sessions.sh --plugin DixlaseBlog
#   ./scripts/setup-claude-sessions.sh --theme DixlaseDefaultTheme
#   ./scripts/setup-claude-sessions.sh --clean            # Remove all generated files
#   ./scripts/setup-claude-sessions.sh --clean-plugin DixlaseBlog
# =============================================================================

set -euo pipefail

# Resolve core root (parent of scripts/)
CORE_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CORE_CLAUDE_DIR="$CORE_ROOT/.claude"
CORE_HOOKS_DIR="$CORE_CLAUDE_DIR/hooks"
CORE_MCP="$CORE_ROOT/.mcp.json"
CORE_SETTINGS="$CORE_CLAUDE_DIR/settings.json"
SHARED_RULES="$CORE_ROOT/scripts/claude-shared-rules.md"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# =============================================================================
# Utility Functions
# =============================================================================

info()  { echo -e "${BLUE}[INFO]${NC} $*"; }
ok()    { echo -e "${GREEN}[OK]${NC}   $*"; }
warn()  { echo -e "${YELLOW}[WARN]${NC} $*"; }
error() { echo -e "${RED}[ERR]${NC}  $*"; }

check_dependencies() {
    if ! command -v jq &>/dev/null; then
        error "jq is required but not installed."
        echo "  Install with: brew install jq (macOS) or apt install jq (Linux)"
        exit 1
    fi
}

# =============================================================================
# Shared Rules Injection
# =============================================================================

get_shared_rules() {
    if [ -f "$SHARED_RULES" ]; then
        echo ""
        cat "$SHARED_RULES"
    fi
}

# =============================================================================
# .gitignore Management
# =============================================================================

update_gitignore() {
    local dir="$1"
    local gitignore="$dir/.gitignore"
    local marker="# Claude Code / AI assistant configs"

    if [ -f "$gitignore" ] && grep -qF "$marker" "$gitignore"; then
        return 0
    fi

    {
        echo ""
        echo "$marker"
        echo "CLAUDE.md"
        echo "/.claude/"
        echo "/.mcp.json"
    } >> "$gitignore"
}

# =============================================================================
# Hook: post-edit-pint.sh (modified for plugin/theme context)
# =============================================================================

create_pint_hook() {
    local hook_path="$1"
    cat > "$hook_path" << 'HOOK_EOF'
#!/bin/bash
# Post-edit hook: Run Laravel Pint on edited PHP files
# Plugin/Theme variant: walks up to find core's vendor/bin/pint

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

# Skip if no file path
if [ -z "$FILE_PATH" ]; then
  exit 0
fi

# Only format PHP files
if [[ "$FILE_PATH" != *.php ]]; then
  exit 0
fi

# Skip vendor and node_modules
if [[ "$FILE_PATH" == *vendor/* ]] || [[ "$FILE_PATH" == *node_modules/* ]]; then
  exit 0
fi

# Find Pint by walking up from CLAUDE_PROJECT_DIR
SEARCH_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
PINT=""
while [ "$SEARCH_DIR" != "/" ]; do
  if [ -x "$SEARCH_DIR/vendor/bin/pint" ]; then
    PINT="$SEARCH_DIR/vendor/bin/pint"
    break
  fi
  SEARCH_DIR=$(dirname "$SEARCH_DIR")
done

# Check if Pint was found
if [ -z "$PINT" ]; then
  exit 0
fi

# Run Pint on the specific file
"$PINT" "$FILE_PATH" --quiet 2>/dev/null

exit 0
HOOK_EOF
    chmod +x "$hook_path"
}

# =============================================================================
# Create .claude/ structure with hooks
# =============================================================================

setup_claude_dir() {
    local target_dir="$1"

    mkdir -p "$target_dir/.claude/hooks"

    # Copy settings.json (identical to core)
    if [ -f "$CORE_SETTINGS" ]; then
        cp "$CORE_SETTINGS" "$target_dir/.claude/settings.json"
    else
        warn "Core .claude/settings.json not found, skipping hooks config"
    fi

    # Create modified pint hook
    create_pint_hook "$target_dir/.claude/hooks/post-edit-pint.sh"

    # Symlink other hooks to core (relative paths)
    # From plugins/Name/.claude/hooks/ or themes/Name/.claude/hooks/
    # -> 4 levels up to core root -> .claude/hooks/
    local rel_core="../../../../.claude/hooks"

    for hook in block-dangerous-commands.sh pre-commit-security.sh; do
        if [ -f "$CORE_HOOKS_DIR/$hook" ]; then
            ln -sf "$rel_core/$hook" "$target_dir/.claude/hooks/$hook"
        else
            warn "Core hook $hook not found, skipping symlink"
        fi
    done
}

# =============================================================================
# Copy .mcp.json
# =============================================================================

setup_mcp() {
    local target_dir="$1"

    if [ -f "$CORE_MCP" ]; then
        cp "$CORE_MCP" "$target_dir/.mcp.json"
    else
        warn ".mcp.json not found in core, skipping"
    fi
}

# =============================================================================
# Plugin CLAUDE.md Generation
# =============================================================================

generate_plugin_claude_md() {
    local plugin_dir="$1"
    local plugin_json="$plugin_dir/plugin.json"
    local dir_name
    dir_name=$(basename "$plugin_dir")

    if [ ! -f "$plugin_json" ]; then
        warn "No plugin.json found in $dir_name, generating minimal CLAUDE.md"
        cat > "$plugin_dir/CLAUDE.md" << EOF
# $dir_name - Dixlase CMS Plugin

## Architecture

This is a plugin for **Dixlase CMS** (Laravel 12). The core application lives at \`../../\`.

### Key Paths
- **Core root**: \`../../\`
- **Artisan**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`
- **Tests**: \`docker exec -i dixlase-laravel.test-1 php artisan test plugins/$dir_name/tests/\`

## Development Rules

- PHP 8.3, Laravel 12, Livewire 4
- Constructor property promotion, explicit return types
- Form Request classes for validation
- PHPDoc blocks over inline comments
- Bilingual translations (en/ and ja/)
- Never use \`env()\` directly — use \`config()\`
- Write PHPUnit feature tests (not Pest)
$(get_shared_rules)
EOF
        return
    fi

    # Extract metadata from plugin.json (handle schema variations)
    local name version category namespace desc_en provides_text

    name=$(jq -r '.name // ""' "$plugin_json")
    version=$(jq -r '.version // "1.0.0"' "$plugin_json")
    category=$(jq -r '.category // "general"' "$plugin_json")

    # Namespace: try .namespace, then first key of .autoload.psr-4, then construct from dir name
    namespace=$(jq -r '
        .namespace //
        ((.autoload // {})["psr-4"] // {} | keys | first // null) //
        null
    ' "$plugin_json")
    if [ "$namespace" = "null" ] || [ -z "$namespace" ]; then
        namespace="Plugins\\\\$dir_name"
    fi
    # Clean trailing backslash from psr-4 keys
    namespace="${namespace%\\}"
    namespace="${namespace%\\\\}"

    # Description: handle both string and {en, ja} object
    desc_en=$(jq -r '
        if (.description | type) == "object" then
            .description.en // .description.ja // "No description"
        else
            .description // "No description"
        end
    ' "$plugin_json")

    # Provides: extract true values
    provides_text=$(jq -r '
        (.provides // {}) | to_entries
        | map(select(.value == true))
        | map("- " + .key)
        | join("\n")
    ' "$plugin_json" 2>/dev/null || echo "")
    if [ -z "$provides_text" ]; then
        provides_text="- (see plugin.json for details)"
    fi

    cat > "$plugin_dir/CLAUDE.md" << CLAUDE_EOF
# $name - Dixlase CMS Plugin

> **Type**: Dixlase Plugin | **Category**: $category | **Version**: $version
> **Namespace**: \`$namespace\`

## What This Plugin Does

$desc_en

## Architecture

This is a plugin for **Dixlase CMS** (Laravel 12). The core application lives at \`../../\` relative to this plugin directory.

### Key Paths
- **Core root**: \`../../\` (contains \`vendor/\`, \`artisan\`, core \`app/\`)
- **This plugin**: \`plugins/$dir_name/\`
- **Artisan commands**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`

### Plugin Provides
$provides_text

## Development Rules

### Core Interaction
- **Never import core internals directly** — use \`App\Contracts\*\` interfaces
- **Register everything via ServiceProvider** — routes, views, config, migrations
- **Namespace isolation** — all classes under \`$namespace\*\`
- **Self-contained migrations** — this plugin manages its own tables

### PHP Standards
- PHP 8.3, Laravel 12, Livewire 4
- Always use constructor property promotion
- Always use explicit return types on all methods
- Use Form Request classes for validation (never inline)
- Prefer PHPDoc blocks over inline comments
- Enum keys in TitleCase
- Use \`config()\` never \`env()\` directly

### Translations
- Always provide both \`en/\` and \`ja/\` lang files

### Route Naming
- Pattern: \`plugin.{slug}.{resource}.{action}\`
- Use \`plugin\`, \`plugin.web\`, \`plugin.admin\` middleware groups

### Testing
- Write PHPUnit feature tests (not Pest)
- Use model factories (check for existing states before manual setup)
- Run tests: \`docker exec -i dixlase-laravel.test-1 php artisan test plugins/$dir_name/tests/\`
- Run specific test: \`docker exec -i dixlase-laravel.test-1 php artisan test --filter=testMethodName\`

### Code Formatting
- Pint runs automatically after edits via hooks
$(get_shared_rules)

## MCP Tools (Laravel Boost)
- Use \`search-docs\` for Laravel ecosystem documentation
- Use \`tinker\` for debugging PHP code
- Use \`database-query\` for read-only database queries
- Use \`list-artisan-commands\` before running artisan commands
CLAUDE_EOF
}

# =============================================================================
# Theme CLAUDE.md Generation
# =============================================================================

generate_theme_claude_md() {
    local theme_dir="$1"
    local theme_json="$theme_dir/theme.json"
    local dir_name
    dir_name=$(basename "$theme_dir")

    if [ ! -f "$theme_json" ]; then
        warn "No theme.json found in $dir_name, generating minimal CLAUDE.md"
        cat > "$theme_dir/CLAUDE.md" << EOF
# $dir_name - Dixlase CMS Theme

## Architecture

This is a theme for **Dixlase CMS** (Laravel 12). The core application lives at \`../../\`.

### Key Paths
- **Core root**: \`../../\`
- **Artisan**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`

## Development Rules

- PHP 8.3, Laravel 12, Livewire 4, Tailwind CSS 3, Alpine.js 3
- Dark mode required (\`dark:\` variants)
- Responsive required (mobile-first)
- No business logic in themes
- Bilingual translations (en/ and ja/)
$(get_shared_rules)
EOF
        return
    fi

    local name version namespace desc_en supports_text

    name=$(jq -r '.name // ""' "$theme_json")
    version=$(jq -r '.version // "1.0.0"' "$theme_json")

    namespace=$(jq -r '.namespace // null' "$theme_json")
    if [ "$namespace" = "null" ] || [ -z "$namespace" ]; then
        namespace="Themes\\\\$dir_name"
    fi

    desc_en=$(jq -r '
        if (.description | type) == "object" then
            .description.en // .description.ja // "No description"
        else
            .description // "No description"
        end
    ' "$theme_json")

    supports_text=$(jq -r '
        (.supports // {}) | to_entries
        | map(select(.value == true))
        | map("- " + .key)
        | join("\n")
    ' "$theme_json" 2>/dev/null || echo "")
    if [ -z "$supports_text" ]; then
        supports_text="- (see theme.json for details)"
    fi

    cat > "$theme_dir/CLAUDE.md" << CLAUDE_EOF
# $name - Dixlase CMS Theme

> **Type**: Dixlase Theme | **Version**: $version
> **Namespace**: \`$namespace\`

## What This Theme Does

$desc_en

## Architecture

This is a theme for **Dixlase CMS** (Laravel 12). The core application lives at \`../../\` relative to this theme directory.

### Key Paths
- **Core root**: \`../../\` (contains \`vendor/\`, \`artisan\`, core \`app/\`)
- **This theme**: \`themes/$dir_name/\`
- **Artisan commands**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`

### Theme Supports
$supports_text

## Development Rules

### Core Principles
- **No business logic in themes** — presentation only, no Eloquent queries in views
- **View namespace**: \`themes::\` for all theme views
- **Translation namespace**: \`themes::\` for all translations
- **Table prefix**: \`thm_\` for theme database tables
- **Dark mode required**: All components must support \`dark:\` Tailwind variants
- **Responsive required**: Mobile-first, test all breakpoints
- **Plugin-aware**: Integrate with plugins but gracefully degrade without them

### PHP Standards
- PHP 8.3, Laravel 12, Livewire 4
- Always use constructor property promotion
- Always use explicit return types
- Prefer PHPDoc blocks over inline comments
- Use \`config()\` never \`env()\` directly

### Frontend Stack
- **Tailwind CSS 3**: Utility-first, \`gap-*\` for spacing, \`dark:\` for dark mode
- **Alpine.js 3**: \`x-data\`, \`@click\`, \`x-show\`, \`x-transition\`, \`x-cloak\`
- **Vite**: \`npm run dev\` for development, \`npm run build\` for production

### Blade Components (Core)
Available: \`x-ui-maintenance-banner\`, \`x-ui-admin-bar\`, \`x-form-text\`,
\`x-form-textarea\`, \`x-front.button\`, \`components.media-picker\`, \`components.save\`

### Translations
- Always provide both \`en/\` and \`ja/\` lang files

### Asset Bundling
- Run \`npm run dev\` in this theme directory for development
- Run \`npm run build\` before committing asset changes

### Testing
- Run tests: \`docker exec -i dixlase-laravel.test-1 php artisan test\`

### Code Formatting
- Pint runs automatically after edits via hooks
$(get_shared_rules)

## MCP Tools (Laravel Boost)
- Use \`search-docs\` for Laravel/Tailwind/Livewire documentation
- Use \`tinker\` for debugging
- Use \`database-query\` for read-only database queries
CLAUDE_EOF
}

# =============================================================================
# Setup Plugin
# =============================================================================

setup_plugin() {
    local plugin_dir="$1"
    local plugin_name
    plugin_name=$(basename "$plugin_dir")

    if [ ! -d "$plugin_dir" ]; then
        error "Plugin directory not found: $plugin_dir"
        return 1
    fi

    info "Setting up plugin: $plugin_name"

    generate_plugin_claude_md "$plugin_dir"
    setup_claude_dir "$plugin_dir"
    setup_mcp "$plugin_dir"
    update_gitignore "$plugin_dir"

    ok "$plugin_name"
}

# =============================================================================
# Setup Theme
# =============================================================================

setup_theme() {
    local theme_dir="$1"
    local theme_name
    theme_name=$(basename "$theme_dir")

    if [ ! -d "$theme_dir" ]; then
        error "Theme directory not found: $theme_dir"
        return 1
    fi

    info "Setting up theme: $theme_name"

    generate_theme_claude_md "$theme_dir"
    setup_claude_dir "$theme_dir"
    setup_mcp "$theme_dir"
    update_gitignore "$theme_dir"

    ok "$theme_name"
}

# =============================================================================
# Clean Generated Files
# =============================================================================

clean_target() {
    local target_dir="$1"
    local name
    name=$(basename "$target_dir")

    if [ ! -d "$target_dir" ]; then
        warn "Directory not found: $target_dir"
        return
    fi

    info "Cleaning: $name"

    rm -f "$target_dir/CLAUDE.md"
    rm -f "$target_dir/.mcp.json"
    rm -rf "$target_dir/.claude"

    ok "Cleaned $name"
}

# =============================================================================
# Main
# =============================================================================

main() {
    check_dependencies

    echo ""
    echo "========================================"
    echo "  Dixlase Claude Code Session Setup"
    echo "========================================"
    echo ""

    case "${1:-}" in
        --all)
            local count=0
            for dir in "$CORE_ROOT"/plugins/*/; do
                [ -d "$dir" ] && setup_plugin "$dir" && ((count++))
            done
            for dir in "$CORE_ROOT"/themes/*/; do
                [ -d "$dir" ] && setup_theme "$dir" && ((count++))
            done
            echo ""
            ok "Setup complete: $count targets configured"
            ;;
        --plugin)
            if [ -z "${2:-}" ]; then
                error "Plugin name required. Usage: $0 --plugin PluginName"
                exit 1
            fi
            setup_plugin "$CORE_ROOT/plugins/$2"
            ;;
        --theme)
            if [ -z "${2:-}" ]; then
                error "Theme name required. Usage: $0 --theme ThemeName"
                exit 1
            fi
            setup_theme "$CORE_ROOT/themes/$2"
            ;;
        --clean)
            for dir in "$CORE_ROOT"/plugins/*/; do
                [ -d "$dir" ] && clean_target "$dir"
            done
            for dir in "$CORE_ROOT"/themes/*/; do
                [ -d "$dir" ] && clean_target "$dir"
            done
            echo ""
            ok "All generated files removed"
            ;;
        --clean-plugin)
            if [ -z "${2:-}" ]; then
                error "Plugin name required."
                exit 1
            fi
            clean_target "$CORE_ROOT/plugins/$2"
            ;;
        --clean-theme)
            if [ -z "${2:-}" ]; then
                error "Theme name required."
                exit 1
            fi
            clean_target "$CORE_ROOT/themes/$2"
            ;;
        *)
            echo "Usage: $0 <command> [name]"
            echo ""
            echo "Commands:"
            echo "  --all                  Set up all plugins and themes"
            echo "  --plugin <Name>        Set up a specific plugin"
            echo "  --theme <Name>         Set up a specific theme"
            echo "  --clean                Remove all generated files"
            echo "  --clean-plugin <Name>  Clean a specific plugin"
            echo "  --clean-theme <Name>   Clean a specific theme"
            exit 1
            ;;
    esac
}

main "$@"
