# Releasing Core

Steps that have to happen before a core release is tagged. The release ZIP itself is built by `.github/workflows/release.yml` when the GitHub release is published.

The Japanese mirror of this page lives at [`docs/ja/development/releasing.md`](../ja/development/releasing.md).

---

## Bundle the theme's latest release

The release ZIP, the one-liner installer and `composer create-project` all install the official theme (`dixlase/dixlase-onepage`) that `composer.lock` locks. Nothing moves that lock when the theme cuts a release, so it has to be updated by hand before every core release (#517).

1. Update the lock to the theme's latest release:

   ```bash
   composer update dixlase/dixlase-onepage
   ```

   `composer.json` requires the theme by a release range (`>=0.2 <1.0`), so any 0.x release is taken. When the theme reaches 1.0, raise the range first.

2. Check the result:

   ```bash
   php scripts/check-bundled-theme.php
   ```

   It exits 1 when `composer.lock` does not lock the theme's latest release. Set `GITHUB_TOKEN` to avoid the anonymous GitHub API rate limit.

3. Commit `composer.json` and `composer.lock` through a pull request (`No issue: dependencies`), and tag the release after it is merged.

4. **Run the check once more, immediately before pushing the tag.**

   ```bash
   php scripts/check-bundled-theme.php
   ```

   Not as a formality. The window between merging the lock and pushing the tag is enough: on 2026-10-13 the theme published `v0.4.0` **53 seconds** before core `v0.1.10` was published. The check had passed half an hour earlier and was correct at the time. `v0.1.10` went out with no archives at all as a result (#557).

## Releasing the theme and core together

**Theme release → the pull request that moves core's lock → core's release.** In that order. The other way round loses by construction: the lock cannot name a theme release that does not exist yet, so a theme released after core's lock was set is always ahead of it.

## Where the check blocks, and where it only warns

| Where | Behaviour |
| --- | --- |
| A pull request that changes `VERSION` | **Blocks.** `release-preflight.yml` reports the `Bundled Theme Lock` check, which is required on `main` |
| `release.yml` on a published release | **Warns, and builds anyway** |
| `release.yml` on a `workflow_dispatch` | Fails, unless `allow_outdated_theme` is set |

The release build used to refuse, and that was the wrong trade. It is triggered by `release: published`, so by the time it can refuse, the release is already public — and a release's archives are attached only at the moment it is published, so a refusal leaves a published release that can never be given any. `releases/latest/download/dixlase-core.zip` then answers 404, and the one-line installer falls back to its Composer path, which fails outright on a host without Composer. A theme one release behind is installable, and `dls:theme:update` moves it. So the blocking happens before the merge, and the build only warns (#557).

The check only covers new installs. A core update never changes an installed theme; theme updates go through `dls:theme:update`.
