# Issue Guidelines

How Dixlase uses GitHub Issues: what goes in an issue, how to write one, how it is labelled, and how it is closed. The goal is a public history in which every known problem can be traced from report to fix to release.

The Japanese mirror of this page lives at [`docs/ja/development/issues.md`](../ja/development/issues.md).

---

## What belongs in an issue

**Yes**

- Bugs, including ones the maintainers find themselves
- Usability problems in the admin panel, installers or documentation
- Feature suggestions
- Hardening work that is not a vulnerability (defence in depth, safer defaults)
- Technical debt worth tracking in public
- Documentation and translation errors

**No**

- **Unfixed security vulnerabilities.** Report them privately (see [SECURITY.md](../../SECURITY.md)). After the fix ships, the maintainers publish a GitHub Security Advisory. A public issue, if any, only records that the fix has shipped.
- Details of a specific production environment: host names, paths, credentials, logs that contain personal data.
- Internal matters of private repositories.

**Where to file**

- File the issue in the repository where the problem lives. A plugin bug goes to that plugin's repository.
- A problem that spans several repositories gets one issue in `dixlase-core`. The other repositories link to it.

---

## Issue first

Before editing the source of core or of an official plugin or theme, open an issue for the change, or find the one that already exists. The branch and the pull request point at it (`Fixes #N` in the description).

**Exceptions.** No issue is needed for:

