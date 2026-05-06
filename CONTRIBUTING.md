# Contributing to Dixlase

Thank you for your interest in Dixlase.

## Current Contribution Status (v0.x)

Dixlase is in early development. **External pull requests are not currently accepted.** External code contributions will reopen once the formal legal review of the Contributor License Agreement (CLA) is complete.

**Currently welcomed:**

- Bug reports via [Issues](https://github.com/Dixlase/dixlase-core/issues)
- Feature suggestions via [Discussions](https://github.com/Dixlase/dixlase-core/discussions) or Issues
- Documentation typo / error reports via Issues
- Questions and feedback via Discussions

**Not currently accepted:**

- Source code pull requests (will reopen after CLA legal review)
- Documentation pull requests (please file an Issue instead)
- Translation pull requests (will reopen after CLA legal review)

The future PR-based contribution flow is documented in [`CONTRIBUTING-FUTURE.md`](./CONTRIBUTING-FUTURE.md). That document is currently informational; it will become operative once the CLA legal review is complete and external code contributions are reopened.

## Reporting Bugs

When reporting bugs, please include:

- A clear description of the problem
- Steps to reproduce
- Expected vs. actual behavior
- Environment details (OS, PHP version, browser, etc.)
- Relevant logs or error messages

> **A note on code snippets in bug reports.** If you include a code suggestion to help fix the bug, it is treated as **reference information only**. A maintainer will independently re-implement any fix; your snippet is unlikely to be committed verbatim. This is necessary because external code contributions cannot currently be accepted under Dixlase's dual-license model until the CLA's formal legal review is complete.

## Suggesting Features

When proposing a new feature, open an issue describing:

- The use case or problem the feature addresses
- The proposed behavior or API
- Alternatives you considered

For larger or design-heavy proposals, starting a thread in [GitHub Discussions](https://github.com/Dixlase/dixlase-core/discussions) before opening an issue is encouraged.

## Reporting Security Vulnerabilities

**Do not report security vulnerabilities through public issues.** Instead, please follow the procedure in [SECURITY.md](./SECURITY.md).

## Plugins and Themes

Plugins and themes that interact with Dixlase's Plugin API (see [PLUGIN-API.md](./PLUGIN-API.md)) are outside the scope of the core repository: their authors retain full copyright and may distribute them under any license of their choice. The current PR deferral applies to the Dixlase core repository only.

## Translations

Dixlase ships with **English source comments as the canonical form**. Each supported locale lives at `resources/comment-translations/{locale}/` (mirrored inside each plugin and theme), and `./convert-comments.sh ja` flips a development checkout to Japanese in-place. See [`docs/development/comment-translation.md`](./docs/development/comment-translation.md) for the full architecture.

If you spot a translation issue, please file an Issue rather than a PR — translation pull requests will reopen alongside code PRs once the CLA legal review is complete. In the meantime, dictionary files (`resources/comment-translations/{locale}/...`) are versioned with the source so any improvement you note in an Issue becomes easy for a maintainer to reproduce and apply.

## Questions?

If you have questions about contributing, feel free to:

- Open a [discussion](https://github.com/Dixlase/dixlase-core/discussions) on GitHub
- Email us at office@exc-d.com

Thank you for helping make Dixlase better — even before PRs reopen, your bug reports and feedback are valuable.
