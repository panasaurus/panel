# Security Policy

## Reporting a vulnerability

Please report security vulnerabilities to **security@panasaurus.com** rather than
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

## PHP runtime security update (2026-10-22)

CVE-2026-109618 and CVE-2026-109619 affect the PHP runtime. The minimum
supported patch releases are **8.3.36, 8.4.27, and 8.5.12**, respectively.
The production image and CI use PHP 8.4.27. Composer rejects older patch
releases; updating Composer packages alone does not update PHP.

Rebuild and recreate existing containers to replace every PHP-FPM and queue
worker. Persistent data must be retained:

```bash
# For a source checkout with a Compose build configuration:
docker compose build --pull panel
docker compose up -d --no-deps --force-recreate panel

# For deployments using the published image, after the patched release is available:
docker compose pull panel
docker compose up -d --no-deps --force-recreate panel

# Verify the runtime actually running in the replacement container:
docker compose exec -T panel php scripts/check-php-version.php
docker compose exec -T panel php -v
docker compose exec -T panel php-fpm -v
docker compose exec -T panel php artisan panasaurus:status
```

For native installations, update the PHP CLI and web server/FPM packages to a
fixed release and restart PHP-FPM, web servers, queue workers, and schedulers.
Vendor backports require confirmation from the package vendor. Disabling
session upload progress reduces an attack surface but does not replace the
runtime patch. Do not remove memory limits as a substitute for updating PHP.

Upstream advisories:
- https://github.com/php/php-src/security/advisories/GHSA-w8hw-w9fx-j79w
- https://github.com/php/php-src/security/advisories/GHSA-6wmf-4rv5-p3rp
