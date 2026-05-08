# `@alpinejs/csp` Dry-Run Findings

**Date:** 2026-05-09
**Scope:** P4 of the CSP strict-mode pre-work (see `.backlog/csp-strict-mode-readiness.md`).

## What was done

Built two parallel scratch entries with esbuild:

- `csp-shared-baseline.js` — `import Alpine from 'alpinejs'` + the new shared `Alpine.data()` registrations from `resources/src/common/js/alpine-data/index.js`
- `csp-shared.js` — same code, but `import Alpine from '@alpinejs/csp'`

Both bundle the same shared registrations (`accordion`, `toggle`, `tabs`, `modal`, `clipboardCopy`) plus a tiny page-specific `dryRunCounter`.

## Results

### Build cleanliness

Both entries bundle without warnings or errors. The shared `Alpine.data()` factories are syntactically and semantically compatible with both Alpine builds.

```
csp-shared-baseline.js  46.7 KB  (minified)
csp-shared.js           61.5 KB  (minified)
delta                  +14.8 KB  (+31.7%)
```

The size delta is the cost of the CSP build's safe expression evaluator (which ships its own parser instead of using `new Function()`).

### What this proves

1. The dependency `@alpinejs/csp` resolves and bundles cleanly — the package is installed and ready to use.
2. The shared `Alpine.data()` registrations we authored in `resources/src/common/js/alpine-data/index.js` are valid under the strict bundle; no factory uses CSP-build-forbidden constructs (object literals in directives, arrow functions in expressions, etc.).
3. The size cost of the strict bundle is acceptable (about 15 KB minified per entry that ships Alpine).

### What this does NOT prove

- **Runtime correctness of existing pages.** The CSP build runs at runtime; build success does not catch object-literal directive expressions or other runtime-only violations. Confirming that real Blade views work under the strict bundle requires a smoke test (E2E or manual) — that is item P10 in the backlog.
- **Compatibility with all Alpine plugins we use.** `@alpinejs/collapse`, `@alpinejs/persist`, etc. need their own per-plugin verification. None were exercised in this dry-run.

## Known migration breakage points (not exhaustive)

When the eventual full migration runs, each of these patterns must be rewritten:

```html
<!-- BAD (object literal in directive) -->
<div :class="{ 'opacity-50': disabled }">
<button @click="$dispatch('foo', { bar: baz })">

<!-- GOOD -->
<div :class="classes">           <!-- computed property -->
<button @click="emitFoo">        <!-- method on registered data -->
```

The CSP page itself (`resources/views/admin/settings/security/csp.blade.php`) currently uses the BAD pattern at line 66 — a known migration target documented but not addressed in this pre-work pass.

## Cleanup

The contents of `resources/src/_csp-build-dryrun/` are scratch and do not get a Vite entry. They can be deleted at any time without affecting production. Keep them around as a reference until the full migration starts; at that point, this directory and `FINDINGS.md` are deleted in the same commit that flips the production import statements.
