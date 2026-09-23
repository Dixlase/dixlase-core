# Dixlase Pillars — Guidelines (Draft)

> Status: **Draft v0.1** (published, not final)
> Created: 2026-09-23
> Decision-maker: exc-D inc.
> Planned split: `docs/pillars/` (README.md + one file per pillar)

## Labels used in this draft

Items in this draft carry the following labels. The labels will be removed once the text is finalized.

- [Proposal] A new proposal in this draft that needs a decision on whether to adopt it
- [TBD] The text cannot be finalized until a policy decision is made
- [Legal review] Requires confirmation from legal counsel

---

## 0. About this document

### Purpose
Dixlase is built on a set of pillars. This document states in writing what each pillar promises and what it does not. Its aim is to ensure that the core team, contributors, and users all read the pillars the same way.

### Scope and standing
- These guidelines set out **principles and boundaries**. The authoritative source for specific procedures and figures lives in the relevant document; these guidelines only refer to it. [Proposal]
  - Example: SECURITY.md is the authoritative source for the vulnerability reporting procedure and response SLA. These guidelines explain why a non-disclosure period exists and where its limits lie.
- The same commitment should not be written in more than one document. Where this cannot be avoided, the document states which source is authoritative. [Proposal]
- Descriptions of the pillars on the brand site and in the README must not contradict these guidelines.

### Audience
- **Core team**: the core development team (currently exc-D inc.)
- **Contributors**: people who report issues, future code contributors, and plugin and theme developers
- **Users**: individuals, web agencies, businesses, local governments, and educational institutions that deploy and operate Dixlase

---

## 1. Structure: five pillars and an apex

```
                     Freedom
                        ▲
   ┌──────────┬─────────┼──────────┬──────────┐
Security  Fairness  Transparency  Extensibility  Sustainability
```

- **Freedom** sits at the apex. It is what Dixlase aims for.
- **The five pillars** are the conditions that make it possible to exercise that freedom in practice. [Proposal: interpretation of this structure]
  - Without security, the freedom to run your own site comes with danger.
  - Without fairness, freedom belongs only to some.
  - Without transparency, there is nothing to base a free choice on.
  - Without extensibility, freedom is limited to what has been provided.
  - Without sustainability, today's freedom is lost tomorrow.

---

## 2. Definitions at a glance

| Pillar | One-line definition | Most relevant to |
|---|---|---|
| Security | Protection works without extra configuration, and the protective mechanisms are concentrated in the core | Users, plugin developers |
| Fairness | The same standards apply uniformly, and openly, to everyone in the same situation | Contributors, users |
| Transparency | The rules for what is disclosed, when, and what is not disclosed are themselves public | Everyone |
| Extensibility | Features can be added without modifying the core, within limits that do not compromise security | Plugin and theme developers |
| Sustainability | The project endures over the long term, so users can depend on it with confidence | Local governments, businesses, long-term users |
| Freedom | Users decide for themselves about their own sites, data, and operations | Everyone |

Note: every definition above is a [Proposal]. Once finalized, they will be checked against the pillar descriptions on the brand site.

---

## 3. When pillars conflict

### Basic rules [Proposal]

1. **Security is never weakened for the sake of another pillar.** However, users relaxing protective settings on their own sites falls within their freedom (→ 4. Security, "Boundary with freedom").
2. **Non-disclosure is not an exception to transparency but one of its procedures.** Even when information is withheld, the fact that it is withheld, the reason, and the deadline or condition for release are disclosed.
3. **Changes that disadvantage existing users come with an advance notice period.** Length of the period: [TBD] (suggested: 30 days or more)
4. **When another pillar is scaled back on grounds of sustainability, the decision and the reason are made public.**
5. **When a decision cannot be reached, it is weighed against Freedom at the apex, and the option that does not narrow users' choices is taken.**

### Anticipated conflicts and provisional rulings

