# Contributing to Dixlase

Thank you for your interest in contributing to Dixlase! This document outlines how to participate in the project. For the Japanese version, see [CONTRIBUTING.ja.md](./CONTRIBUTING.ja.md).

## Ways to Contribute

There are many ways to contribute to Dixlase:

- **Report bugs** or suggest features by [opening an issue](https://github.com/Dixlase/dixlase-core/issues)
- **Submit code** via pull requests (bug fixes, new features, performance improvements)
- **Improve documentation** including guides, API docs, and translations
- **Help others** in discussions and community channels
- **Review pull requests** from other contributors
- **Develop plugins and themes** for the Dixlase ecosystem

## Before You Contribute

### Important: Copyright Policy

Dixlase is distributed under a **dual license** (AGPL v3 + commercial). To maintain this licensing model, contributions to the Dixlase **core repository** require agreement to our [Copyright Policy](./COPYRIGHT-POLICY.md).

**By submitting a pull request, commit, or issue to this repository, you agree to the terms outlined in the Copyright Policy.** In summary:

- The economic copyright of your contribution is assigned to exc-D inc.
- You agree not to exercise moral rights over the contribution
- You confirm that you have the right to contribute the code

Please read [COPYRIGHT-POLICY.md](./COPYRIGHT-POLICY.md) in full before your first contribution.

> **Note on plugins and themes:** If you are developing a plugin or theme that uses Dixlase's Plugin API (see [PLUGIN-API.md](./PLUGIN-API.md)), you retain full copyright and can license your work under any license you choose. The Copyright Policy only applies to contributions to the Dixlase core repository.

## How to Contribute Code

### 1. Set Up Your Development Environment

Follow the setup instructions in [README.md](./README.md) to get a working development environment.

### 2. Find or Create an Issue

- Browse [open issues](https://github.com/Dixlase/dixlase-core/issues) for something to work on
- For larger changes, please open an issue first to discuss your proposal
- Comment on an issue to let others know you're working on it

### 3. Create a Branch

Create a topic branch from `main`:

```bash
git checkout -b feature/your-feature-name
```

Use descriptive branch names such as `fix/login-redirect` or `feature/two-factor-auth`.

### 4. Make Your Changes

- Write clear, well-documented code
- Follow the coding style used in the existing codebase
- Add or update tests as appropriate
- Update documentation if your change affects user-facing behavior
- Ensure all source files you add include the standard license header (see [README.md](./README.md))

### 5. Commit Your Changes

Use [Conventional Commits](https://www.conventionalcommits.org/) style for the commit subject:

- `feat:` — a new feature
- `fix:` — a bug fix
- `refactor:` — code restructuring without behavior change
- `docs:` — documentation only
- `test:` — adding or updating tests
- `chore:` — tooling, dependencies, or other maintenance

Write commit messages that explain *what* changed and *why*:

```
feat: add two-factor authentication for admin login

Detailed explanation of the change. Wrap lines at around 72
characters. Explain the problem this commit is solving and why
this particular solution was chosen.

Closes #123
```

Core maintainers additionally include a Japanese summary for the project's bilingual history. External contributors are **not** required to do this — English-only commits are welcome.

### 6. Submit a Pull Request

- Push your branch to your fork
- Open a pull request against `main`
- Fill out the pull request template completely
- Reference related issues (e.g., "Closes #123")
- Be responsive to review feedback

### 7. Review Process

- A maintainer will review your PR as time permits
- You may be asked to make changes before merging
- Once approved, a maintainer will merge your PR

## Reporting Bugs

When reporting bugs, please include:

- A clear description of the problem
- Steps to reproduce
- Expected vs. actual behavior
- Environment details (OS, PHP version, browser, etc.)
- Relevant logs or error messages

## Suggesting Features

When proposing a new feature, open an issue describing:

- The use case or problem the feature addresses
- The proposed behavior or API
- Alternatives you considered
- Whether you are willing to help implement it

For larger or design-heavy proposals, starting a thread in [GitHub Discussions](https://github.com/Dixlase/dixlase-core/discussions) before opening an issue is encouraged.

## Reporting Security Vulnerabilities

**Do not report security vulnerabilities through public issues.** Instead, please follow the procedure in [SECURITY.md](./SECURITY.md).

## Contribution Attribution

Contributors are credited in the project's commit history, release notes, and contributor acknowledgments. While the economic copyright is transferred to exc-D inc. under the Copyright Policy, your authorship is recognized and respected.

## Questions?

If you have questions about contributing, feel free to:

- Open a [discussion](https://github.com/Dixlase/dixlase-core/discussions) on GitHub
- Join our community channels (links in [README.md](./README.md))
- Email us at office@exc-d.com

Thank you for helping make Dixlase better!
