## What is Panasaurus?

Panasaurus is a game server management panel built on the proven Pterodactyl/Pyrodactyl lineage, hardened with a modern stack and a **pre-installed Blueprint extension framework**. It ships with everything you need to run, theme, and extend a server hosting operation:

- **Blazing fast frontend** — React 19, Vite 7, Tailwind CSS 4, SWC compilation, and signal-based state.
- **Fast backend** — Laravel 12 on PHP 8.4 with OPcache JIT preloading tuned out of the box.
- **Blueprint built in** — install community extensions in seconds, no patching required. Manage everything from the admin area's **Extensions** page, or browse the built-in **Marketplace**.
- **Docker-native** — built via Docker, installed via Docker, extended via Docker. One command to go from zero to a running panel.

## Quick start (Docker)

```bash
curl -fsSL https://raw.githubusercontent.com/panasaurus/panel/main/install.sh | bash
```

That's it. The installer:

1. Downloads the Panasaurus compose stack into `./panasaurus/`
2. Generates strong database + admin credentials
3. Pulls and boots MariaDB, Redis, and the panel
4. Applies migrations and seeds the Blueprint framework
5. Prints your admin credentials (shown once — save them!)

Prefer manual control?

```bash
git clone https://github.com/panasaurus/panel.git panasaurus
cd panasaurus
docker compose up -d
```

### Managing extensions

The Blueprint CLI lives inside the container as both `panasaurus` and `blueprint`:

```bash
# inside the panel container
panasaurus -install <extension>    # install a .blueprint file
panasaurus -list                   # list installed extensions
panasaurus -remove <extension>     # remove an extension
```

Or use the **Admin → Panasaurus → Marketplace** page to discover extensions with one-click install instructions.

## What's built in

| Area | Details |
|------|---------|
| Blueprint framework | Full backend, CLI, admin pages, and React extension points — extensions made for Blueprint/Pterodactyl work out of the box |
| Marketplace | Live directory of the Blueprint ecosystem, cached in-app with search and filters |
| Command menu | Fast navigation on server pages |
| Health endpoint | `GET /api/health` — public, monitor-friendly JSON status (database, redis, storage) |
| Panasaurus CLI | `php artisan panasaurus:info` and `php artisan panasaurus:status` |
| Docker everywhere | Multi-stage image, healthchecks, tini, tuned PHP-FPM + nginx + supervisor, queue worker, scheduler |
| Performance | OPcache JIT + preloading, gzip, aggressive static caching, code-split assets |

## Development

```bash
# frontend dev server (hot reload against a local panel)
pnpm install
pnpm dev

# full docker dev stack
docker compose -f docker-compose.develop.yml up -d
```

Useful artisan commands:

```bash
php artisan panasaurus:info        # panel + blueprint version info
php artisan panasaurus:status      # service health check
php artisan bp:meta                # refresh extension metadata cache
```

## Building an extension?

Blueprint extensions work out of the box. Panasaurus keeps full compatibility with the Blueprint extension format — read the [Blueprint developer guides](https://blueprint.zip/guides) or the bundled docs in [`docs/EXTENSIONS.md`](docs/EXTENSIONS.md).

## Credits

Panasaurus stands on the shoulders of giants:

- [Pterodactyl](https://pterodactyl.io) — the original panel, by Dane Everitt and contributors
- [Pyrodactyl](https://pyrodactyl.dev) — the modernized fork this panel builds on
- [Blueprint](https://blueprint.zip) — the extension framework, by Emma (prpl.wtf) and contributors

Licensed under [Apache-2.0](LICENSE).