| Conflict | Example | Provisional ruling |
|---|---|---|
| Transparency × Security | Whether to disclose a vulnerability before it is fixed | Withheld until fixed. The upper limit and exceptions are set in 6. Transparency [Proposal] |
| Freedom × Security | Whether users can disable CSP or signature verification | Allowed in principle, on condition that a warning is shown and the change is recorded in the audit log. Items that cannot be disabled are to be defined separately [TBD] |
| Extensibility × Security | Whether plugins may use raw SQL or handle authentication themselves | Not allowed. Extensions are declared; enforcement is done by the core |
| Fairness × Sustainability | Whether to give commercial licensees features or fixes ahead of others | Security fixes are released to all users at the same time. Any other differences are set in 5. Fairness [Proposal] |
| Fairness × Security | Whether to follow a step-by-step process with a malicious contributor | Threats to security are acted on immediately, skipping the steps [Proposal] |
| Transparency × Fairness | Whether to notify only key users of a vulnerability in advance | If advance notice is given, the criteria for choosing recipients are published [Proposal] |
| Transparency × Extensibility | Whether to publish all health score rules | Categories and criteria are published; detailed detection signatures are not [Proposal] |
| Freedom × Sustainability | Whether to collect telemetry | [TBD] If collected, it is off by default and what is sent is published [Proposal] |

---

## 4. Security

### Definition
Protection works without extra configuration, and the protective mechanisms are concentrated in the core.

### What we promise
- The 15 controls recommended by IPA, NIST, and OWASP are enabled without extra configuration.
- Protective mechanisms (authentication, authorization, validation, auditing) live in the core. Plugins only declare the permissions they need.
- Plugin permission declarations, Ed25519 signature verification, and health scoring through static analysis are provided.
- A channel for reporting vulnerabilities is provided, and response times are published. [TBD: SLA values]
- Updates are verified by signature and hash.

### What we do not promise / boundaries
- We do not claim to be "absolutely secure" or "the most secure in the world."
- We do not claim that the core can stop vulnerabilities originating in third-party plugins. Sandboxing is not implemented.
- We do not guarantee against problems arising from users' own operations (server configuration, password management, updates not applied).
- Fixes for versions that are out of support. Support period: [TBD]

### Boundary with freedom [Proposal]
- Users can relax protective settings on their own sites. When they do, a warning in the admin panel and a record in the audit log are mandatory.
- Whether to define items that cannot be disabled (e.g. signature verification of core updates) [TBD]

### What this means for each role
- **Core team**: Do not create parallel validation paths. Do not allow raw SQL.
- **Contributors / plugin developers**: Do not implement authentication token handling or permission checks yourself; use the core's mechanisms.
- **Users**: Applying updates is the user's responsibility.

### Related documents
SECURITY.md, PLUGIN-API, Security Manifest (brand site)

---

## 5. Fairness

### Definition
The same standards apply uniformly, and openly, to everyone in the same situation.

### What we promise
- Security fixes are released to AGPL users and future commercial licensees at the same time. [Proposal]
- We declare the areas the core team will not develop, leaving room for third parties to invest.
- The criteria for accepting contributions are published and applied uniformly. During v0.x, closing unsolicited code PRs without review is stated explicitly as part of those criteria.
- If advance notice or early access is offered, the criteria for choosing recipients are published. [Proposal]

### What we do not promise / boundaries
- We do not promise the same level of support to every user. When a commercial license is offered, the support it includes will be set out separately in its terms.
- We do not promise to accept every contribution. What we promise is that the criteria are public and applied uniformly.
- Whether to have feature differences in the core between the AGPL edition and a commercial edition [TBD] (for reference: a GitLab CE/EE-style split, or no feature difference with only exemption from AGPL obligations)

### Community rules [Proposal]

A code of conduct and rules for taking part in the community have not been set yet. They will be put in place gradually, as the community forms and grows.

**Expected stages (draft)**

| Stage | State of the community | What to put in place |
|---|---|---|
| 1. Now | Development is done by exc-D inc.; outside input arrives mainly as issues | Contribution acceptance policy (CONTRIBUTING), vulnerability reporting channel (SECURITY.md) |
| 2. More participants | Discussion and outside contributions arise on an ongoing basis | Code of conduct (CODE_OF_CONDUCT), a contact point for questions and reports, levels of response |
| 3. Participants join in running the project | There are maintainers outside exc-D inc. | Decision-making by more than one person, an appeals procedure |

**Principles for putting the rules in place**
- Fairness includes keeping a state in which every participant can take part with peace of mind under the same rules. For that reason, how we respond to conduct that does not follow the rules, or that harms the project, will also be defined as a fair procedure.
- Responses address conduct, not personal attributes.
- Standards of conduct and the responses to them are published in advance, and the same conduct receives the same response.
- Responses are proportionate to the seriousness of the conduct. The person concerned is told the reason and given a way to appeal.
- Responses extend only to participation in spaces the project runs and to the acceptance of contributions. They do not extend to the rights to use, modify, and redistribute the software under the AGPL. [Legal review]
- exc-D inc. currently both runs the project and makes these decisions. So that the fairness of decisions can be checked, reasons for decisions will be recorded and a way to appeal will be provided.

