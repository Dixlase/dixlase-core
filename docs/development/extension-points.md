# Reserved Extension Points

> **Status**: Phase 1 reservations are stable. Default implementations are intentionally no-ops; production behaviour ships in later phases of the Zero Trust roadmap (`.backlog/zero-trust-roadmap.md`).
>
> **Audience**: plugin authors and integrators preparing to deliver Zero Trust features (managed secret stores, conditional access, ABAC, IAP integration, mTLS / device trust).

## Why these are reserved

The Zero Trust roadmap calls for several capabilities that are out of scope for the initial release but whose **public surface** must be stable from beta onwards. By reserving the interfaces and middleware aliases up front:

- Plugin authors can build against a frozen contract today.
- Default Dixlase behaviour does not change (every default is a deliberate no-op).
- Future Phase 2 / Phase 3 work plugs in via the existing hooks instead of introducing new public surface that competes with what plugins have already targeted.

Each reservation freezes the **shape** of the contract (method signatures, return types, enum cases) under the same compatibility policy as the rest of the Plugin API — see [`PLUGIN-API.md`](../../PLUGIN-API.md#stability-pledge).

## Quick reference

| ID | Reservation | Default | Replace via |
|---|---|---|---|
| C-1 | [`App\Contracts\Security\SecretProviderInterface`](#c-1-secretproviderinterface) | `EnvSecretProvider` (reads `.env`) | Service-container rebind |
| C-2 | [`App\Contracts\Security\RiskEvaluatorInterface`](#c-2-riskevaluatorinterface) | `LowRiskEvaluator` (always low) | Service-container rebind |
| C-3 | [`App\Contracts\Security\PolicyEvaluatorInterface`](#c-3-policyevaluatorinterface) | `NullPolicyEvaluator` (always Defer) | Service-container rebind |
| C-4 | `auth.iap` middleware alias | `AuthenticateIap` (aborts 501) | Replace `App\Http\Middleware\AuthenticateIap` |
| C-5 | `auth.mtls` middleware alias | `AuthenticateMtls` (aborts 501) | Replace `App\Http\Middleware\AuthenticateMtls` |

---

## C-1. SecretProviderInterface

**Contract**: `App\Contracts\Security\SecretProviderInterface`
**Default implementation**: `App\Services\Security\EnvSecretProvider`
**Container binding**: `AppServiceProvider::register()`

### Signature

```php
namespace App\Contracts\Security;

interface SecretProviderInterface
{
    public function get(string $key, ?string $default = null): ?string;
}
```

### Default behaviour

Reads from the local `.env` via Laravel's `env()` helper. Returns `null` for unknown keys (or the supplied `$default`).

### Why it exists

Production deployments will ultimately need rotated, KMS-backed secrets (database credentials, signing keys, API tokens) rather than long-lived `.env` values. Phase 3.9 of the Zero Trust roadmap covers Vault / AWS KMS / GCP Secret Manager integrations. By reserving the interface now, those integrations slot in via container rebind without touching call sites.

### Implementer requirements

- Return `null` when the key is unknown rather than throwing.
- Return the supplied `$default` when the key is unknown and a default is provided.
- Treat the key as opaque; no quoting, no parsing.
- Be safe to call repeatedly (callers may not cache).

### Example replacement (sketch)

```php
namespace MyPlugin\Services;

use App\Contracts\Security\SecretProviderInterface;

final class VaultSecretProvider implements SecretProviderInterface
{
    public function get(string $key, ?string $default = null): ?string
    {
        // Talk to Vault, return value or $default
    }
}

// In the plugin's service provider:
$this->app->bind(SecretProviderInterface::class, VaultSecretProvider::class);
```

---

## C-2. RiskEvaluatorInterface

**Contract**: `App\Contracts\Security\RiskEvaluatorInterface`
**Input DTO**: `App\DTO\Security\LoginContext`
**Output DTO**: `App\DTO\Security\RiskScore` (carrying `App\Enums\AccessRiskLevel`)
**Default implementation**: `App\Services\Security\LowRiskEvaluator`
**Container binding**: `AppServiceProvider::register()`

### Signature

```php
namespace App\Contracts\Security;

use App\DTO\Security\LoginContext;
use App\DTO\Security\RiskScore;

interface RiskEvaluatorInterface
{
    public function evaluate(LoginContext $context): RiskScore;
}
```

### Default behaviour

Returns `RiskScore::low()` unconditionally. No conditional access policy is enforced out of the box.

### Why it exists

Phase 3.1 of the Zero Trust roadmap adds risk scoring on top of the signals already collected by `LoginBehaviorService` (location change, new device fingerprint, off-hours, ASN drift). Risk scores will gate step-up authentication, session lifetime decisions, and hard-deny rules. Reserving the contract now lets call sites take a dependency on the interface today; the default low-risk evaluator preserves current behaviour.

### Implementer requirements

- Be deterministic for the same input.
- Be safe to call repeatedly during a single request.
- Do not throw on missing context fields; missing fields are signals.
- Treat the call as read-only — no DB writes, no audit log entries (the caller does those).

### Forward compatibility

`LoginContext` may grow new fields in future minor versions (additive only). Implementers should access fields by name via the public properties; `match` / `switch` on a closed set is fine but should always have a fallthrough that handles unrecognised values as a low-information signal rather than throwing.

---

## C-3. PolicyEvaluatorInterface

**Contract**: `App\Contracts\Security\PolicyEvaluatorInterface`
**Decision enum**: `App\Enums\PolicyDecision` (`Allow` / `Deny` / `Defer`)
**Default implementation**: `App\Services\Security\NullPolicyEvaluator`
**Container binding**: `AppServiceProvider::register()`

### Signature

```php
namespace App\Contracts\Security;

use App\Enums\PolicyDecision;
use Illuminate\Contracts\Auth\Authenticatable;

interface PolicyEvaluatorInterface
{
    public function evaluate(
        ?Authenticatable $actor,
        string $action,
        ?object $resource,
        array $context,
    ): PolicyDecision;
}
```

### Default behaviour

Returns `PolicyDecision::Defer` for every input. Authorisation falls through to the existing role-based check in `PermissionService`.

### Why it exists

Phase 3.6 adds Attribute-Based Access Control on top of RBAC ("editors may publish only during business hours from a trusted IP range", "admins cannot delete users from a country flagged in the risk engine", external OPA delegation, …). The three-value decision enum (`Allow` / `Deny` / `Defer`) lets a policy short-circuit when it has an opinion and stay out of the way otherwise.

### Caller semantics

```
PolicyDecision::Allow  → caller short-circuits and allows
PolicyDecision::Deny   → caller short-circuits and denies
PolicyDecision::Defer  → caller continues with the standard RBAC check
```

The default `Defer` is what guarantees ABAC adoption is non-breaking: every existing call site behaves exactly as before until an integration plugin starts returning `Allow` / `Deny`.

### Implementer requirements

- Be deterministic for the same input.
- Be safe to call repeatedly during a single request.
- Do not throw on missing context fields.
- Read-only: no DB writes, no audit log entries (the caller — typically `PermissionService::evaluate()` — is responsible).
- Return `PolicyDecision::Defer` whenever no policy applies.

---

## C-4. `auth.iap` middleware alias

**Alias**: `auth.iap`
**Default class**: `App\Http\Middleware\AuthenticateIap`
**Aliased in**: `bootstrap/app.php`

### Default behaviour

`abort(501, ...)` with a message instructing the operator to install an IAP integration plugin or rebind the class.

### Why it exists

Phase 3.7 of the Zero Trust roadmap delivers integration plugins for Cloudflare Access JWT verification, Google IAP, Pomerium, and similar Identity-Aware Proxies. By reserving `auth.iap` as a public alias today, plugin authors can write the same `Route::middleware('auth.iap')` regardless of the upstream IAP, and operators can switch IAPs without rewriting routes.

### Replacement pattern

A plugin replaces `App\Http\Middleware\AuthenticateIap` (typically by binding it in the service container or merging configuration) with an implementation that:

1. Reads the IAP-issued JWT or signed assertion from the appropriate header (`Cf-Access-Jwt-Assertion`, `X-Goog-IAP-JWT-Assertion`, …).
2. Verifies the signature against the IAP's published public keys.
3. Resolves the asserted identity to a Dixlase member and binds it to the auth manager.
4. Calls `$next($request)` only after the identity is established.

### Operator note

Applying `auth.iap` to a route in a build that has no IAP plugin installed is a configuration error. The 501 abort makes that error visible early instead of failing open.

---

## C-5. `auth.mtls` middleware alias

**Alias**: `auth.mtls`
**Default class**: `App\Http\Middleware\AuthenticateMtls`
**Aliased in**: `bootstrap/app.php`

### Default behaviour

`abort(501, ...)` with a message instructing the operator to install a client-certificate / device-trust plugin or rebind the class.

### Why it exists

Phase 3.3 (device registration / client certificate authentication) and Phase 3.8 (mTLS for outbound webhooks) both depend on a verified-client-certificate flow. Some operators will want admin routes gated on a certificate issued by the operator's CA; some will want mTLS for service-to-service traffic. The `auth.mtls` alias is the route-level entry point.

### Replacement pattern

A typical implementation reads cert subject / issuer from headers populated by the upstream proxy (`X-SSL-Client-S-DN`, `X-SSL-Client-Verify`, `X-SSL-Client-Cert`, …), confirms the verification status, and rejects the request unless the certificate chains to a trusted CA and matches a registered device.

### Operator note

Same as `auth.iap`: applying `auth.mtls` without a backing implementation is a configuration error and the 501 abort surfaces it.

---

## Compatibility policy

Each item above is part of the Plugin API stability surface. Within a major version:

- New methods may be added (additive only).
- New optional parameters with defaults may be added.
- New enum cases may be added (consumers should always have a fallthrough).
- New properties on DTOs may be added.

Renames, type changes, or removals follow the [Plugin API deprecation policy](../../PLUGIN-API.md#deprecation-policy): one-minor-cycle deprecation, removed in the next major.

## Naming conventions

Identifier names used here follow the rules in [`docs/development/naming.md`](naming.md):

- Permission keys passed to `PolicyEvaluatorInterface::evaluate()`'s `$action` argument: dot-path lower_snake_case.
- Configuration env keys for plugin-supplied implementations: `SCREAMING_SNAKE_CASE`.

## See also

- [`PLUGIN-API.md`](../../PLUGIN-API.md) — Plugin API stability pledge and deprecation policy
- `.backlog/zero-trust-roadmap.md` (maintainer-only) — phased plan that drives these reservations
- [`SECURITY.md`](../../SECURITY.md) — operator-facing security guidance (IAP / TrustProxies)
- [`docs/development/naming.md`](naming.md) — public identifier naming conventions
