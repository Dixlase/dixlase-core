# Security Policy

The Dixlase team takes security seriously. Dixlase is designed with a "security-first" philosophy, and we rely on our community and security researchers to help us maintain that standard.

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

You can submit a vulnerability report through either of the following private channels:

- **GitHub Private Vulnerability Reporting (preferred)** — [Open a security advisory](https://github.com/Dixlase/dixlase-core/security/advisories/new). Submissions are protected by HTTPS and visible only to maintainers.
- **Email:** security@exc-d.com

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

The PGP key will be published on this page once generated. Until then, **GitHub Private Vulnerability Reporting** is the recommended encrypted alternative — submissions are protected by HTTPS and accessible only to maintainers. Email encryption is also acceptable if supported by your mail provider.

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

## Plugin Security Model

Dixlase manages plugin and theme risk through a **defense-in-depth model**, not a runtime sandbox. We are explicit about this distinction so that operators, plugin authors, and security researchers can evaluate the actual security posture without ambiguity.

### What Dixlase provides

The following layers are combined to reduce plugin risk:

1. **Capability declarations** — every plugin must declare its required permissions in `plugin.json` (`database`, `storage`, `settings`, `members`, `mail`, `content`, `system`).
2. **Static analysis** — at install / scan time, Dixlase compares declared permissions against actual code patterns (e.g. `DangerousApiPattern`, `DatabaseDetectionPattern`) and detects undeclared usage and dangerous-API calls (`exec`, `eval`, etc.).
3. **Signature verification** — Ed25519-based verification of plugin authenticity through `SignatureVerifierInterface`.
4. **Health scoring** — every plugin receives a 0–100 score derived from signature, declared-permission consistency, CSP compliance, and dangerous-API detection. Activation policy is gated by score and critical-issue flags (`Allowed` / `Warning` / `Acknowledge` / `Blocked`).
5. **Distribution-source provenance** — official sources are declared and may be cryptographically signed; plugin downloads pass through `ExtensionSourceManager`.
6. **Convention-based separation** — namespaces (`Plugins\<Name>\*`) and table prefixes (`dls_plg_{slug}_*`) keep plugin code and data identifiable; core tables are read-only by convention.
7. **Operational kill switches** — `BlockPluginRoutes` middleware and the per-site activation table allow operators to disable plugins at the edge without uninstalling them.

### What Dixlase does NOT provide

We want to be unambiguous about the gaps so that operators do not over-trust the model:

- **No process-, OS-, VM-, or language-runtime-level isolation.** Plugins execute in-process within the same PHP-FPM worker as the core. A plugin that ignores Dixlase conventions and calls a core class directly will succeed at runtime; the violation is detected by static scanning at install time, not blocked at execution time.
- **No memory isolation** between plugin code and core code.
- **Filesystem and network access are not jailed** by default — only declared via `plugin.json` and detected by static analysis.
- **No CPU / memory quotas** on plugin code execution.
- **`PluginPermissionService::enforce()` is opt-in by core call sites**, not a global runtime interceptor.

These limitations are inherent to single-process PHP CMSs and apply equally to WordPress, Drupal, and other Laravel-based CMSs. They are not unique gaps in Dixlase.

### Forward direction

We will explore a range of approaches over future releases and progressively strengthen plugin isolation. Candidates under consideration include subprocess-level separation with `disable_functions` / `open_basedir` restrictions, and language-runtime-level isolation via WebAssembly. We are intentionally not pre-committing to a specific implementation path until the relevant ecosystems (e.g. `wasmer-php`, WASI Component Model) reach production readiness.

### Operational guidance

For operators running Dixlase in security-sensitive environments:

- Enable the **"Require signed plugins"** option in **Admin → Settings → Security → Extensions**.
- Set the plugin / theme **maximum health-level** to the strictest tier acceptable for your deployment.
- Install plugins only from sources you trust; the static scanner is a safety net, not a guarantee.
- Subscribe to security advisories for the plugins you install (each plugin maintainer is responsible for their own advisories).

## Reverse-proxy / IAP deployment

Dixlase reads `X-Forwarded-*` headers only from upstream IPs declared in `TRUSTED_PROXIES`. This is security-critical: if an untrusted IP is allowed to set `X-Forwarded-For`, an attacker on the public internet can spoof their source IP and bypass admin IP allow-lists.

### Default posture

Out of the box, `TRUSTED_PROXIES` is **unset** and Dixlase trusts no proxies. `$request->ip()` returns the actual TCP source. Forwarded headers from any upstream are ignored. This is the safe default for operators running Dixlase on a public IP without a reverse proxy.

### Required configuration when running behind a proxy / CDN / IAP

If Dixlase is deployed behind any of:

- A CDN (Cloudflare, Cloudfront, Fastly, …)
- A reverse proxy (Nginx, Traefik, Envoy, HAProxy, …)
- An Identity-Aware Proxy (Cloudflare Access, Google IAP, Pomerium, Zscaler, …)
- A managed load balancer (AWS ALB, GCP HTTPS LB, …)

…you **must** populate `TRUSTED_PROXIES` in `.env` with the upstream IPs or CIDR ranges. Otherwise:

- `$request->ip()` returns the upstream's IP, not the real client IP.
- HTTPS detection (`FORCE_SSL`, `HSTS`, secure cookies) breaks because `X-Forwarded-Proto` is ignored.
- Admin IP allow-lists become a no-op (every request looks like it comes from the same upstream).
- Audit logs lose source-IP fidelity.

### Worked example: Cloudflare

Cloudflare publishes its IP ranges at <https://www.cloudflare.com/ips/>. As of writing the IPv4 list is:

```
TRUSTED_PROXIES=173.245.48.0/20,103.21.244.0/22,103.22.200.0/22,103.31.4.0/22,141.101.64.0/18,108.162.192.0/18,190.93.240.0/20,188.114.96.0/20,197.234.240.0/22,198.41.128.0/17,162.158.0.0/15,104.16.0.0/13,104.24.0.0/14,172.64.0.0/13,131.0.72.0/22
```

Operators should source the current list from Cloudflare directly and refresh on a cadence (Cloudflare adds ranges occasionally).

### Worked example: single internal reverse proxy

Single Nginx in front of Dixlase, running on `10.0.0.5`:

```
TRUSTED_PROXIES=10.0.0.5
```

Or trust the whole internal subnet:

```
TRUSTED_PROXIES=10.0.0.0/8
```

### `*` is for development only

`TRUSTED_PROXIES=*` disables the upstream authenticity check and trusts forwarded headers from any source. This is acceptable in local Docker development where every reachable upstream is the operator's machine, but **never** in a deployment exposed to a network the operator does not control.

### Admin IP allow-list is supplementary

The admin IP allow-list (`Admin → Settings → Security → IP`) is a defense-in-depth layer, not a sole admin protection. Even with `TRUSTED_PROXIES` correctly set, IP allow-listing is brittle (mobile networks, ISP NAT, working from a new location). The recommended deployment pattern is:

1. Identity-Aware Proxy (Cloudflare Access, Google IAP, …) as the primary admin gate.
2. Strong authentication (WebAuthn / TOTP) inside Dixlase as the second gate.
3. IP allow-list as an additional optional layer for high-risk environments.

A separate `docs/operations/iap.md` operator guide will be published as IAP integration plugins land in v0.2+. Until then the configuration above is sufficient.

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