**Applies even before the rules are in place**
- **Threats to security are acted on immediately, whatever the stage.** (→ 3. Basic rule 1)
  - Examples: attempts to introduce malicious code, attempts to seize permissions or signing keys
- Closing PRs under the published acceptance policy (including closing unsolicited code PRs without review during v0.x) is a policy applied uniformly to everyone, not an action against an individual.

### What this means for each role
- **Core team**: Do not build exceptions into the criteria that favor ourselves.
- **Contributors**: The CLA applies to everyone on the same terms.
- **Users**: The level of security does not differ by the size of the organization or the type of contract.

### Related documents
CONTRIBUTING, CODE_OF_CONDUCT (to be created), CLA, LICENSE-EXCEPTIONS

---

## 6. Transparency

### Definition
The rules for what is disclosed, when, and what is not disclosed are themselves public. This does not mean disclosing everything.

### Two layers [TBD: whether both fall within this pillar]
- **Product transparency**: within a user's site, it is visible who changed what, when, and how (the audit log, `actor_source`, `is_ai_generated`, and so on)
- **Project transparency**: the Dixlase project's decision-making, business structure, and incident response are visible

### Three categories of disclosure [Proposal]

**Always public**
- Source code, the license, and the license exceptions
- The roadmap and the reasoning behind design decisions (ADRs)
- Release notes
- Business structure (what is free, what is paid, and where revenue comes from)
- Whether telemetry exists and what it sends
- Undecided matters are stated as undecided (e.g. community rules)

**Withheld for a limited time**
- Information about vulnerabilities before they are fixed. An advisory is published together with the fix release.
- The non-disclosure period is capped at 90 days; disclosure happens earlier whenever a fix is ready. [Proposal]
- If active exploitation is confirmed, disclosure happens early, with mitigations, even before a fix. [Proposal]
- Fixes are prepared in a private location (such as a GitHub Security Advisories private fork) and pushed to the public repository at the time of release. Pushing a fix to the public repository first would let the vulnerability be read from the diff. [Proposal]
- For reference: CERT/CC uses 45 days by default and Google Project Zero uses 90 days. In Japan, coordination is available through the Information Security Early Warning Partnership run by IPA and JPCERT/CC.

**Permanently withheld**
- Personal information about reporters, users, and customers
- Secrets such as signing keys
- The content of legal advice (though the fact that a legal review took place can be disclosed)
- Individual contract terms and the names of commercial licensees (unless they consent)
- Details of detection logic that would stop working if circumvented (e.g. health score detection signatures)

### Commitments when withholding information [Proposal]
Even when information is withheld, the following three points are disclosed as far as possible:
1. That withheld information exists
2. The reason it is withheld
3. The deadline, or the condition for release

