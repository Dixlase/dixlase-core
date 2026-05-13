# Manifest Compatibility Fields

This page documents the fields in `plugin.json` / `theme.json` that declare
**which Core versions** a plugin or theme is built for. The fields are
read by the plugin scanner and surfaced in the admin UI; the future Dixlase
marketplace will use them for compatibility filtering when paid and
sandboxed extensions are listed.

The fields are part of the manifest contract frozen for `^0.1`. See
[`PLUGIN-API.md`](../../../PLUGIN-API.md) for the stability pledge.

## `requires.dixlase` (required)

A [Composer-style version constraint](https://getcomposer.org/doc/articles/versions.md)
that declares the **minimum Core API contract** the extension was built
against. This is the hard floor: if the host Core does not satisfy the
constraint, the extension is refused at load time.

```json
{
    "requires": {
        "dixlase": "^0.1.0"
    }
}
```

Guidance:

- Use a **caret constraint** (`^x.y`) anchored at the lowest minor Core
  version your extension actually exercises. `^0.1.0` means "any v0.1.x
  release". Once the project moves past `^0.1`, breaking API changes
  require a new major version per the [Stability Pledge](../../../PLUGIN-API.md#stability-pledge).
- Do **not** pin to an exact patch version (`"=0.1.3"`); patch releases
  ship security and bug fixes that you almost certainly want.
- Other top-level keys under `requires` (e.g. `php`, `extensions`) are
  reserved for future use; declare them only when documented in
  `PLUGIN-API.md`.

## `tested_up_to` (optional, recommended for marketplace listings)

The highest Core version against which the extension's release was
**actually tested**. Unlike `requires.dixlase`, this is informational only:
the extension is not refused if the host Core is newer.

```json
{
    "requires": {
        "dixlase": "^0.1.0"
    },
    "tested_up_to": "0.1.4"
}
```

Semantics:

- The value is a single Core version string (no constraint operator).
  `tested_up_to` answers "what is the highest Core release the author has
  run this extension against and verified clean?" — not "what range works".
- If the host Core's version is **higher** than `tested_up_to`, the admin
  UI marks the extension with a soft "untested on this Core release"
  notice but does not block installation or activation.
- If the host Core's version is **higher than the major covered by
  `requires.dixlase`** (e.g. `requires.dixlase = "^0.1.0"` but the host
  is on `1.0.0`), the extension is refused regardless of `tested_up_to`.
- Updating `tested_up_to` does **not** require a version bump on its own
  — it is metadata about validation effort, not a code change.

## Why a separate field

`requires.dixlase` is a **promise** ("I refuse to run outside this
range"). `tested_up_to` is a **statement of fact** ("I last ran my
test suite on this Core release"). Combining them would force authors
to choose between being permissive (raise the floor and risk false
"unsupported" errors on older patch releases) and being conservative
(lock to the exact tested version, preventing routine patch upgrades).

Splitting the two follows the pattern used by WordPress (`Requires at
least` vs `Tested up to`) and by Composer (`require` constraints vs
`README` compatibility notes).

## Worked example

```json
{
    "package_name": "acme/forms",
    "version": "1.2.0",
    "requires": {
        "dixlase": "^0.1.0"
    },
    "tested_up_to": "0.1.4"
}
```

Reading:

- This release was built against the `^0.1` Plugin API contract.
- It refuses to load on Core `0.0.x` (below floor) or `1.x` (outside the
  caret range).
- It was last verified against Core `0.1.4`. On Core `0.1.5` the admin UI
  will show an "untested" badge until the author publishes a new release
  with `tested_up_to` raised.

## See also

- [Supply-Chain Metadata](supply-chain-metadata.md) — `author_id` and
  `authority_key_id` fields (required for hijack detection).
- [Plugin Health Scoring Rules](health-scoring-rules.md) — how missing or
  inconsistent metadata affects the health score.
- [`PLUGIN-API.md`](../../../PLUGIN-API.md#stability-pledge) — the
  versioning promise that `requires.dixlase` constraints rely on.
