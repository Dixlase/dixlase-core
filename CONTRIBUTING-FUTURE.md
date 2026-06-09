# Future Contribution Flow (Planned)

> **Status — Not currently active.** This document describes the contribution flow that will be activated **after the Contributor License Agreement's formal legal review is complete and external pull requests are reopened**. Until then, please refer to [`CONTRIBUTING.md`](./CONTRIBUTING.md) for the contributions currently being welcomed (Issue-based bug reports, Discussions, etc.).
>
> This file is preserved as the planned operating procedure so that contributors can review what will be expected when external PRs reopen. **It is not active today and signing the CLA today is not required.**

## Ways to Contribute (planned)

There are many ways to contribute to Dixlase:

- **Report bugs** or suggest features by [opening an issue](https://github.com/Dixlase/dixlase-core/issues)
- **Submit code** via pull requests (bug fixes, new features, performance improvements)
- **Improve documentation** including guides, API docs, and translations
- **Help others** in discussions and community channels
- **Review pull requests** from other contributors
- **Develop plugins and themes** for the Dixlase ecosystem

Bug reports and Discussions are welcomed today; code-bearing contributions resume once CLA legal review is complete.

## Before You Contribute

### Important: Contributor License Agreement (CLA)

Dixlase is distributed under a **dual license** (AGPL v3 + commercial). To maintain this licensing model, contributions to the Dixlase **core repository** require a signed Contributor License Agreement.

A single CLA document ([CLA.md](./CLA.md)) covers both individuals and entities; you choose a signing capacity (individual or entity) at the top.

- **Sign as an individual** — for any person contributing on their own behalf.
- **Sign as a legal entity** — for an organization that wants to authorize its employees to contribute on its behalf (complete Schedules A and B). Sign in this capacity when contributions are part of an employment relationship and the employer asserts rights in the work.

In summary, under the CLA:

- You **retain ownership** of your contribution
- You **grant exc-D inc.** a perpetual, worldwide, irrevocable, sublicensable license sufficient to support the dual-license model
- You **agree not to assert moral rights** in a way that would prevent the exercise of that license
- You **confirm** you are authorized to grant the license (employer permission, original creation, third-party material disclosure)

#### How to submit your CLA (interim process planned for activation)

When external PRs reopen, CLA submission will initially be handled by email:

1. Read [`CLA.md`](./CLA.md) in full and choose your signing capacity (individual or entity)
2. Fill in the applicable information fields (and Schedules A and B for entities) and sign at the bottom
3. Email the completed file to **info@dixlase.org** with the subject `CLA submission — <your name or organization>`
4. Wait for confirmation before submitting your first pull request

A subsequent Dixlase release will introduce automated CLA signing (e.g. via [CLA Assistant](https://cla-assistant.io/)) integrated with GitHub PRs. Until that automation is in place, the email process above will apply once PRs reopen.

For the broader licensing context, see the [Copyright Policy](./COPYRIGHT-POLICY.md).

> **Note on plugins and themes:** If you are developing a plugin or theme that uses Dixlase's Plugin API (see [PLUGIN-API.md](./PLUGIN-API.md)), you retain full copyright and can license your work under any license you choose. The CLA only applies to contributions to the Dixlase core repository.

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

## Contribution Attribution

Contributors are credited in the project's commit history, release notes, and contributor acknowledgments. The CLA preserves your right to be identified as the author of your contributions (see CLA Section 11).