| Reason | Covers |
| --- | --- |
| `security` | An unfixed vulnerability. It is fixed privately, and the fix is published as a Security Advisory afterwards (see [What belongs in an issue](#what-belongs-in-an-issue) and [Writing a security pull request](#writing-a-security-pull-request)) |
| `release` | Version bumps and changelog entries for a release |
| `signing` | Re-signing a plugin or theme |
| `dependencies` | Dependency updates, including Dependabot pull requests |
| `generated` | Regenerating generated files, with no hand edits |
| `typo` | A typo fix that changes no meaning |

An exempt pull request says so on its own line in the description:

```
No issue: release
```

**Something else turns up while you work.** Do not fix it in the same change. Open a separate issue for it. One pull request answers one issue.

**The check.** A workflow on every pull request fails when the description neither links an issue (`#N`, `owner/repo#N` or an issue URL) nor states one of the reasons above. Text inside HTML comments does not count, so the template's own examples never satisfy it. Dependabot pull requests are exempt.

---

## Title

Use the same bilingual style as commit messages, with an area prefix:

```
<area>: <English summary> / <日本語の要約>
```

Examples:

```
core-update: the admin sees a 500 during the vendor swap / コア更新: vendor の入れ替えの間に管理者に 500 が出る
extensions: installing without a token hits the GitHub rate limit / 拡張機能: トークン無しのインストールで GitHub の利用制限に当たる
```

External reporters may write the title in English or Japanese only. A maintainer adds the other language during triage.

The area prefix matches the `area:` label (see below).

---

## Body

Bug reports and feature suggestions use the issue forms in `.github/ISSUE_TEMPLATE/`.

Issues that maintainers file for their own findings use the **Known issue** form. Its body is one block of English followed by one block of Japanese, separated by a `---` line. Write every section in English first, then every section in Japanese. Do not alternate the two languages section by section: each language should read as one continuous text.

The sections, in the same order in both languages:

| Section (EN / JA) | Content |
| --- | --- |
| What happens / 何が起きるか | The observed behaviour, in one or two sentences |
| Impact / 影響 | Who is affected, from which version, and how badly |
| Reproduction / 再現手順 | Steps, and the version the steps were run on |
| Cause / 原因 | The cause, if known. Mark a guess as a guess |
| Proposed fix / 直し方の案 | A possible fix (optional) |
| Done when / 完了の条件 | What has to be true before the issue can be closed |

```markdown
### What happens
…
### Done when
…

---

### 何が起きるか
…
### 完了の条件
…
```

Write what was observed, not what was assumed. When a reproduction depends on a site's state (installed plugins, preset, HTTPS mode), say so.

---

## Labels

Every issue gets one **type** label and at least one **area** label. **Priority** and **status** labels are added during triage.

The canonical list is [`.github/labels.yml`](../../.github/labels.yml).

| Group | Labels |
| --- | --- |
| Type | `bug`, `enhancement`, `docs`, `hardening`, `chore`, `question` |
| Area | `area:core-update`, `area:extensions`, `area:install`, `area:admin`, `area:front`, `area:security`, `area:i18n`, `area:api`, `area:ci` |
| Priority | `P0` (blocks a release or loses data), `P1` (fix in the next release), `P2` (when convenient) |
| Status | `needs-repro`, `confirmed`, `blocked` |
| Resolution | `duplicate`, `wontfix`, `invalid` |

Priorities follow the GA roadmap phases.

---

## Milestones

Assign the release an issue is planned for as a milestone, for example `v0.1.4`, `v0.2.0` or `GA`. An issue without a milestone is not scheduled yet.

Move the milestone when the plan changes, instead of leaving an issue on a release that has already shipped.

---

## From issue to release

1. The pull request that fixes an issue says `Fixes #N` in its description. Merging it closes the issue, and the squash commit keeps the PR number (`(#M)`).
2. The CHANGELOG entry cites the PR number. Cite the issue number too when the issue holds the discussion.
3. When the issue is closed, a maintainer comments with the version that carries the fix:
   ```
   Fixed in v0.1.4 (#M). / v0.1.4 で修正しました(#M)。
   ```
4. An issue closed without a fix gets a resolution label (`wontfix`, `duplicate` or `invalid`) and a one-line reason.

This keeps the chain report → issue → PR → release visible from any of its ends.

---

## Writing a security pull request

A `fix(security):` pull request lands privately-fixed code to `main`. Until the fix is in a release, **the pull request itself does not describe the vulnerability** — the hole stays private, and the GitHub Security Advisory carries the mechanics.

**Rule.** The title and the body describe the **ordinary change**: what the code now does, in the plain terms any other `fix:` or `refactor:` pull request would use. The squash commit on `main` inherits the same subject and body.

**Do not write, in the public pull request:**

- The exploit mechanics, a proof of concept, or a request / response payload that reproduces the issue
- Enumerations or counts of the affected call sites ("about 78 sites")
- Role or capability names that leak the attack surface ("any contributor / editor could …")
- A severity classification ("Critical", "unauthenticated RCE", a mock CVSS)
- The versions the hole shipped in — the Security Advisory records them

**Do write, in the public pull request:**

- What the code now does, in terms of the shipped behaviour ("verify the download before applying it", "escape the flash message", "gate the admin route behind authorization")
- A `No issue: security` line in place of `Fixes #N`

**The vulnerability itself lives in the Security Advisory** (`Security` tab → *Draft advisory*, or `gh api repos/{owner}/{repo}/security-advisories`). It stays private until the fix ships, publishes with a CVE, and names the affected and fixed versions. The CHANGELOG entry is written at **release** time, alongside publishing the advisory — not as part of the pull request that lands the fix.

**An already-public hole** — an upstream dependency CVE, an issue already disclosed elsewhere — may name the upstream advisory in the pull request. There is nothing left to protect.

**Review comments are as public as the pull request itself.** When a review needs to discuss the mechanics, move that part to the Security Advisory thread.

---

## Triage

- A maintainer adds labels and replies to an external report within a few days.
- When a report cannot be reproduced, label it `needs-repro` and ask for what is missing. If there is no answer for 30 days, close it with a note that it can be reopened.
- Code in an issue is reference information only (see [CONTRIBUTING.md](../../CONTRIBUTING.md)). A maintainer re-implements any fix.

---

## Language

Issues may be written in English or Japanese. Maintainers write titles bilingually and may answer in either language.
