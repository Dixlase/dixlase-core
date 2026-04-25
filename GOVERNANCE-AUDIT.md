# Governance Audit — Deferred Items

**Version:** 1.0
**Last reviewed:** 2026-04-25

This document captures governance, legal, and operational concerns surfaced during the pre-v0.1.0 audit but **deliberately deferred**. Items listed here are known gaps that are acceptable for the current release stage and must be revisited under the conditions noted.

For the Japanese version, see [GOVERNANCE-AUDIT.ja.md](./GOVERNANCE-AUDIT.ja.md). In case of any inconsistency, the Japanese version shall prevail.

## How to Read This Document

Each entry lists:

- **Current snapshot** — what is in place today
- **Trigger for re-evaluation** — the condition or cadence that brings the item back into scope

Items are not dismissed; they are queued.

---

## Items Already Resolved

These were classified as critical (must-fix before v0.1.0) or high-priority (must-fix immediately after) and have been completed:

- **License consistency** — AGPL v3 + commercial dual-license structure, Plugin API Exception, license headers across all source files (`cbe43495`, `320f1fb0`, `6a04b7ef`)
- **CAA legal completeness, EN/JA parity** — [`COPYRIGHT-POLICY.md`](./COPYRIGHT-POLICY.md) / [`COPYRIGHT-POLICY.ja.md`](./COPYRIGHT-POLICY.ja.md); strengthened to v1.1 with explicit irrevocability and an indemnification clause (`647de24b`)
- **AGPL §13 source-provisioning surface** — running-instance source URL exposed via admin (`b703e583`)
- **GitHub Community Standards** — [`CONTRIBUTING.md`](./CONTRIBUTING.md), [`SECURITY.md`](./SECURITY.md), issue templates, PR template (`a237b8d7`, `8ec1dcea`)
- **Source license headers** — AGPL headers across PHP, JS, CSS, tests
- **CAA scope and edge cases** — handled inline in `COPYRIGHT-POLICY.md` v1.1 (Sections 6, 8, 9)
- **Contribution operational flow** — branching, conventional-commit guidance, feature-proposal route, review expectations in `CONTRIBUTING.md`
- **Pre-release contributor history** — all commits before v0.1.0 are by the founder; no external retroactive consent required. Legacy email identities are consolidated via `.mailmap`
- **Security disclosure effectiveness** — coordinated disclosure, safe harbor, scope clauses in `SECURITY.md`

---

## Release-day Checklist

The following operational items can only be verified or activated at the moment the repository is published. Walk through this list when cutting v0.1.0 and confirm each item before announcing the release.

### Email and Reporting Operations (F-1)

- [ ] Send a test message to `security@exc-d.com` from an external address and confirm receipt
- [ ] Verify DNS MX records and any forwarding aliases resolve to the maintainer inbox
- [ ] Decide and document the acknowledgment policy: auto-reply, 24-hour human response, or rely on GitHub PVR notifications only

### GitHub Repository Settings (F-5, H-3)

- [ ] Make the repository public (`Settings → General → Change repository visibility`)
- [ ] Enable **Private vulnerability reporting** (`Settings → Code security`) — this activates the `/security/advisories/new` link surfaced in `SECURITY.md` and the issue templates
- [ ] Confirm the Security tab is visible and renders `SECURITY.md` at `/security/policy`
- [ ] Verify the Community Standards page shows green checks for everything except Code of Conduct (deferred — see Low Priority)
- [ ] Enable Dependabot alerts and security updates
- [ ] Enable Secret scanning (free for public repositories)
- [ ] Add branch protection on `main`: required PR reviews, required status checks, force-push blocked

When each item is completed, move the corresponding entry to **Items Already Resolved** with the date and any relevant configuration reference.

---

## Medium Priority — Phased Post-v0.1.0

### I. Structural and Adversarial Scenarios

#### I-1. Free-rider Defense
**Snapshot:** The Plugin API boundary is documented in `PLUGIN-API.md` with a four-condition test (API-only access, no core modification, no internal-implementation bypass, standard loader). Enforcement currently relies on review pressure, not technical isolation. AGPL §13 catches a SaaS operator modifying the core; the Plugin API Exception is one-way and does not let modified-plugin claims escape that. Pure intranet (non-network-interactive) deployments are by design outside §13 reach.

**Trigger:** First detected commercial fork; any major Plugin API surface expansion; annual cadence regardless.

#### I-2. Trademark and Brand Protection
**Snapshot:** "Dixlase" trademark **not yet filed**. Plugin API Exception does not explicitly grant trademark rights but does not explicitly deny them either. No "official vs unofficial" marker beyond marketplace listing.

**Trigger:** First sign of third-party commercial use of the name; before any expanded marketing push; **target trademark filing decision: 2026-Q3**.

#### I-3. Long-term Governance / Bus Factor
**Snapshot:** Single-key-person project. exc-D inc. is the corporate steward; no secondary entity. Nonprofit transition is mentioned in `COPYRIGHT-POLICY.md` as a possibility but has no documented criteria.

**Trigger:** When active contributor count exceeds 10; annual corporate governance review.

