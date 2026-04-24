# Security Policy

The Dixlase team takes security seriously. Dixlase is designed with a "security-first" philosophy, and we rely on our community and security researchers to help us maintain that standard. For the Japanese version, see [SECURITY.ja.md](./SECURITY.ja.md).

## Supported Versions

Security updates are provided for the following versions:

| Version | Supported |
|---------|-----------|
| Latest stable release | ✅ |
| Previous stable release | ✅ (for 6 months after the next major release) |
| Older releases | ❌ |

During the early development phase, only the latest development version is supported. This section will be updated as stable releases become available.

## Reporting a Vulnerability

**Please do not report security vulnerabilities through public GitHub issues, discussions, or pull requests.**

### How to Report

Send vulnerability reports to:

**Email:** security@exc-d.com

Please include as much of the following information as possible:

- Type of vulnerability (e.g., SQL injection, XSS, authentication bypass)
- Affected version(s) of Dixlase
- Step-by-step instructions to reproduce the issue
- Proof-of-concept code or screenshots (if applicable)
- Potential impact of the vulnerability
- Any suggested mitigation or fix

### PGP / GPG Encryption (Optional)

For highly sensitive reports, you may encrypt your report using our PGP key:

- **Fingerprint:** *(To be published)*
- **Public key:** *(To be published)*

The PGP key will be published on this page once generated. In the meantime, please use email encryption if supported by your mail provider.

## What to Expect

We aim to acknowledge all security reports promptly and handle them responsibly.

Our process is as follows:

1. **Acknowledgment** — We confirm receipt once we have seen the report
2. **Initial Assessment** — We reproduce the issue and assess impact and severity
3. **Remediation Plan** — For confirmed issues, we develop a fix strategy
4. **Patch Release** — We release a patch as promptly as severity and complexity allow
5. **Public Disclosure** — Coordinated with the reporter, after the patch is available

For critical vulnerabilities actively exploited in the wild, we will work to release a patch as quickly as possible.

## Responsible Disclosure Policy

We practice coordinated disclosure:

1. Report the vulnerability privately to us via the channels above
2. We confirm receipt and begin investigation
3. We develop, test, and release a patch
4. We publicly disclose the vulnerability after the patch is available, giving users time to update
5. We credit the reporter in our security advisory (unless the reporter wishes to remain anonymous)

## Scope

### In Scope

- The Dixlase CMS core (this repository)
- Official Dixlase-maintained plugins and themes
- Dixlase infrastructure (dixlase.com and related official domains)

### Out of Scope

- Third-party plugins and themes not maintained by exc-D inc.
- User-deployed instances of Dixlase (we may help mediate, but the instance operator is responsible)
- Issues in dependencies already disclosed and patched upstream
- Social engineering attacks against Dixlase staff or users
- Physical security issues
- Denial-of-service attacks via resource exhaustion (without a novel vulnerability)

## Safe Harbor for Security Research

Dixlase supports good-faith security research. We will not pursue legal action against researchers who:

- Make a good-faith effort to avoid privacy violations, data destruction, and service disruption
- Only interact with accounts they own or have explicit permission to access
- Do not exploit vulnerabilities beyond what is necessary to confirm their existence
- Do not publicly disclose vulnerabilities before we have had a reasonable opportunity to respond
- Comply with all applicable laws

## Recognition

With the reporter's permission, we will publicly credit those who responsibly disclose security vulnerabilities in:

- The security advisory for the vulnerability
- Our project's security acknowledgments page *(to be published)*
- Release notes for the patched version

## Security Updates

To stay informed about security updates:

- Watch this repository for security advisories on GitHub
- Follow the Dixlase official channels (linked in [README.md](./README.md))
- Subscribe to the security mailing list *(to be launched)*

## Contact

For general security questions (non-vulnerability-related):

- Email: security@exc-d.com
- Website: https://dixlase.com/security *(planned)*

---

Thank you for helping keep Dixlase and its users safe.
