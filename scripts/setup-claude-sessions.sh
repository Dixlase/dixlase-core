#!/bin/bash
# =============================================================================
# setup-claude-sessions.sh
# Dixlase CMS - Parallel Claude Code Development Environment Setup
#
# Sets up Claude Code configuration (CLAUDE.md, .claude/, .mcp.json) for
# individual plugins and themes so they can run independent Claude Code sessions.
#
# Usage:
#   ./scripts/setup-claude-sessions.sh --all              # Core + all plugins/themes (English)
#   ./scripts/setup-claude-sessions.sh --lang ja --all    # Core + all plugins/themes (Japanese)
#   ./scripts/setup-claude-sessions.sh --core             # Core CLAUDE.md only (English)
#   ./scripts/setup-claude-sessions.sh --core --lang ja   # Core CLAUDE.md only (Japanese)
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

# Default language
LANG_CODE="en"

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
# Language / Translation
# =============================================================================

set_lang() {
    case "$LANG_CODE" in
        ja)
            # === Common Headers ===
            L_PLUGIN_SUFFIX="Dixlase CMS プラグイン"
            L_THEME_SUFFIX="Dixlase CMS テーマ"
            L_ARCHITECTURE="アーキテクチャ"
            L_KEY_PATHS="主要パス"
            L_DEV_RULES="開発ルール"
            L_PLUGIN_OVERVIEW="プラグイン概要"
            L_THEME_OVERVIEW="テーマ概要"
            L_CORE_INTEGRATION="コアとの連携"
            L_PHP_STANDARDS="PHP 標準"
            L_TRANSLATION="翻訳"
            L_ROUTE_NAMING="ルート命名規則"
            L_TESTING="テスト"
            L_CODE_FORMAT="コードフォーマット"
            L_MCP_TOOLS="MCP ツール (Laravel Boost)"
            L_GUIDELINES="基本方針"
            L_FRONTEND_STACK="フロントエンドスタック"
            L_BLADE_COMPONENTS="Blade コンポーネント（コア提供）"
            L_ASSET_BUNDLE="アセットバンドル"
            L_PLUGIN_FEATURES="プラグイン提供機能"
            L_THEME_FEATURES="テーマ対応機能"

            # === Meta Labels ===
            L_META_TYPE="種別"
            L_META_CATEGORY="カテゴリ"
            L_META_VERSION="バージョン"
            L_META_NAMESPACE="名前空間"
            L_META_PLUGIN="Dixlase プラグイン"
            L_META_THEME="Dixlase テーマ"

            # === Architecture Descriptions ===
            L_PLUGIN_ARCH="**Dixlase CMS** (Laravel 12) のプラグイン。コアアプリケーションは \`../../\` に配置。"
            L_PLUGIN_ARCH_FULL="**Dixlase CMS** (Laravel 12) のプラグイン。コアアプリケーションはこのプラグインディレクトリから \`../../\` に配置。"
            L_THEME_ARCH="**Dixlase CMS** (Laravel 12) のテーマ。コアアプリケーションは \`../../\` に配置。"
            L_THEME_ARCH_FULL="**Dixlase CMS** (Laravel 12) のテーマ。コアアプリケーションはこのテーマディレクトリから \`../../\` に配置。"

            # === Path Labels ===
            L_CORE_ROOT="コアルート"
            L_CORE_ROOT_DETAIL=" （\`vendor/\`, \`artisan\`, コア \`app/\` を含む）"
            L_THIS_PLUGIN="このプラグイン"
            L_THIS_THEME="このテーマ"
            L_ARTISAN_CMD="Artisan コマンド"
            L_RUN_TESTS="テスト実行"

            # === Minimal Template Rules ===
            L_RULE_CONSTRUCTOR_SHORT="コンストラクタプロパティプロモーション、明示的な戻り値型"
            L_RULE_FORM_REQUEST="バリデーションは Form Request クラスで（インライン不可）"
            L_RULE_PHPDOC="インラインコメントよりPHPDocブロック"
            L_RULE_TRANSLATION="翻訳ファイルは en/ と ja/ の両方を用意する"
            L_RULE_CONFIG="\`env()\` は直接使わず \`config()\` を使用"
            L_RULE_PHPUNIT="テストは PHPUnit で記述（Pest不可）"
            L_RULE_DARK_MODE_SHORT="ダークモード必須（\`dark:\` バリアント）"
            L_RULE_RESPONSIVE_SHORT="レスポンシブ必須（モバイルファースト）"
            L_RULE_NO_LOGIC_SHORT="テーマ内にビジネスロジックを含めない"

            # === Full Template Rules ===
            L_RULE_CONSTRUCTOR="コンストラクタプロパティプロモーションを使用"
            L_RULE_RETURN_TYPE="全メソッドに明示的な戻り値型を宣言"
            L_RULE_PHPDOC_FULL="インラインコメントよりPHPDocブロックを優先"
            L_RULE_ENUM="Enum キーは TitleCase"
            L_RULE_TRANSLATION_FULL="翻訳ファイルは \`en/\` と \`ja/\` の両方を必ず用意する"
            L_RULE_FACTORY="モデルファクトリを使用（手動セットアップ前に既存のstateを確認）"
            L_RULE_PHPUNIT_FULL="PHPUnit でフィーチャーテストを記述（Pest不可）"
            L_RULE_PINT="Pint はフック経由で編集後に自動実行"

            # === Plugin-Specific Rules ===
            L_RULE_NO_CORE_IMPORT="**コア内部を直接インポートしない** — \`App\\Contracts\\*\` インターフェースを使用"
            L_RULE_REGISTER_SP="**全てServiceProviderで登録** — ルート、ビュー、設定、マイグレーション"
            L_RULE_NS_ISOLATION="**名前空間の分離** — 全クラスは"  # + namespace appended
            L_RULE_NS_ISOLATION_SUFFIX="配下"
            L_RULE_SELF_MIGRATION="**マイグレーションは自己完結** — プラグイン独自のテーブルを管理"

            # === Route Naming ===
            L_ROUTE_PATTERN="パターン"
            L_ROUTE_MIDDLEWARE="ミドルウェアグループ"

            # === Theme-Specific Rules ===
            L_RULE_NO_LOGIC="**テーマ内にビジネスロジックを含めない** — 表示のみ、ビュー内で Eloquent クエリ不可"
            L_RULE_VIEW_NS="**ビュー名前空間**: テーマビューは全て \`themes::\`"
            L_RULE_TRANS_NS="**翻訳名前空間**: テーマ翻訳は全て \`themes::\`"
            L_RULE_TABLE_PREFIX="**テーブル接頭辞**: テーマDBテーブルは \`thm_\`"
            L_RULE_DARK_MODE="**ダークモード必須**: 全コンポーネントで \`dark:\` Tailwind バリアントをサポート"
            L_RULE_RESPONSIVE="**レスポンシブ必須**: モバイルファースト、全ブレークポイントでテスト"
            L_RULE_PLUGIN_COMPAT="**プラグイン対応**: プラグインと連携するが、なくても正常に動作すること"

            # === Frontend ===
            L_TAILWIND_DESC="ユーティリティファースト、スペーシングは \`gap-*\`、ダークモードは \`dark:\`"
            L_VITE_DESC="開発は \`npm run dev\`、本番は \`npm run build\`"
            L_BLADE_AVAILABLE="利用可能"
            L_ASSET_DEV="開発時はテーマディレクトリで \`npm run dev\` を実行"
            L_ASSET_BUILD="アセット変更のコミット前に \`npm run build\` を実行"

            # === Test Commands ===
            L_TEST_SPECIFIC="特定テスト"
            L_TEST_RUN="テスト実行"

            # === MCP Descriptions ===
            L_MCP_SEARCH_DOCS_PLUGIN="Laravel エコシステムのドキュメント検索"
            L_MCP_SEARCH_DOCS_THEME="Laravel/Tailwind/Livewire ドキュメント検索"
            L_MCP_TINKER_PLUGIN="PHP コードのデバッグ"
            L_MCP_TINKER_THEME="デバッグ"
            L_MCP_DB_QUERY="読み取り専用データベースクエリ"
            L_MCP_LIST_ARTISAN="Artisan コマンド実行前に利用可能なコマンドを確認"
            ;;

        en|*)
            # === Common Headers ===
            L_PLUGIN_SUFFIX="Dixlase CMS Plugin"
            L_THEME_SUFFIX="Dixlase CMS Theme"
            L_ARCHITECTURE="Architecture"
            L_KEY_PATHS="Key Paths"
            L_DEV_RULES="Development Rules"
            L_PLUGIN_OVERVIEW="Plugin Overview"
            L_THEME_OVERVIEW="Theme Overview"
            L_CORE_INTEGRATION="Core Integration"
            L_PHP_STANDARDS="PHP Standards"
            L_TRANSLATION="Translation"
            L_ROUTE_NAMING="Route Naming"
            L_TESTING="Testing"
            L_CODE_FORMAT="Code Formatting"
            L_MCP_TOOLS="MCP Tools (Laravel Boost)"
            L_GUIDELINES="Guidelines"
            L_FRONTEND_STACK="Frontend Stack"
            L_BLADE_COMPONENTS="Blade Components (Core)"
            L_ASSET_BUNDLE="Asset Bundling"
            L_PLUGIN_FEATURES="Plugin Features"
            L_THEME_FEATURES="Theme Features"

            # === Meta Labels ===
            L_META_TYPE="Type"
            L_META_CATEGORY="Category"
            L_META_VERSION="Version"
            L_META_NAMESPACE="Namespace"
            L_META_PLUGIN="Dixlase Plugin"
            L_META_THEME="Dixlase Theme"

            # === Architecture Descriptions ===
            L_PLUGIN_ARCH="Plugin for **Dixlase CMS** (Laravel 12). The core application is located at \`../../\`."
            L_PLUGIN_ARCH_FULL="Plugin for **Dixlase CMS** (Laravel 12). The core application is located at \`../../\` relative to this plugin directory."
            L_THEME_ARCH="Theme for **Dixlase CMS** (Laravel 12). The core application is located at \`../../\`."
            L_THEME_ARCH_FULL="Theme for **Dixlase CMS** (Laravel 12). The core application is located at \`../../\` relative to this theme directory."

            # === Path Labels ===
            L_CORE_ROOT="Core Root"
            L_CORE_ROOT_DETAIL=" (includes \`vendor/\`, \`artisan\`, core \`app/\`)"
            L_THIS_PLUGIN="This Plugin"
            L_THIS_THEME="This Theme"
            L_ARTISAN_CMD="Artisan Commands"
            L_RUN_TESTS="Run Tests"

            # === Minimal Template Rules ===
            L_RULE_CONSTRUCTOR_SHORT="Constructor property promotion, explicit return types"
            L_RULE_FORM_REQUEST="Validate with Form Request classes (no inline validation)"
            L_RULE_PHPDOC="Prefer PHPDoc blocks over inline comments"
            L_RULE_TRANSLATION="Provide both en/ and ja/ translation files"
            L_RULE_CONFIG="Use \`config()\` instead of \`env()\` directly"
            L_RULE_PHPUNIT="Write tests with PHPUnit (not Pest)"
            L_RULE_DARK_MODE_SHORT="Dark mode required (\`dark:\` variant)"
            L_RULE_RESPONSIVE_SHORT="Responsive required (mobile-first)"
            L_RULE_NO_LOGIC_SHORT="No business logic in themes"

            # === Full Template Rules ===
            L_RULE_CONSTRUCTOR="Use constructor property promotion"
            L_RULE_RETURN_TYPE="Declare explicit return types for all methods"
            L_RULE_PHPDOC_FULL="Prefer PHPDoc blocks over inline comments"
            L_RULE_ENUM="Enum keys should be TitleCase"
            L_RULE_TRANSLATION_FULL="Always provide both \`en/\` and \`ja/\` translation files"
            L_RULE_FACTORY="Use model factories (check existing states before manual setup)"
            L_RULE_PHPUNIT_FULL="Write feature tests with PHPUnit (not Pest)"
            L_RULE_PINT="Pint auto-runs via hook after edits"

            # === Plugin-Specific Rules ===
            L_RULE_NO_CORE_IMPORT="**Do not import core internals directly** — use \`App\\Contracts\\*\` interfaces"
            L_RULE_REGISTER_SP="**Register everything in ServiceProvider** — routes, views, config, migrations"
            L_RULE_NS_ISOLATION="**Namespace isolation** — all classes under"  # + namespace appended
            L_RULE_NS_ISOLATION_SUFFIX=""
            L_RULE_SELF_MIGRATION="**Self-contained migrations** — manage plugin-specific tables"

            # === Route Naming ===
            L_ROUTE_PATTERN="Pattern"
            L_ROUTE_MIDDLEWARE="Middleware groups"

            # === Theme-Specific Rules ===
            L_RULE_NO_LOGIC="**No business logic in themes** — display only, no Eloquent queries in views"
            L_RULE_VIEW_NS="**View namespace**: all theme views use \`themes::\`"
            L_RULE_TRANS_NS="**Translation namespace**: all theme translations use \`themes::\`"
            L_RULE_TABLE_PREFIX="**Table prefix**: theme DB tables use \`thm_\`"
            L_RULE_DARK_MODE="**Dark mode required**: support \`dark:\` Tailwind variant in all components"
            L_RULE_RESPONSIVE="**Responsive required**: mobile-first, test across all breakpoints"
            L_RULE_PLUGIN_COMPAT="**Plugin compatible**: integrate with plugins but function without them"

            # === Frontend ===
            L_TAILWIND_DESC="Utility-first, spacing with \`gap-*\`, dark mode with \`dark:\`"
            L_VITE_DESC="Dev: \`npm run dev\`, Prod: \`npm run build\`"
            L_BLADE_AVAILABLE="Available"
            L_ASSET_DEV="Run \`npm run dev\` in theme directory during development"
            L_ASSET_BUILD="Run \`npm run build\` before committing asset changes"

            # === Test Commands ===
            L_TEST_SPECIFIC="Specific test"
            L_TEST_RUN="Run tests"

            # === MCP Descriptions ===
            L_MCP_SEARCH_DOCS_PLUGIN="Search Laravel ecosystem documentation"
            L_MCP_SEARCH_DOCS_THEME="Search Laravel/Tailwind/Livewire documentation"
            L_MCP_TINKER_PLUGIN="Debug PHP code"
            L_MCP_TINKER_THEME="Debug"
            L_MCP_DB_QUERY="Read-only database queries"
            L_MCP_LIST_ARTISAN="Check available commands before running Artisan"
            ;;
    esac
}

