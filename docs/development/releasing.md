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

`release.yml` runs the same check before it builds the ZIP and fails when the lock is behind, so an outdated theme is never published. A manual rebuild of an older tag (`workflow_dispatch`) can skip the check with the `allow_outdated_theme` input.

The check only covers new installs. A core update never changes an installed theme; theme updates go through `dls:theme:update`.
