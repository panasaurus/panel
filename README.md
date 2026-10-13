## What is Panasaurus?

Panasaurus is a game server management panel built on the proven Pterodactyl/Pyrodactyl lineage, hardened with a modern stack and a **pre-installed Blueprint extension framework**. It ships with everything you need to run, theme, and extend a server hosting operation:

- **Blazing fast frontend** — React 19, Vite 7, Tailwind CSS 4, SWC compilation, and signal-based state.
- **Fast backend** — Laravel 12 on PHP 8.4 with OPcache JIT preloading tuned out of the box.
- **Blueprint built in** — install community extensions in seconds, no patching required. Manage everything from the admin area's **Extensions** page, or browse the built-in **Marketplace**.
- **Docker-native** — built via Docker, installed via Docker, extended via Docker. One command to go from zero to a running panel.

## VPS installation

Use Ubuntu 22.04, 24.04, or 26.04, or Debian 12 or 13, on an amd64 or arm64 VPS. Allow at least 2 GB RAM and 10 GB free disk space; 4 GB RAM is recommended for the source build. Run the installer as root or with sudo.

For an IP-based test, copy `install.sh` to the VPS and run:

```bash
sudo bash install.sh --ip YOUR_VPS_IP --email admin@example.com
```

For a domain, point its DNS records at the VPS and allow incoming TCP ports 80 and 443 before running:

```bash
sudo bash install.sh --domain panel.example.com --email admin@example.com
```

The installer installs Docker when needed, builds the panel from source, and starts MariaDB, Redis, the panel, and Caddy. It enables the queue worker and scheduler, applies database migrations, and creates the admin account. [Docker's official apt repository](https://docs.docker.com/engine/install/ubuntu/) supplies Docker; [Caddy manages domain certificates and renewals](https://caddyserver.com/docs/automatic-https).

The default installation directory is `/opt/panasaurus`. Configuration, stable encryption keys, and credentials are saved under `.local/vps/` with restricted permissions. Database, storage, extensions, cache, and certificates use persistent Docker volumes. The script prints the URL and initial password after health, account, worker, and web proxy checks pass. Rerunning it reuses the existing configuration and data; it does not fetch a new version.

Additional options:

```bash
sudo bash install.sh --check --domain panel.example.com --email admin@example.com
sudo bash install.sh --ip YOUR_VPS_IP --email admin@example.com --timezone Asia/Kathmandu
sudo bash install.sh --source /root/panel --dir /opt/panasaurus --ip YOUR_VPS_IP --email admin@example.com
```

`--source` includes uncommitted files from a local checkout while excluding credentials and runtime data. Running the script from a checkout automatically uses that checkout. A standalone copy downloads the GitHub `main` branch; use `--ref` to choose another branch, tag, or commit. `--check` validates the inputs without installing packages or starting services. Run `bash install.sh --help` for all options.

Manage the installed services with:

```bash
sudo /opt/panasaurus/panel ps
sudo /opt/panasaurus/panel logs -f
sudo /opt/panasaurus/panel restart panel
sudo /opt/panasaurus/panel exec panel panasaurus -help
```

The initial credentials are also saved at `/opt/panasaurus/.local/vps/admin-credentials.txt`. SMTP defaults to local logging; configure a mail provider in `.local/vps/compose.yml` and recreate the panel container before using password-reset email. To back up the installation, retain the configuration (especially `APP_KEY`) and back up the persistent volumes, including a database dump. Wings is installed and connected separately when adding a node.

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

Bundled game templates use matching runtime and installer images from [`ghcr.io/panasaurus/images`](https://github.com/panasaurus/images). Upgrades migrate matching existing egg and server image settings while preserving custom images and installation scripts. Recreate or restart existing server containers to apply the new runtime. Mumble and legacy ARK retain their specialized external runtimes: the repository does not provide a Mumble image, and its Source image lacks the RCON helper used by the ARK template.

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
