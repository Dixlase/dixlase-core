# Development Guide
Technical documentation for developers building on or extending Dixlase.

## API Reference

Event system, translation, and webhook specifications.

- [API Reference](api-reference/)

## Authentication

Login identifier verification and lockout mechanisms.

- [Authentication](auth/)

## Components

UI component usage guides and styling conventions.

- [Components](components/)

## Issues

How to write, label and close GitHub issues, and how they link to releases.

- [Issue Guidelines](issues.md)

## Revisions

Shared revision API: Revisionable contract, HasRevisions trait,
RevisionService, diff presenter and blade components.

- [Revisions](revisions.md)

## Members

Role-based access control and permission system design.

- [Members](members/)

## Plugins

Plugin permission framework and development guidelines.

- [Plugins](plugins/)

## Security

CSP coding rules and security settings registry.

- [Security](security/)

## System

API signatures, backup, and deployment systems.

- [System](system/)

## Two-Factor Authentication

2FA architecture and UI component specifications.

- [Two-Factor Authentication](two-factor/)

## Other

- [Action Layer](action-layer.md) - AbstractAction lifecycle (`authorize → validate → handle → audit → events`), `ActionResult` metadata schema, and why actions are the only audit producer
- [Supply-Chain Defense Data Layer](supply-chain.md) - Plugin / theme / core version-history tables, the slug-keyed (no-FK, no-CASCADE) retention policy frozen for `^0.1`, and how change-flag fields surface supply-chain attacks
- [Public Identifier Naming Conventions](naming.md) - Naming format for API scopes, permission keys, event names, webhook event types, audit log actions, plugin capabilities, etc.
- [Reserved Extension Points](extension-points.md) - Phase 1 reserved hooks for the Zero Trust roadmap (SecretProvider, RiskEvaluator, PolicyEvaluator, auth.iap, auth.mtls)
- [Cache Key Convention](cache-key-convention.md) - Naming convention and Builder helper for cache keys
- [SDK Trait Dependencies](sdk-trait-dependencies.md) - SDK trait dependency analysis
