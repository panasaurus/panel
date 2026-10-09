# Changelog

All notable changes to Panasaurus are documented here. The project follows
[Semantic Versioning](https://semver.org).

## v1.0.0 — Initial Panasaurus release

Panasaurus is a rebranded, modernized panel built on Pyrodactyl v5.0.6 and the
Pterodactyl panel, with the Blueprint extension framework built in.

### Added
- **Blueprint extension framework, pre-installed** — full PHP backend, `panasaurus`
  /`blueprint` CLI, admin extension manager, React 19 extension mount points,
  migrations, and seeder. Extensions made for Blueprint/Pterodactyl work as-is.
- **Extension Marketplace** (`/admin/extensions/marketplace`) — live directory of the
  Blueprint ecosystem with search, filters, and install instructions; cached in-app.
- **Public health endpoint** (`GET /api/health`) — database/redis/storage checks with
  latency, suitable for uptime monitors and container healthchecks.
- **Panasaurus CLI** — `php artisan panasaurus:info` and `php artisan panasaurus:status`.
- **Docker-first install** — rewritten multi-stage image (Node 22 build → PHP 8.4 runtime),
  tuned OPcache JIT preloading, nginx + PHP-FPM + supervisor stack, queue worker and
  scheduler built in, container healthcheck, `install.sh` one-command installer.
- **CI/CD** — GitHub Actions for frontend build, backend tests, code style, and image
  releases to ghcr.io/panasaurus/panel.
- Custom Panasaurus dinosaur brand mark, favicons, and emerald theme.

### Changed
- Full rebrand: Pyrodactyl → Panasaurus across config, UI, mail, and docs.
- Brand palette moved from coral to Panasaurus emerald (#23d18d).
- Blueprint CLI adapted for the Panasaurus runtime (pnpm + Vite instead of yarn + webpack,
  nginx ownership, pre-installed state).

### Compatibility
- The `Pterodactyl\` PHP namespace is preserved — Blueprint extensions that reference
  Pterodactyl classes continue to work unchanged.
- Blueprint settings keys (`blueprint::…`), migrations, and CLI commands are fully
  compatible with upstream.