#### I-4. Policy Change Resilience
**Snapshot:** No notification procedure for CAA amendments to existing contributors. License migration path (e.g., AGPL → MPL 2.0) is enabled by the CAA's broad assignment but undocumented. No formal Plugin API breaking-change policy.

**Trigger:** Before any CAA amendment; before v1.0 (Plugin API stability commitment must precede v1.0).

---

### J. Legal Risk — Detailed Review

#### J-1. Japan-specific Risk
**Snapshot:** Author's moral rights (著作者人格権) non-exercise clause is included in the CAA but has not been reviewed by Japanese counsel. Employee/work-for-hire (職務著作) verification procedure is not formalized. Privacy Act handling of contributor contact data is implicit (email-only minimal collection).

**Trigger:** Before accepting first corporate contribution; before v1.0.

#### J-2. International Considerations
**Snapshot:** GDPR — minimal personal data collected from contributors; no DPIA performed. US contributors — CAA is Japanese-law-governed, with English version provided; enforceability under US law not separately reviewed. Export control — Dixlase ships standard framework-provided crypto; no ECCN classification performed.

**Trigger:** Before v1.0; first inquiry from a non-JP/non-US jurisdiction.

#### J-3. Dual-license Practical Validity
**Snapshot:** CAA grants exc-D inc. the right to relicense, which is the core mechanism for commercial offerings. Obligations from third-party AGPL-compatible upstream libraries (e.g., framework dependencies) still bind commercial-license customers — this is industry-standard but not yet documented for customer-facing materials.

**Trigger:** Before launching commercial license sales; when adding any non-permissive upstream dependency.

---

### K. Operational Maturity

#### K-1. Contact and Intake
**Snapshot:** `contact@exc-d.com` and `security@exc-d.com` are configured but operate as one-person inboxes. No SLA published — `SECURITY.md` states "promptly" without numeric targets (deliberate, until response capacity is proven). No backup contact for founder absence.

**Trigger:** First vulnerability report (validates response time); when the team grows beyond founder.

#### K-2. Document Maintenance
**Snapshot:** LICENSE / COPYRIGHT-POLICY / PLUGIN-API.md carry version markers but lack a formal versioning policy. No governance-document CHANGELOG. EN/JA sync is handled manually per commit, with no automated diff check.

**Trigger:** First contributor-requested governance-doc amendment; annual cadence.

#### K-3. Community Engagement
**Snapshot:** No FAQ explaining "why AGPL?" / "why a CAA?" — explanations are spread across README and COPYRIGHT-POLICY. No Code of Conduct (see Low Priority). No active public communication channel (Discord, Discussions, mailing list).

**Trigger:** When public Discussions activity begins; first non-trivial contributor pushback on the CAA.

---

## Low Priority — Defer Until Growth Justifies

### Code of Conduct
**Snapshot:** None adopted.
**Rationale for deferral:** Adopting a CoC without a community to enforce it against is theater. Adopt when community size makes conflicts foreseeable.
**Trigger:** First multi-contributor PR conflict; external request.

### Migration to CLA Assistant
**Snapshot:** Currently using the "commit = consent" lightweight model documented in `COPYRIGHT-POLICY.md`.
**Rationale for deferral:** CLA Assistant adds friction and operational overhead disproportionate to current scale.
**Trigger:** Active contributor count exceeds 20; first corporate contributor requiring an explicitly signed agreement.

### Formal Legal Review
**Snapshot:** Documents are drafted in-house, referencing comparable Japanese OSS projects (e.g., EC-CUBE).
**Rationale for deferral:** Estimated cost (50,000–200,000 JPY for a spot review) currently outweighs measurable risk. Documents follow conservative, established patterns.
**Trigger:** Before commercial-license launch; before v1.0; on first material legal challenge.

---

## Audit Cadence

- **Each minor release** — verify no trigger above has fired
- **Annually** — full re-read; reclassify items whose priority has changed
- **On external pressure** — legal inquiry, contributor pushback, fork attempt, etc.

When an item is resolved, move it to **Items Already Resolved** with the resolving commit or PR reference. When a new gap is identified post-release, add it under the appropriate priority section with the same structure.

---

## Final Decision Integrity Check

The following decisions established during pre-v0.1.0 review remain authoritative. If any is reversed, this document and the underlying policy files must be updated together:

- Core distributed under AGPL v3 + a commercial license (dual)
- Plugins and themes may be distributed under any license chosen by their authors
- License propagation is blocked by the Plugin API Exception (four-condition test)
- CAA assigns economic copyright to exc-D inc. (assignment model, EC-CUBE precedent)
- Author's moral rights non-exercise clause (Japan-law adaptation)
- Future transition to a nonprofit steward is mentioned with reservation language
- "Commit = consent" lightweight model from launch; CLA Assistant migration deferred
- Commercial license operates on a "contact us" intake from launch; terms TBD
- "Dixlase" trademarks are held by exc-D inc. and not granted via the Plugin API Exception
- AGPL §13 source-provisioning link implemented in the admin surface
- Dedicated security reporting channel (`security@exc-d.com`)
- Bilingual EN/JA documentation, positioning Dixlase as a Japan-origin OSS with international reach