### What we do not promise / boundaries
- We do not promise to publish every unsettled discussion during development in real time.
- How far to disclose the use of AI in the development process [TBD] (to be kept consistent with the CLA's AI clause and the thinking behind `is_ai_generated`)

### What this means for each role
- **Core team**: Decide whether to disclose according to these three categories.
- **Contributors**: Do not post vulnerabilities in public issues; report them through the channel in SECURITY.md.
- **Users**: Not disclosing a vulnerability before it is fixed is a procedure, not a lack of transparency.

### Related documents
SECURITY.md (response SLA and security.txt: [TBD]), PRIVACY-POLICY, release notes

---

## 7. Extensibility

### Definition
Features can be added without modifying the core, within limits that do not compromise security.

### What we promise
- Features can be added through published extension points (the plugin API).
- Extension points are put in place first; features are implemented later.
- Under the plugin exception clause, third-party plugin authors choose their own license. [Legal review: pending confirmation of changes to the exception clause structure]
- A compatibility policy for the public API (semantic versioning, the period from deprecation to removal) is published. Period: [TBD]

### What we do not promise / boundaries
- We do not promise compatibility for non-public internal implementation.
- During v0.x and beta, the public API may also change, with notice. [Proposal: should be stated explicitly]
- We do not promise to create an extension point for every request.
- We do not provide means of extension that compromise security (raw SQL, interfering with authentication).

### What this means for each role
- **Core team**: Change the API only after announcing a deprecation.
- **Plugin developers**: Use only the public API. Plugins that depend on internal implementation are not guaranteed to keep working after updates.
- **Users**: Choosing plugins that use only the public API makes them less likely to be affected by updates.

### Related documents
PLUGIN-API, LICENSE-EXCEPTIONS, API reference (planned for v1.0)

---

## 8. Sustainability

### Definition
The project endures over the long term, so users can depend on it with confidence.

### Three aspects [Proposal]
- **Business**: in addition to publication under the AGPL, a commercial license (annual subscription with a perpetual fallback license) is planned
- **Development**: development is currently done by a single company, exc-D inc., which is a single point of failure
- **Distribution**: distribution and releases do not stop because of an outage at a particular hosting provider

### What we promise
- The revenue model is published. [Proposal]
- Core security features are not made paid features for the sake of revenue. [Proposal]
- The policy for the case where the project can no longer continue is published in advance. [Proposal]
  - The source code remains available under the AGPL
  - Handling of signing keys, domains, and trademarks [TBD]

### What we do not promise / boundaries
- We do not promise indefinite support.
- We do not promise to provide any particular feature or plugin permanently.
- We do not promise free support (mutual help within the community is welcome).

### What this means for each role
- **Core team**: When scaling back another pillar on grounds of sustainability, publish the reason (→ 3. Basic rule 4).
- **Contributors**: Sustained contributions reduce the single point of failure in development.
- **Users**: Local governments and businesses can review the policy for discontinuation when deciding on long-term adoption.

### Related documents
LICENSE, commercial license terms (not yet written), distribution redundancy design

---

## 9. Freedom (apex)

### Definition
Users decide for themselves about their own sites, data, and operations, without depending on any particular provider.

### What we promise
- Freedom to self-operate: Dixlase can be self-hosted and run on-premises.
- Data ownership: site data belongs to the user and is not sent to Dixlase.
- Freedom to modify and redistribute: guaranteed within the terms of AGPL v3.
- Freedom to leave: users can take their data with them when they stop using Dixlase. Export format: [TBD]
- Less dependence on the distribution source: we aim for a structure in which updates do not stop because of an outage at a particular hosting provider.

### What we do not promise / boundaries
- We do not guarantee the consequences of exercising freedom. Problems caused by disabled protections or a modified core are outside the scope of support. [Proposal]
- Use of the name and logo is not included in this freedom; it is governed by TRADEMARK-POLICY.
- A commercial license is planned for users who want freedom from AGPL obligations. Those obligations are not waived free of charge.
- We do not promise to support uses that infringe on the freedom of others (such as infringing third-party rights).

### What this means for each role
- **Core team**: Avoid designs that create lock-in (data that can only be exported in a proprietary format, features that require a provider's servers).
- **Contributors**: Changes that narrow users' choices are not accepted unless a reason is given.
- **Users**: Freedom comes with operational responsibility (applying updates, the results of configuration changes).

### Related documents
LICENSE, LICENSE-EXCEPTIONS, TRADEMARK-POLICY, PRIVACY-POLICY

---

## 10. Maintaining these guidelines

- **Decision-maker**: currently exc-D inc. If the decision-maker changes, the change is recorded in this document. [Proposal]
- **Revisions**: revisions are recorded in the revision history. Changes that disadvantage users come with an advance notice period (→ 3. Basic rule 3). [Proposal]
- **Exceptions**: situations not covered by these guidelines are decided by the decision-maker; the reason is published after the decision and, where needed, reflected in this document. [Proposal]

---

## Appendix A. Open items

| # | Pillar | Item |
|---|---|---|
| 1 | General | Finalize the one-line definition of each pillar |
| 2 | General | Length of the advance notice period |
| 3 | Security | Protections that cannot be disabled |
| 4 | Security | Support period (fixes for older versions) |
| 5 | Security / Transparency | Response SLA in SECURITY.md and security.txt |
| 6 | Fairness | Whether the AGPL and commercial editions differ in features |
| 7 | Fairness | When and how to put community rules in place (code of conduct, levels of response, appeals) |
| 8 | Transparency | Scope of the pillar (product transparency, project transparency, or both) |
| 9 | Transparency | How far to disclose the use of AI in development |
| 10 | Extensibility | Period from deprecation to removal |
| 11 | Sustainability | Handling of signing keys, domains, and trademarks if the project cannot continue |
| 12 | Sustainability × Freedom | Whether to collect telemetry, and its default |
| 13 | Freedom | Data export format |
| 14 | Legal | Changes to the exception clause structure (pending legal review) |
| 15 | Legal | Relationship between community responses, license rights, and existing contributions |

## Revision history

| Version | Date | Changes |
|---|---|---|
| 0.1 | 2026-09-23 | Initial draft |
