# Contributing to Dixlase

Thank you for your interest in Dixlase.

## Current Status (v0.x)

Dixlase is in early development. **External pull requests are not currently accepted** — code contributions will open once the formal legal review of the Contributor License Agreement ([CLA](./CLA.md), in preparation) is complete. This document will be replaced with the full PR-based contribution guide at that time.

**Welcome now (via [Issues](https://github.com/Dixlase/dixlase-core/issues) / [Discussions](https://github.com/Dixlase/dixlase-core/discussions)):**

- Bug reports — a clear description, steps to reproduce, expected vs. actual behavior, and environment details (OS, PHP version, browser)
- Feature suggestions — the use case, proposed behavior, and alternatives considered
- Documentation / translation error reports
- Questions and feedback

**Not accepted yet:** pull requests of any kind (code, documentation, translations).

> Code snippets included in bug reports are treated as **reference information only**; a maintainer will independently re-implement any fix. This is required by Dixlase's dual-license model until the CLA review is complete.

## Security Vulnerabilities

**Do not report security vulnerabilities through public issues.** Follow the procedure in [SECURITY.md](./SECURITY.md).

## Plugins and Themes

Plugins and themes built on the Plugin API (see [PLUGIN-API.md](./PLUGIN-API.md)) are outside the scope of the core repository: their authors retain full copyright and may distribute them under any license of their choice. The PR deferral applies to the core repository only.

## Translations

Dixlase ships with English source comments as the canonical form; per-locale dictionaries live at `resources/comment-translations/{locale}/` and can be applied with `./convert-comments.sh ja` (see [`docs/development/comment-translation.md`](./docs/development/comment-translation.md)). Please report translation issues via Issues — the dictionaries are versioned with the source, so reported fixes are easy for a maintainer to apply.

## Questions?

Open a [Discussion](https://github.com/Dixlase/dixlase-core/discussions) or email info@dixlase.org.

Even before PRs open, your bug reports and feedback are valuable contributions.
