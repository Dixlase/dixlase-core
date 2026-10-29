# Versioning

How Dixlase numbers its releases, what each part of a version means, and which
field decides whether an extension runs on a given core.

This page is the policy. The mechanics of cutting a release are in
[Releasing Core](releasing.md).

## The short version

Releases follow semantic versioning: `MAJOR.MINOR.PATCH`.

| Bump | What goes in it | Examples |
| --- | --- | --- |
| **PATCH** (`0.1.19` → `0.1.20`) | Bug and security fixes. No change to default behaviour. A migration only as far as a fix needs it | A security fix, a corrected translation key, a queue that now drains on shutdown |
| **MINOR** (`0.1.20` → `0.2.0`) | New features, changes to default behaviour, new settings, Plugin API changes | Tagging media files; making "publish from the admin" the default for file-stored content |
| **MAJOR** (`0.x` → `1.0.0`) | The point at which Dixlase can be recommended for production use | GA |

**The test for PATCH against MINOR: does an operator who applies the update need
to know something?** If they can apply it without reading anything, it is a
PATCH. If they need to read the release notes and decide something — a new
setting, a changed default, a feature they may want to turn off — it is a MINOR.

## Why a new feature is a MINOR, even below 1.0

Semantic versioning says a MINOR release is where backward-compatible
functionality is added, and that is the rule Dixlase follows. Major version zero
is explicitly permissive in the specification ("anything MAY change at any
time"), so 0.x does not oblige a project to use its MINOR slot for one
particular purpose.

Some ecosystems adopt the convention that in `0.y.z` the MINOR acts as the
breaking axis, because Composer's and Cargo's caret ranges are written that way:
`^0.1.0` means `>=0.1.0 <0.2.0`. That convention serves a **library** consumed
through a package manager, where a version range is the only compatibility
signal available.

Dixlase is an application, and it has a second, explicit compatibility signal —
`requires.dixlase_api`, below. So the version number is free to do the job an
operator reads it for: tell them how large the update is. A MINOR means "there
is something new here; read the notes". It does not mean "your extensions will
stop working".

## Extension compatibility is decided by `dixlase_api`, not by the core version

An extension manifest (`plugin.json` / `theme.json`) declares two different
things, and they do different jobs:

```json
"requires": {
    "dixlase": ">=0.1.0",
    "dixlase_api": "^0.1",
    "php": ">=8.3"
}
```

- **`dixlase_api` is the compatibility contract.** It names the Plugin API
  version the extension was written against, and it is what core checks when it
  loads the extension. The core side is
  `App\Extension\ExtensionApi::CURRENT_VERSION`, and the comparison lives in
  `ExtensionCompatibilityChecker::check()`.
- **`dixlase` is a floor: the oldest core the extension works on.** It is not an
  upper bound, and it is not the compatibility gate. Write it as `>=X.Y.Z`,
  never as a caret.

**Write `dixlase` as a floor.** A caret (`^0.1.0`) silently pins the extension
to one core MINOR, so a feature release of core that changes nothing the
extension uses would still refuse to install it. That is not the intent, and the
caret was only ever a default that nobody revisited.

The two versions move independently. Core going from `0.1.x` to `0.2.0` does not
move `dixlase_api`, so it does not affect any extension that declares `^0.1`.

### Where each one is enforced

Being precise about this matters, because the two checks have different reach.

| Check | Field | Where it runs | What it does |
| --- | --- | --- | --- |
| Load-time | `dixlase_api` | `PluginServiceProvider`, `ThemeServiceProvider` | During the beta: logs a warning and **continues** loading |
| Install / update | `dixlase` and `php` | `dls:plugin:install`, `dls:plugin:update`, `dls:theme:install`, `dls:theme:update`, via `EnforcesExtensionRequirements` | **Refuses** the operation |
| Health score | `dixlase`, `php`, `dixlase_api` | `PluginHealthScorer` | Reports a finding; no enforcement |

Two consequences worth stating plainly:

- The admin panel's install and update buttons call those same Artisan commands,
  so **the install-path refusal applies to the admin panel too**, not only to
  the CLI.
- An extension placed as files — a `git clone`, an `rsync`, an image build, a
  restored backup — passes through no install command, so **nothing checks its
  `requires.dixlase` at all**. The load-time check reads the other field and, in
  the beta, only warns. See `dixlase-core#756`.

## The beta: `0.x`

During the `0.x` series the Plugin API is **published but not frozen**. The
elements enumerated in `PLUGIN-API.md` may still change in backward-incompatible
ways while the design settles.

- A breaking Plugin API change ships in a MINOR release, never in a PATCH, and
  bumps `ExtensionApi::CURRENT_VERSION`.
- Every breaking change is listed in `CHANGELOG.md` under a **"Plugin API —
  Breaking"** heading with migration notes. Where practical the old symbol is
  kept for one release and marked `@deprecated`.
- A MINOR release is **not** required to contain a breaking change. Most of them
  will not.

The Plugin API freeze is announced in `CHANGELOG.md` and marked with the
`plugin-api-v1.0` tag. **It is not tied to any particular core version,
including 1.0.** After the freeze, breaking changes ship only in a MAJOR
release, with at least one full MINOR cycle of deprecation.

## `1.0.0` means production-ready

`1.0.0` is reached when Dixlase can be recommended for production use. It is a
statement about readiness, not about any particular feature landing. From `1.0`
on, a breaking change ships only in a MAJOR release.

## `main`, `next`, and the `-dev` suffix

Every repository has two long-lived branches, and the version number says which
one you are looking at:

- **`main` is exactly the latest release.** It carries a released version
  (`0.1.20`) and moves only when a release is cut.
- **`next` is the next version under development.** It carries that version with
  a `-dev` suffix (`0.1.21-dev`), and it is the default branch.

The suffix exists so that a development tree never claims to *be* a release. An
install that tracks `next` and reads `0.1.20` would believe it already had
0.1.20, when in fact it has 0.1.20 plus unreleased commits. `version_compare`
places `0.1.21-dev` above `0.1.20` and below `0.1.21`, so a tracking install is
never offered a release it has already passed.

Right after a release, `next` is bumped to the next PATCH with `-dev`. If that
release turns out to be a MINOR instead, the bump is corrected when the release
is cut — the suffix is a placeholder, not a promise about which part moves next.

## A released number is never re-cut

Once a version is published, that number is spent. A mistake ships as the next
version, never as a re-tag of the same one.

Two users would otherwise hold different trees both calling themselves
`0.1.20`, and the updater does not offer an update to the version already
installed, so the correction would never reach anyone who already applied it.

Re-cutting was acceptable exactly once, before the first public release.

## No maintenance branch during the beta

There is no long-lived `0.1.x` branch. An urgent fix for a released version uses
the **hotfix** path instead: branch `hotfix/<issue>-<slug>` from `origin/main`,
release from it, fast-forward `main` to the new tag, then merge `origin/main`
into `next`.

That covers the case a maintenance branch would exist for — shipping `0.1.21`
while `next` is already at `0.2.0-dev` — without a third long-lived branch, and
without breaking the rule that `main` is exactly the latest release. Whether a
maintenance line is worth having is revisited at GA, when there are users who
cannot simply track the newest release.

A security fix ships as a PATCH as soon as it is ready. It never waits for a
feature release.

## Extensions version independently

Each plugin and theme has its own version and its own history. An extension's
version matching core's is a coincidence of two things being released the same
day, not a relationship.

The same PATCH / MINOR / MAJOR meanings apply to an extension, read against its
own feature set.

## Release mechanics

- **Core** keeps its version in two files that must agree: `VERSION` and the
  `version` field of `dixlase.json`. Bump both with
  `php scripts/bump-version.php <x.y.z>`; CI fails when they disagree.
- **An extension** keeps its version in its manifest (`plugin.json` /
  `theme.json`) and in `composer.json` (top-level `version` for a plugin,
  `extra.dixlase.version` for a theme). Its own `scripts/bump-version.php` moves
  them together, and `package.json` where one exists.
- A signed extension is **re-signed after the bump**, because the signature
  covers the manifest and the changelog. Signing is the last step before the
  commit and the tag.

## Reference

- [Releasing Core](releasing.md) — the steps and the pre-tag checks
- `CHANGELOG.md` — the authoritative record of Plugin API additions,
  deprecations and removals
- `PLUGIN-API.md` — the stability pledge and the enumerated API surface
- `app/Extension/ExtensionApi.php` — `CURRENT_VERSION`, `SUPPORTED_RANGE`
- `app/Services/Extension/ExtensionCompatibilityChecker.php` — both checks