# =============================================================================
# Shared Rules Injection
# =============================================================================

get_shared_rules() {
    local rules_file="$CORE_ROOT/scripts/claude-shared-rules-${LANG_CODE}.md"
    if [ -f "$rules_file" ]; then
        echo ""
        cat "$rules_file"
    fi
}

# =============================================================================
# Core Guidelines Setup
# =============================================================================

setup_core_guidelines() {
    local src="$CORE_ROOT/.ai/guidelines-${LANG_CODE}"
    local dest="$CORE_ROOT/.ai/guidelines"

    if [ ! -d "$src" ]; then
        error "Guidelines source not found: $src"
        return 1
    fi

    # Clean destination and copy language-specific guidelines
    if [ -d "$dest" ]; then
        rm -f "$dest"/*.blade.php
        find "$dest" -mindepth 1 -type f -name '*.blade.php' -delete
        find "$dest" -mindepth 1 -type d -empty -delete
    fi
    cp -R "$src/." "$dest"

    # Regenerate CLAUDE.md via boost:install
    docker exec -i dixlase-laravel.test-1 php artisan boost:install --no-interaction

    ok "Core CLAUDE.md generated ($LANG_CODE)"
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
# $dir_name - $L_PLUGIN_SUFFIX

## $L_ARCHITECTURE

$L_PLUGIN_ARCH

### $L_KEY_PATHS
- **$L_CORE_ROOT**: \`../../\`
- **Artisan**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`
- **$L_RUN_TESTS**: \`docker exec -i dixlase-laravel.test-1 php artisan test plugins/$dir_name/tests/\`

## $L_DEV_RULES

- PHP 8.3, Laravel 12, Livewire 4
- $L_RULE_CONSTRUCTOR_SHORT
- $L_RULE_FORM_REQUEST
- $L_RULE_PHPDOC
- $L_RULE_TRANSLATION
- $L_RULE_CONFIG
- $L_RULE_PHPUNIT
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
# $name - $L_PLUGIN_SUFFIX

> **$L_META_TYPE**: $L_META_PLUGIN | **$L_META_CATEGORY**: $category | **$L_META_VERSION**: $version
> **$L_META_NAMESPACE**: \`$namespace\`

## $L_PLUGIN_OVERVIEW

$desc_en

## $L_ARCHITECTURE

$L_PLUGIN_ARCH_FULL

### $L_KEY_PATHS
- **$L_CORE_ROOT**: \`../../\`$L_CORE_ROOT_DETAIL
- **$L_THIS_PLUGIN**: \`plugins/$dir_name/\`
- **$L_ARTISAN_CMD**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`

### $L_PLUGIN_FEATURES
$provides_text

## $L_DEV_RULES

### $L_CORE_INTEGRATION
- $L_RULE_NO_CORE_IMPORT
- $L_RULE_REGISTER_SP
- $L_RULE_NS_ISOLATION \`$namespace\*\` $L_RULE_NS_ISOLATION_SUFFIX
- $L_RULE_SELF_MIGRATION

### $L_PHP_STANDARDS
- PHP 8.3, Laravel 12, Livewire 4
- $L_RULE_CONSTRUCTOR
- $L_RULE_RETURN_TYPE
- $L_RULE_FORM_REQUEST
- $L_RULE_PHPDOC_FULL
- $L_RULE_ENUM
- $L_RULE_CONFIG

### $L_TRANSLATION
- $L_RULE_TRANSLATION_FULL

### $L_ROUTE_NAMING
- $L_ROUTE_PATTERN: \`plugin.{slug}.{resource}.{action}\`
- $L_ROUTE_MIDDLEWARE: \`plugin\`, \`plugin.web\`, \`plugin.admin\`

### $L_TESTING
- $L_RULE_PHPUNIT_FULL
- $L_RULE_FACTORY
- $L_TEST_RUN: \`docker exec -i dixlase-laravel.test-1 php artisan test plugins/$dir_name/tests/\`
- $L_TEST_SPECIFIC: \`docker exec -i dixlase-laravel.test-1 php artisan test --filter=testMethodName\`

### $L_CODE_FORMAT
- $L_RULE_PINT
$(get_shared_rules)

## $L_MCP_TOOLS
- \`search-docs\`: $L_MCP_SEARCH_DOCS_PLUGIN
- \`tinker\`: $L_MCP_TINKER_PLUGIN
- \`database-query\`: $L_MCP_DB_QUERY
- \`list-artisan-commands\`: $L_MCP_LIST_ARTISAN
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
# $dir_name - $L_THEME_SUFFIX

## $L_ARCHITECTURE

$L_THEME_ARCH

### $L_KEY_PATHS
- **$L_CORE_ROOT**: \`../../\`
- **Artisan**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`

## $L_DEV_RULES

- PHP 8.3, Laravel 12, Livewire 4, Tailwind CSS 3, Alpine.js 3
- $L_RULE_DARK_MODE_SHORT
- $L_RULE_RESPONSIVE_SHORT
- $L_RULE_NO_LOGIC_SHORT
- $L_RULE_TRANSLATION
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
# $name - $L_THEME_SUFFIX

> **$L_META_TYPE**: $L_META_THEME | **$L_META_VERSION**: $version
> **$L_META_NAMESPACE**: \`$namespace\`

## $L_THEME_OVERVIEW

$desc_en

## $L_ARCHITECTURE

$L_THEME_ARCH_FULL

### $L_KEY_PATHS
- **$L_CORE_ROOT**: \`../../\`$L_CORE_ROOT_DETAIL
- **$L_THIS_THEME**: \`themes/$dir_name/\`
- **$L_ARTISAN_CMD**: \`docker exec -i dixlase-laravel.test-1 php artisan <command>\`

### $L_THEME_FEATURES
$supports_text

## $L_DEV_RULES

### $L_GUIDELINES
- $L_RULE_NO_LOGIC
- $L_RULE_VIEW_NS
- $L_RULE_TRANS_NS
- $L_RULE_TABLE_PREFIX
- $L_RULE_DARK_MODE
- $L_RULE_RESPONSIVE
- $L_RULE_PLUGIN_COMPAT

### $L_PHP_STANDARDS
- PHP 8.3, Laravel 12, Livewire 4
- $L_RULE_CONSTRUCTOR
- $L_RULE_RETURN_TYPE
- $L_RULE_PHPDOC_FULL
- $L_RULE_CONFIG

### $L_FRONTEND_STACK
- **Tailwind CSS 3**: $L_TAILWIND_DESC
- **Alpine.js 3**: \`x-data\`, \`@click\`, \`x-show\`, \`x-transition\`, \`x-cloak\`
- **Vite**: $L_VITE_DESC

### $L_BLADE_COMPONENTS
$L_BLADE_AVAILABLE: \`x-ui-maintenance-banner\`, \`x-ui-admin-bar\`, \`x-form-text\`,
\`x-form-textarea\`, \`x-front.button\`, \`components.media-picker\`, \`components.save\`

### $L_TRANSLATION
- $L_RULE_TRANSLATION_FULL

### $L_ASSET_BUNDLE
- $L_ASSET_DEV
- $L_ASSET_BUILD

### $L_TESTING
- $L_TEST_RUN: \`docker exec -i dixlase-laravel.test-1 php artisan test\`

### $L_CODE_FORMAT
- $L_RULE_PINT
$(get_shared_rules)

## $L_MCP_TOOLS
- \`search-docs\`: $L_MCP_SEARCH_DOCS_THEME
- \`tinker\`: $L_MCP_TINKER_THEME
- \`database-query\`: $L_MCP_DB_QUERY
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

show_usage() {
    echo "Usage: $0 <command> [options]"
    echo ""
    echo "Commands:"
    echo "  --all                  Set up core, all plugins and themes"
    echo "  --core                 Set up core CLAUDE.md only"
    echo "  --plugin <Name>        Set up a specific plugin"
    echo "  --theme <Name>         Set up a specific theme"
    echo "  --clean                Remove all generated files"
    echo "  --clean-plugin <Name>  Clean a specific plugin"
    echo "  --clean-theme <Name>   Clean a specific theme"
    echo ""
    echo "Options:"
    echo "  --lang <en|ja>         Language for generated files (default: en)"
    exit 1
}

main() {
    check_dependencies

    # Pass 1: Extract --lang option from arguments
    local args=()
    while [ $# -gt 0 ]; do
        case "$1" in
            --lang)
                if [ -z "${2:-}" ]; then
                    error "Language code required. Usage: --lang <en|ja>"
                    exit 1
                fi
                case "$2" in
                    en|ja) LANG_CODE="$2" ;;
                    *)
                        error "Unsupported language: $2 (supported: en, ja)"
                        exit 1
                        ;;
                esac
                shift 2
                ;;
            *)
                args+=("$1")
                shift
                ;;
        esac
    done

    # Initialize translation variables
    set_lang

    echo ""
    echo "========================================"
    echo "  Dixlase Claude Code Session Setup"
    echo "========================================"
    echo ""

    info "Language: $LANG_CODE"

    # Pass 2: Process commands
    case "${args[0]:-}" in
        --all)
            setup_core_guidelines
            local count=0
            for dir in "$CORE_ROOT"/plugins/*/; do
                [ -d "$dir" ] && setup_plugin "$dir" && ((count++))
            done
            for dir in "$CORE_ROOT"/themes/*/; do
                [ -d "$dir" ] && setup_theme "$dir" && ((count++))
            done
            echo ""
            ok "Setup complete: core + $count targets configured"
            ;;
        --core)
            setup_core_guidelines
            ;;
        --plugin)
            if [ -z "${args[1]:-}" ]; then
                error "Plugin name required. Usage: $0 --plugin PluginName"
                exit 1
            fi
            setup_plugin "$CORE_ROOT/plugins/${args[1]}"
            ;;
        --theme)
            if [ -z "${args[1]:-}" ]; then
                error "Theme name required. Usage: $0 --theme ThemeName"
                exit 1
            fi
            setup_theme "$CORE_ROOT/themes/${args[1]}"
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
            if [ -z "${args[1]:-}" ]; then
                error "Plugin name required."
                exit 1
            fi
            clean_target "$CORE_ROOT/plugins/${args[1]}"
            ;;
        --clean-theme)
            if [ -z "${args[1]:-}" ]; then
                error "Theme name required."
                exit 1
            fi
            clean_target "$CORE_ROOT/themes/${args[1]}"
            ;;
        *)
            show_usage
            ;;
    esac
}

main "$@"
