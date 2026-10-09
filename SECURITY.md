# Security Policy

## Reporting a vulnerability

Please report security vulnerabilities to **security@panasaurus.dev** rather than
opening a public issue. Include a description, reproduction steps, and affected
versions. You can expect an initial response within 72 hours.

## Supported versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅        |

## Security notes

- The `/api/health` endpoint is intentionally public and only exposes service
  status — never configuration or data.
- Blueprint extensions run with the same privileges as the panel. Only install
  extensions from authors you trust; the built-in marketplace shows community
  extensions published on blueprint.zip.
- Admin account bootstrap credentials are printed once during installation and
  should be rotated after first login.
