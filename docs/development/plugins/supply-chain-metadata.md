# Supply-Chain Metadata for Plugin and Theme Authors

> **Required:** every plugin and theme distributed for Dixlase must declare
> `author_id` and `authority_key_id` in its `plugin.json` / `theme.json`.
> Each missing field deducts **-3** from the health score and disables the
> hijacked-update detection flow.

## Why this matters

A signed extension can be silently replaced by an upstream repo that swaps
the publisher's identity. The version-history layer (see
[Supply-Chain Defense Data Layer](../supply-chain.md)) compares each update's
`author_id` and `signing_key_id` against the previously installed values and
raises a `signing_key_changed` / `author_id_changed` flag when they differ —
that is the signal that surfaces the hijack to an operator.

If your manifest lacks these fields, the comparison can never trigger,
because there is no prior owner to compare against. The health scorer
flags this as a `-3` deduction per missing field (`missing_author_id`,
`missing_authority_key_id`).

## Required fields

### `author_id`

A short, stable identifier for the publishing entity. Use the same value
across every release of every extension you ship. The convention is the
kebab-case slug of your organisation:

```json
{
    "author": "exc-D inc.",
    "author_id": "exc-d-inc"
}
```

- **Stability:** changing this value across an update will trigger an
  `author_id_changed` flag on every install — only use it for a deliberate
  ownership transfer.
- **Scope:** plugin / theme. Core extensions and third-party extensions
  must each declare it.

### `authority_key_id`

The identifier of the public-key entry that signs your releases on the
authority server (`keys.dixlase.net` or your own bundle). Verifiers use it
to look up the key without round-tripping through the file contents:

```json
{
    "authority_key_id": "dixlase-authority-2026"
}
```

- **Format:** lowercase kebab-case with a year segment is conventional
  (e.g. `dixlase-authority-2026`). The year segment makes annual rotation
  obvious.
- **Rotation:** when you rotate the signing key, publish the new public key
  under a new `authority_key_id` and update each in-tree extension's
  `plugin.json` / `theme.json` in the next release. The
  `signing_key_changed` flag will fire on update, which is the **expected
  signal** for an intentional rotation.

## Example `plugin.json` (minimum supply-chain fields)

```json
{
    "name": "Dixlase Pages",
    "slug": "dixlase-pages",
    "version": "0.1.0",
    "author": "exc-D inc.",
    "author_id": "exc-d-inc",
    "authority_key_id": "dixlase-authority-2026",
    "license": "GPL-3.0-or-later",
    "requires": {
        "dixlase": "^0.1.0"
    }
}
```

The same fields apply to `theme.json` — themes are equally exposed to
supply-chain attacks and follow the same scoring rules.

## Verifying your declaration

After installing your extension into a Dixlase environment, run the plugin
audit and check the health score:

```bash
docker exec dixlase-php php artisan dls:plugin:audit --calculate-health
```

The output lists missing fields as issues. The
`PluginHealthScorer::evaluateSupplyChainMetadata()` method is the
authoritative implementation.

If you are running against an environment seeded before v0.1.0 introduced
these columns, populate the database from the on-disk manifests with:

```bash
docker exec dixlase-php php artisan dls:plugin:backfill-supply-chain
```

This is idempotent and only writes columns that are currently `null`.

## See also

- [Supply-Chain Defense Data Layer](../supply-chain.md) — schema and the
  slug-keyed retention policy frozen for `^0.1`.
- [Plugin Health Scoring Rules](health-scoring-rules.md) — full deduction
  table including the `missing_author_id` / `missing_authority_key_id`
  entries.
- [Public Identifier Naming Conventions](../naming.md) — naming format
  rules that apply to `authority_key_id`.
