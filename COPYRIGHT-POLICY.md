# Dixlase Copyright Policy

For Japanese, see [COPYRIGHT-POLICY.ja.md](./COPYRIGHT-POLICY.ja.md). In case of any inconsistency between the English and Japanese versions, the **Japanese version shall prevail**.

**Version:** 1.0
**Effective Date:** 2026-04-25

This Copyright Policy ("Policy") explains the licensing structure of the Dixlase project, operated by exc-D inc. ("exc-D"), and how rights in contributions to it are handled. It is a high-level stance document. The legally operative instruments are referenced in Section 5.

---

## 1. Purpose

This Policy describes:

- The dual licensing model under which Dixlase core is distributed
- The boundary between the Dixlase core and plugins/themes
- The contributor agreement model used for the core
- The principles that govern future arrangements

It is not itself a contract. The contractual instruments are listed in Section 5; in case of any conflict between this Policy and an operative instrument, the operative instrument prevails.

## 2. Dual Licensing Model

The Dixlase core is distributed under two parallel licenses, and recipients choose one:

(a) the GNU Affero General Public License version 3 ("AGPL", as set out in [`LICENSE`](./LICENSE)), together with the Dixlase Plugin and Theme Exception, as set out in [`LICENSE-EXCEPTIONS`](./LICENSE-EXCEPTIONS); and

(b) a separate commercial license offered by exc-D, as set out in [`LICENSE-COMMERCIAL`](./LICENSE-COMMERCIAL), for parties who do not wish to comply with the AGPL.

Both licenses cover the same software; they differ only in obligations.

> **Note on commercial-license availability.** The commercial-license framework documented in [`LICENSE-COMMERCIAL`](./LICENSE-COMMERCIAL) is in place, but commercial terms (pricing and contract format) are still being finalized. Inquiries can be directed to info@dixlase.org.

## 3. Plugin and Theme Exception

The Plugin and Theme Exception is defined in [`LICENSE-EXCEPTIONS`](./LICENSE-EXCEPTIONS) and bounded by [`PLUGIN-API.md`](./PLUGIN-API.md). Authors of plugins and themes that satisfy the four-condition test in the Exception retain full copyright in their plugin/theme code and may distribute it under any license of their choice, including proprietary licenses. The Exception is one-way: it does not allow modified core code to be re-characterized as a plugin to escape the AGPL.

## 4. Contribution Model

Contributions to the Dixlase **core repository** at https://github.com/Dixlase/dixlase-core are governed by a Contributor License Agreement ("CLA") model:

| Contributor type                      | Agreement                                                                   |
| ------------------------------------- | --------------------------------------------------------------------------- |
| Individual person                     | [`CLA.md`](./CLA.md) (signing capacity: individual)                         |
| Organization (covering its employees) | [`CLA.md`](./CLA.md) (signing capacity: entity; complete Schedules A and B) |

Under the CLA model:

- Contributors **retain ownership** of their contributions
- Contributors **grant exc-D** a perpetual, worldwide, non-exclusive, no-charge, royalty-free, irrevocable, sublicensable license sufficient to support the dual licensing model in Section 2
- Contributors agree not to assert moral rights in a way that would prevent the exercise of that license
- Contributors confirm authority to grant the license (employer permission, original creation, third-party material disclosure)

This Policy applies to contributions to the **core repository**. Plugins and themes distributed separately are outside its scope (see Section 3).

> **Current operating policy.** Dixlase is **not currently accepting external pull requests**. The CLA framework above will be activated when external code contributions open. The timing of opening will be decided, with a finalized CLA as a prerequisite, based on the stability of the core API and operational experience after the initial release. See [`CONTRIBUTING.md`](./CONTRIBUTING.md) for the contributions currently being welcomed (Issue-based bug reports, Discussions, etc.).

## 5. Operative Legal Instruments

The legally operative documents are:

| Layer                                            | Document                                                                                                     |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------------------------ |
| Open-source license (downstream recipients)      | [`LICENSE`](./LICENSE) (AGPL v3) + [`LICENSE-EXCEPTIONS`](./LICENSE-EXCEPTIONS) (Plugin and Theme Exception) |
| Commercial license (downstream recipients)       | [`LICENSE-COMMERCIAL`](./LICENSE-COMMERCIAL)                                                                 |
| Contributor agreement (individual and corporate) | [`CLA.md`](./CLA.md)                                                                                         |
| Plugin API boundary                              | [`PLUGIN-API.md`](./PLUGIN-API.md)                                                                           |

This Policy is a stance summary, not a contract. Where its summary statements differ from an operative document, the operative document controls.

## 6. Contributor Recognition

Authorship is recognized in the project's commit history, release notes, and contributor acknowledgements regardless of which agreement form a contributor signs. The CLA preserves the contributor's right to be identified as the author of their contributions; see CLA Section 11 for moral rights handling.

## 7. Future Governance of Dixlase

exc-D currently operates and manages Dixlase. exc-D does not insist on holding Dixlase as a permanent private asset. Depending on the growth of the project, the development of the community, and public-interest considerations that may emerge, exc-D may consider various options for future governance, including but not limited to:

1. Continued operation by exc-D
2. Transition to a more distributed, multi-stakeholder governance structure (e.g. a certified partner program)
3. Transfer of management and/or rights to a non-profit organization or other public-interest entity
4. Opening governance through measures such as establishing a board that includes community representatives
5. Hybrid structures combining the above
6. Other arrangements deemed appropriate

At this time, no specific plan or commitment exists regarding any of these options, and this Policy does not create any obligation to carry out any particular transition. The CLA model used in Section 4 is designed to enable such transitions without re-papering existing contributors: the license grant runs to "exc-D inc. **and its successors**" (see CLA Section 4), so that a successor entity can continue distribution under the same terms.

Regardless of which arrangement is adopted, exc-D will endeavor to respect the following principles:

- Continued distribution as free software under the AGPL v3
- Transparent communication with the community
- Advance notice of significant governance changes

## 8. Changes to This Policy

exc-D may update this Policy from time to time. Substantive changes will be announced publicly through the project repository before they take effect. Changes to operative legal instruments (CLA, LICENSE files) follow their own versioning and notification rules.

## 9. Governing Law and Jurisdiction

This Policy is governed by the laws of Japan. Any disputes arising out of or in connection with this Policy shall be submitted to the exclusive jurisdiction of the Osaka District Court as the court of first instance.

## 10. Contact

For questions about this Policy, contact exc-D at:

- Email: info@dixlase.org
- Project website: https://dixlase.org
- Operating company: exc-D inc. <https://exc-d.com>

---

**This Policy summarizes the licensing structure of Dixlase as of the version stated above. The operative legal instruments listed in Section 5 control in case of conflict.**
