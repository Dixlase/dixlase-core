# Dixlase Copyright Policy

**Version:** 2.0
**Effective Date:** 2026-04-25

This Copyright Policy ("Policy") explains the licensing structure of the Dixlase project, operated by exc-D inc. ("exc-D"), and how rights in contributions to it are handled. It is a high-level stance document. The legally operative instruments are referenced in Section 5.

The Japanese version of this Policy is published as [COPYRIGHT-POLICY.ja.md](./COPYRIGHT-POLICY.ja.md). In case of any inconsistency between the English and Japanese versions, the **Japanese version shall prevail**.

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

  (a) the GNU Affero General Public License version 3 ("AGPL"), together with the Dixlase Plugin and Theme Exception, as set out in [`LICENSE`](./LICENSE); and

  (b) a separate commercial license offered by exc-D, as set out in [`LICENSE.commercial`](./LICENSE.commercial), for parties who do not wish to comply with the AGPL.

Both licenses cover the same software; they differ only in obligations.

## 3. Plugin and Theme Exception

The Plugin and Theme Exception is defined in [`LICENSE`](./LICENSE) and bounded by [`PLUGIN-API.md`](./PLUGIN-API.md). Authors of plugins and themes that satisfy the four-condition test in the Exception retain full copyright in their plugin/theme code and may distribute it under any license of their choice, including proprietary licenses. The Exception is one-way: it does not allow modified core code to be re-characterized as a plugin to escape the AGPL.

## 4. Contribution Model

Contributions to the Dixlase **core repository** at https://github.com/Dixlase/dixlase-core are governed by a Contributor License Agreement ("CLA") model:

| Contributor type | Agreement |
|---|---|
| Individual person | [`CLA-INDIVIDUAL.md`](./CLA-INDIVIDUAL.md) |
| Organization (covering its employees) | [`CLA-CORPORATE.md`](./CLA-CORPORATE.md) |

Under the CLA model:

- Contributors **retain ownership** of their contributions
- Contributors **grant exc-D** a perpetual, worldwide, non-exclusive, no-charge, royalty-free, irrevocable, sublicensable license sufficient to support the dual licensing model in Section 2
- Contributors agree not to assert moral rights in a way that would prevent the exercise of that license
- Contributors confirm authority to grant the license (employer permission, original creation, third-party material disclosure)

This Policy applies to contributions to the **core repository**. Plugins and themes distributed separately are outside its scope (see Section 3).

## 5. Operative Legal Instruments

The legally operative documents are:

| Layer | Document |
|---|---|
| Open-source license (downstream recipients) | [`LICENSE`](./LICENSE) — AGPL v3 + Plugin and Theme Exception |
| Commercial license (downstream recipients) | [`LICENSE.commercial`](./LICENSE.commercial) |
| Individual contributor agreement | [`CLA-INDIVIDUAL.md`](./CLA-INDIVIDUAL.md) |
| Corporate contributor agreement | [`CLA-CORPORATE.md`](./CLA-CORPORATE.md) |
| Plugin API boundary | [`PLUGIN-API.md`](./PLUGIN-API.md) |

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

## 8. Migration Notice (CAA → CLA)

Versions 1.0 through 1.2 of this Policy operated under a Contributor Assignment Agreement (CAA) model, in which the economic copyright in each contribution was assigned to exc-D. With v0.1.0 of Dixlase, the project migrated to the Contributor License Agreement (CLA) model described in Section 4.

- Contributions **accepted before v0.1.0** remain governed by the CAA terms in effect at the time they were submitted; those rights stay with exc-D as previously assigned.
- Contributions **on or after v0.1.0** are governed by the CLA.
- The current CLA is an **interim version** pending formal legal review; see the notice at the top of [`CLA-INDIVIDUAL.md`](./CLA-INDIVIDUAL.md) and [`CLA-CORPORATE.md`](./CLA-CORPORATE.md).

This migration was made to lower contribution friction, align with the global OSS norm (Apache, Eclipse, OpenStack, jQuery, LibreOffice all use CLA-style models), and improve compatibility with international jurisdictions where outright copyright assignment is restricted.

## 9. Changes to This Policy

exc-D may update this Policy from time to time. Substantive changes will be announced publicly through the project repository before they take effect. Changes to operative legal instruments (CLA, LICENSE files) follow their own versioning and notification rules.

## 10. Governing Law and Jurisdiction

This Policy is governed by the laws of Japan. Any disputes arising out of or in connection with this Policy shall be submitted to the exclusive jurisdiction of the Tokyo District Court as the court of first instance.

## 11. Contact

For questions about this Policy, contact exc-D at:

- Email: office@exc-d.com
- Website: https://exc-d.com

---

**This Policy summarizes the licensing structure of Dixlase as of the version stated above. The operative legal instruments listed in Section 5 control in case of conflict.**
