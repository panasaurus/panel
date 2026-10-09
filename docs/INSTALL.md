# Installing Panasaurus

Panasaurus is designed to be **installed by Docker and run in Docker**. Everything —
the panel, database, cache, queue worker, and scheduler — lives in a single compose
stack managed by `docker-compose.yml`.

## Requirements

- Docker Engine 24+ (with the compose plugin)
- 1GB+ RAM (2GB recommended)
- A domain pointed at your host (optional, for HTTPS via reverse proxy)

## One-command install

```bash
curl -fsSL https://raw.githubusercontent.com/panasaurus/panel/main/install.sh | bash
```

The installer creates a `panasaurus/` directory in your current working directory,
generates strong credentials, boots the stack, and prints your admin login.

## Manual install

```bash
git clone https://github.com/panasaurus/panel.git panasaurus
cd panasaurus

# create data directories
mkdir -p data/database data/var data/logs data/extensions data/nginx data/certs

# edit the compose file first: set APP_URL and the database passwords
$EDITOR docker-compose.yml

docker compose up -d
```

On first boot the panel will:

1. Wait for MariaDB to accept connections
2. Run all migrations
3. Seed the Blueprint framework
4. Create your admin account (if `PANASAURUS_ADMIN_PASSWORD` is set)
5. Cache config/routes/views for production speed

Check progress:

```bash
docker compose logs -f panel
```

## Environment variables

| Variable | Purpose |
|---|---|
| `APP_URL` | Public URL of the panel (used for links/emails) |
| `APP_TIMEZONE` | Panel timezone (PHP timezone names) |
| `PANASAURUS_ADMIN_EMAIL` | First-boot admin email |
| `PANASAURUS_ADMIN_USERNAME` | First-boot admin username (default `admin`) |
| `PANASAURUS_ADMIN_PASSWORD` | First-boot admin password |
| `PANASAURUS_DB_PASSWORD` | MariaDB password (must match `MYSQL_PASSWORD` and `DB_PASSWORD`) |

The full list lives in [.env.example](../.env.example).

## HTTPS

Two supported approaches:

**Reverse proxy (recommended)** — run nginx/Caddy/Traefik in front and set `APP_URL`
to your https:// domain. Add `TRUSTED_PROXIES` with your proxy subnet so the panel
sees real client IPs.

**Built-in nginx** — mount certificates into `./data/certs` and enable the commented
`listen 443 ssl` block in `.github/docker/default.conf` by copying it to
`./data/nginx/panasaurus.conf` with your domain and cert paths.

## Upgrading

```bash
cd panasaurus
docker compose pull panel
docker compose up -d
```

Migrations run automatically on every container start.

## Managing extensions

```bash
# list installed extensions
docker compose exec panel panasaurus -list

# install a .blueprint file you downloaded
docker compose cp ./my-extension.blueprint panel:/app/
docker compose exec panel panasaurus -install my-extension

# or browse the Marketplace in Admin → Panasaurus → Marketplace
```

## Backups

Database: `docker compose exec database sh -c 'exec mariadb-dump -u panasaurus -p"$MYSQL_PASSWORD" panasaurus' > backup.sql`

Volumes worth backing up: `data/database`, `data/var` (server files metadata, certificates).

## Troubleshooting

**Panel loops on "not healthy"** — `docker compose logs panel` shows the boot log; the
most common cause is a wrong `DB_PASSWORD` between compose and the database volume.

**401 on `/api/health`?** — it's public; check that no proxy strips the route.

**Extension assets 404** — run `docker compose exec panel panasaurus -rerun-install`
to refresh symlinks and caches.
