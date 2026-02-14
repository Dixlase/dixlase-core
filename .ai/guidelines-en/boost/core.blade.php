# Laravel Boost
- Laravel Boost is an MCP server with powerful tools specifically for this application. Use it actively

## Artisan
- When running Artisan commands, check available parameters with the `list-artisan-commands` tool

## URL
- When sharing project URLs with the user, verify the correct scheme, domain/IP, and port with the `get-absolute-url` tool

## Tinker / Debugging
- Use the `tinker` tool for debugging PHP code and directly querying Eloquent models
- Use the `database-query` tool when you only need to read from the database
- Check table structure with the `database-schema` tool before creating migrations or models

@if (config('boost.browser_logs', true) !== false || config('boost.browser_logs_watcher', true) !== false)
## Reading Browser Logs (`browser-logs` tool)
- Use the `browser-logs` tool to read browser logs, errors, and exceptions
- Only recent browser logs are useful — ignore old logs
@endif

## Documentation Search (Important)
- Boost has a powerful `search-docs` tool — use it before other approaches when working with Laravel ecosystem packages. This tool automatically sends installed packages and versions to the Boost API and returns only version-specific documentation. Pass an array of package names when you need documentation for specific packages
- Search documentation before making code changes to verify the correct approach
- Use multiple broad, simple topic-based queries at once. Example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results are returned first
- Do not include package names in queries (package information is already shared). Example: use `test resource table`, not `filament 4 test resource table`

### Search Syntax
1. Word search with auto-stemming - query=authentication - also finds 'authenticate' and 'auth'
2. Multiple words (AND logic) - query=rate limit - results containing "rate" AND "limit"
3. Quoted phrases (exact match) - query="infinite scroll" - adjacent words in this order
4. Mixed queries - query=middleware "rate limit" - "middleware" AND exact match "rate limit"
5. Multiple queries - queries=["authentication", "middleware"] - either term
