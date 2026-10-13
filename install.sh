#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

APP_DIR="${PANASAURUS_INSTALL_DIR:-/opt/panasaurus}"
SOURCE_DIR=""
SOURCE_REF="main"
SITE_HOST=""
SITE_SCHEME=""
ADMIN_EMAIL=""
APP_TIMEZONE="UTC"
HTTP_PORT="80"
HTTPS_PORT="443"
CHECK_ONLY=false
TEMP_DIR=""

usage() {
    cat <<'EOF'
Panasaurus VPS installer

Usage: sudo bash install.sh [options]

  --domain DOMAIN     Install with automatic HTTPS
  --ip IPV4           Install over HTTP for an IP-based test
  --email EMAIL       Administrator email
  --timezone ZONE     Application timezone (default: UTC)
  --dir DIRECTORY     Installation directory (default: /opt/panasaurus)
  --source DIRECTORY  Build a local checkout, including uncommitted changes
  --ref REF           GitHub branch, tag, or commit (default: main)
  --http-port PORT    Public HTTP port (default: 80)
  --https-port PORT   Public HTTPS port (default: 443)
  --check             Validate options and show the plan without installing
  --help              Show this help

Supports Ubuntu 22.04/24.04/26.04 and Debian 12/13 on amd64 or arm64.
Missing host/email values are prompted on a terminal. Passwords are generated.
Rerunning an installation preserves its credentials, app key, and data.
EOF
}

fail() { printf 'Error: %s\n' "$*" >&2; exit 1; }
log() { printf '\n==> %s\n' "$*"; }
cleanup() { if [[ -n "$TEMP_DIR" ]]; then rm -rf -- "$TEMP_DIR"; fi; }
trap cleanup EXIT
trap 'printf "Installation stopped at line %s. Existing data and credentials are retained.\n" "$LINENO" >&2' ERR

while (( $# )); do
    case "$1" in
        --help|-h) usage; exit 0 ;;
        --check) CHECK_ONLY=true; shift ;;
        --domain|--ip|--email|--timezone|--dir|--source|--ref|--http-port|--https-port)
            [[ $# -ge 2 && -n "$2" && "$2" != --* ]] || fail "Missing value for $1."
            case "$1" in
                --domain|--ip)
                    [[ -z "$SITE_HOST" ]] || fail "Choose one of --domain or --ip."
                    SITE_HOST="$2"
                    if [[ "$1" == --domain ]]; then SITE_SCHEME=https; else SITE_SCHEME=http; fi
                    ;;
                --email) ADMIN_EMAIL="$2" ;;
                --timezone) APP_TIMEZONE="$2" ;;
                --dir) APP_DIR="$2" ;;
                --source) SOURCE_DIR="$2" ;;
                --ref) SOURCE_REF="$2" ;;
                --http-port) HTTP_PORT="$2" ;;
                --https-port) HTTPS_PORT="$2" ;;
            esac
            shift 2
            ;;
        *) fail "Unknown option: $1. Use --help." ;;
    esac
done

[[ -f /etc/os-release ]] || fail "Run this installer on an Ubuntu or Debian VPS."
source /etc/os-release
case "${ID:-}:${VERSION_ID:-}" in
    ubuntu:22.04|ubuntu:24.04|ubuntu:26.04|debian:12|debian:13) ;;
    *) fail "Supported systems: Ubuntu 22.04/24.04/26.04 or Debian 12/13. Found ${PRETTY_NAME:-unknown}." ;;
esac
case "$(uname -m)" in x86_64|aarch64) ;; *) fail "An amd64 or arm64 VPS is required." ;; esac
[[ "$APP_DIR" =~ ^/[a-zA-Z0-9_./-]+$ && "$APP_DIR" != *..* ]] || fail "Use an absolute installation path without spaces or '..'."
APP_DIR="${APP_DIR%/}"
[[ ! -L "$APP_DIR" ]] || fail "The installation directory cannot be a symbolic link."
CONFIG_DIR="$APP_DIR/.local/vps"
ENV_FILE="$CONFIG_DIR/.env"

env_value() { sed -n "s/^$1=//p" "$ENV_FILE"; }

if [[ -f "$ENV_FILE" ]]; then
    SAVED_HOST="$(env_value SITE_HOST)"
    SAVED_SCHEME="$(env_value SITE_SCHEME)"
    SAVED_EMAIL="$(env_value PANASAURUS_ADMIN_EMAIL)"
    [[ -z "$SITE_HOST" || ( "$SITE_HOST" == "$SAVED_HOST" && "$SITE_SCHEME" == "$SAVED_SCHEME" ) ]] || fail "This installation already uses $SAVED_SCHEME://$SAVED_HOST. Edit its configuration to change the address."
    [[ -z "$ADMIN_EMAIL" || "$ADMIN_EMAIL" == "$SAVED_EMAIL" ]] || fail "This installation already has an admin email. Change it in the panel."
    SITE_HOST="$SAVED_HOST"
    SITE_SCHEME="$SAVED_SCHEME"
    ADMIN_EMAIL="$SAVED_EMAIL"
    APP_TIMEZONE="$(env_value APP_TIMEZONE)"
    HTTP_PORT="$(env_value HTTP_PORT)"
    HTTPS_PORT="$(env_value HTTPS_PORT)"
fi

if [[ -z "$SITE_HOST" ]]; then
    [[ -t 0 ]] || fail "Supply --domain panel.example.com or --ip YOUR_VPS_IP."
    read -r -p 'Panel domain or IPv4 address: ' SITE_HOST
    if [[ "$SITE_HOST" =~ ^[0-9.]+$ ]]; then SITE_SCHEME=http; else SITE_SCHEME=https; fi
fi
if [[ -z "$ADMIN_EMAIL" ]]; then
    [[ -t 0 ]] || fail "Supply --email your-admin@example.com."
    read -r -p 'Administrator email: ' ADMIN_EMAIL
fi

if [[ "$SITE_SCHEME" == http ]]; then
    [[ "$SITE_HOST" =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]] || fail "--ip requires an IPv4 address."
    IFS=. read -r -a octets <<< "$SITE_HOST"
    for octet in "${octets[@]}"; do
        (( 10#$octet <= 255 )) || fail "Invalid IPv4 address: $SITE_HOST."
        [[ "$octet" == 0 || "$octet" != 0* ]] || fail "IPv4 octets must not have leading zeros."
    done
else
    [[ ${#SITE_HOST} -le 253 && "$SITE_HOST" == *.* && "$SITE_HOST" != *..* && "$SITE_HOST" != *. && ! "$SITE_HOST" =~ ^[0-9.]+$ ]] || fail "Use a public DNS name with --domain."
    IFS=. read -r -a labels <<< "$SITE_HOST"
    for label in "${labels[@]}"; do
        [[ ${#label} -le 63 && "$label" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?$ ]] || fail "Invalid domain: $SITE_HOST."
    done
    case "$SITE_HOST" in *.local|*.localhost|*.internal|*.home.arpa) fail "Use a public domain for trusted HTTPS." ;; esac
fi
[[ "$ADMIN_EMAIL" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]] || fail "Invalid administrator email."
[[ "$APP_TIMEZONE" =~ ^[a-zA-Z0-9_+/-]+$ && -f "/usr/share/zoneinfo/$APP_TIMEZONE" ]] || fail "Unknown timezone: $APP_TIMEZONE."
for port in "$HTTP_PORT" "$HTTPS_PORT"; do
    [[ "$port" =~ ^[0-9]{1,5}$ ]] && (( 10#$port >= 1 && 10#$port <= 65535 )) || fail "Ports must be between 1 and 65535."
done
HTTP_PORT=$((10#$HTTP_PORT))
HTTPS_PORT=$((10#$HTTPS_PORT))
[[ "$HTTP_PORT" != "$HTTPS_PORT" ]] || fail "HTTP and HTTPS ports must differ."
[[ "$SOURCE_REF" =~ ^[a-zA-Z0-9_./-]+$ && "$SOURCE_REF" != *..* ]] || fail "Invalid GitHub ref."
if [[ -z "$SOURCE_DIR" && -n "${BASH_SOURCE[0]:-}" && -f "${BASH_SOURCE[0]}" ]]; then
    SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
    if [[ -f "$SCRIPT_DIR/Dockerfile" ]]; then SOURCE_DIR="$SCRIPT_DIR"; fi
fi
if [[ -n "$SOURCE_DIR" ]]; then
    [[ -d "$SOURCE_DIR" ]] || fail "Source directory does not exist."
    SOURCE_DIR="$(cd -- "$SOURCE_DIR" && pwd)"
    [[ -f "$SOURCE_DIR/Dockerfile" && -f "$SOURCE_DIR/artisan" ]] || fail "Source directory must be a Panasaurus checkout."
fi
if [[ -d "$APP_DIR" && ! -f "$ENV_FILE" && "$SOURCE_DIR" != "$APP_DIR" ]]; then
    [[ -z "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit)" ]] || fail "Installation directory is not empty. Choose a new --dir."
fi
APP_URL="$SITE_SCHEME://$SITE_HOST"
if [[ "$SITE_SCHEME" == http && "$HTTP_PORT" != 80 ]]; then APP_URL+=":$HTTP_PORT"; fi
if [[ "$SITE_SCHEME" == https && "$HTTPS_PORT" != 443 ]]; then APP_URL+=":$HTTPS_PORT"; fi

if "$CHECK_ONLY"; then
    printf 'OS: %s\nInstall: %s\nURL: %s\nAdmin: %s\nSource: %s\n' "$PRETTY_NAME" "$APP_DIR" "$APP_URL" "$ADMIN_EMAIL" "${SOURCE_DIR:-GitHub ref $SOURCE_REF}"
    printf 'Plan: install Docker if needed, build the panel, start MariaDB/Redis/Caddy, verify health and admin account.\n'
    exit 0
fi
(( EUID == 0 )) || fail "Run with sudo bash install.sh (or as root)."

log 'Preparing VPS dependencies'
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y --no-install-recommends ca-certificates curl openssl tar iproute2 tzdata

if ! command -v docker >/dev/null 2>&1; then
    for package in docker.io docker-compose docker-compose-v2 podman-docker containerd runc; do
        if dpkg-query -W -f='${Status}' "$package" 2>/dev/null | grep -q 'install ok installed'; then
            fail "Conflicting package $package is installed. Use a fresh VPS or resolve Docker package conflicts first."
        fi
    done
    log "Installing Docker from its official $ID repository"
    install -m 0755 -d /etc/apt/keyrings
    curl -fsSL "https://download.docker.com/linux/$ID/gpg" -o /etc/apt/keyrings/docker.asc
    chmod 644 /etc/apt/keyrings/docker.asc
    cat > /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/$ID
Suites: ${UBUNTU_CODENAME:-$VERSION_CODENAME}
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
    systemctl enable --now docker
fi
docker info >/dev/null 2>&1 || fail "Docker is installed but unavailable. Start its daemon and rerun."
docker compose version >/dev/null 2>&1 || fail "Docker Compose v2 is required. Install the docker-compose-plugin package."
docker buildx version >/dev/null 2>&1 || fail "Docker Buildx is required. Install the docker-buildx-plugin package."

if [[ ! -f "$ENV_FILE" ]]; then
    for port in "$HTTP_PORT" "$HTTPS_PORT"; do
        [[ -z "$(ss -H -ltn "sport = :$port")" ]] || fail "Port $port is already in use. Choose another port or stop the conflicting service."
    done
    log "Preparing source in $APP_DIR"
    mkdir -p "$APP_DIR"
    TEMP_DIR="$(mktemp -d)"
    if [[ -n "$SOURCE_DIR" ]]; then
        if [[ "$SOURCE_DIR" != "$APP_DIR" ]]; then
            tar -C "$SOURCE_DIR" --exclude=.git --exclude=.local --exclude=.env --exclude='.env.*' --exclude=data --exclude=node_modules --exclude=vendor --exclude=public/build --exclude=public/assets/manifest.json --exclude=docker-compose.override.yml --exclude=panel --exclude=LOCAL-INSTALL.md --exclude='*.log' -cf "$TEMP_DIR/source.tar" .
            tar -xf "$TEMP_DIR/source.tar" -C "$APP_DIR"
            cp "$SOURCE_DIR/.env.example" "$APP_DIR/.env.example"
        fi
    else
        curl -fSL --retry 3 "https://api.github.com/repos/panasaurus/panel/tarball/$SOURCE_REF" -o "$TEMP_DIR/source.tar.gz"
        tar -xzf "$TEMP_DIR/source.tar.gz" -C "$APP_DIR" --strip-components=1
    fi
    [[ -f "$APP_DIR/Dockerfile" && -f "$APP_DIR/artisan" ]] || fail "Downloaded source is incomplete."
    mkdir -p "$CONFIG_DIR/nginx"
    cp "$APP_DIR/.github/docker/default.conf" "$CONFIG_DIR/nginx/panasaurus.conf"
    cp "$APP_DIR/.github/docker/supervisord.conf" "$CONFIG_DIR/supervisord.conf"
    cat >> "$CONFIG_DIR/supervisord.conf" <<'EOF'

[program:scheduler]
command=crond -f
autostart=true
autorestart=true
priority=40
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF
    cat > "$ENV_FILE" <<EOF
COMPOSE_PROJECT_NAME=panasaurus-$(openssl rand -hex 4)
PANASAURUS_SOURCE_DIR=$APP_DIR
SITE_HOST=$SITE_HOST
SITE_SCHEME=$SITE_SCHEME
APP_URL=$APP_URL
APP_TIMEZONE=$APP_TIMEZONE
HTTP_PORT=$HTTP_PORT
HTTPS_PORT=$HTTPS_PORT
APP_KEY=base64:$(openssl rand -base64 32)
HASHIDS_SALT=$(openssl rand -hex 16)
PANASAURUS_DB_PASSWORD=$(openssl rand -hex 24)
PANASAURUS_DB_ROOT_PASSWORD=$(openssl rand -hex 24)
PANASAURUS_ADMIN_EMAIL=$ADMIN_EMAIL
PANASAURUS_ADMIN_USERNAME=admin
PANASAURUS_ADMIN_PASSWORD=$(openssl rand -hex 16)
EOF
    chmod 600 "$ENV_FILE"
fi

cat > "$CONFIG_DIR/compose.yml" <<'EOF'
services:
  database:
    image: mariadb:11
    restart: unless-stopped
    environment:
      MARIADB_DATABASE: panasaurus
      MARIADB_USER: panasaurus
      MARIADB_PASSWORD: ${PANASAURUS_DB_PASSWORD:?}
      MARIADB_ROOT_PASSWORD: ${PANASAURUS_DB_ROOT_PASSWORD:?}
    volumes:
      - database:/var/lib/mysql
    healthcheck:
      test: [CMD, healthcheck.sh, --connect, --innodb_initialized]
      interval: 10s
      timeout: 5s
      retries: 12
      start_period: 30s
  cache:
    image: redis:7-alpine
    restart: unless-stopped
    command: [redis-server, --appendonly, 'yes']
    volumes:
      - cache:/data
    healthcheck:
      test: [CMD, redis-cli, ping]
      interval: 10s
      timeout: 5s
      retries: 6
  panel:
    image: ${COMPOSE_PROJECT_NAME:?}-panel:local
    build:
      context: ${PANASAURUS_SOURCE_DIR:?}
    restart: unless-stopped
    depends_on:
      database:
        condition: service_healthy
      cache:
        condition: service_healthy
    environment:
      APP_ENV: production
      APP_DEBUG: 'false'
      APP_ENVIRONMENT_ONLY: 'false'
      APP_URL: ${APP_URL:?}
      APP_TIMEZONE: ${APP_TIMEZONE:?}
      APP_KEY: ${APP_KEY:?}
      HASHIDS_SALT: ${HASHIDS_SALT:?}
      HASHIDS_LENGTH: 8
      DB_CONNECTION: mariadb
      DB_HOST: database
      DB_PORT: 3306
      DB_DATABASE: panasaurus
      DB_USERNAME: panasaurus
      DB_PASSWORD: ${PANASAURUS_DB_PASSWORD:?}
      CACHE_DRIVER: redis
      SESSION_DRIVER: redis
      QUEUE_CONNECTION: redis
      REDIS_HOST: cache
      TRUSTED_PROXIES: '*'
      MAIL_MAILER: log
      PANASAURUS_ADMIN_EMAIL: ${PANASAURUS_ADMIN_EMAIL:?}
      PANASAURUS_ADMIN_USERNAME: ${PANASAURUS_ADMIN_USERNAME:?}
      PANASAURUS_ADMIN_PASSWORD: ${PANASAURUS_ADMIN_PASSWORD:?}
    volumes:
      - panel-storage:/app/storage
      - panel-var:/app/var
      - extensions:/app/.blueprint/extensions
      - ${PANASAURUS_SOURCE_DIR:?}/.local/vps/nginx:/etc/nginx/http.d:ro
      - ${PANASAURUS_SOURCE_DIR:?}/.local/vps/supervisord.conf:/etc/supervisor/conf.d/supervisord.conf:ro
  caddy:
    image: caddy:2-alpine
    restart: unless-stopped
    depends_on:
      panel:
        condition: service_healthy
    ports:
      - '${HTTP_PORT:?}:80'
      - '${HTTPS_PORT:?}:443'
      - '${HTTPS_PORT:?}:443/udp'
    environment:
      SITE_HOST: ${SITE_HOST:?}
      SITE_SCHEME: ${SITE_SCHEME:?}
      ADMIN_EMAIL: ${PANASAURUS_ADMIN_EMAIL:?}
    volumes:
      - ${PANASAURUS_SOURCE_DIR:?}/.local/vps/Caddyfile:/etc/caddy/Caddyfile:ro
      - caddy-data:/data
      - caddy-config:/config
volumes:
  database:
  cache:
  panel-storage:
  panel-var:
  extensions:
  caddy-data:
  caddy-config:
EOF
if [[ ! -f "$CONFIG_DIR/Caddyfile" ]]; then
    cat > "$CONFIG_DIR/Caddyfile" <<'EOF'
{
    email {$ADMIN_EMAIL}
}
{$SITE_SCHEME}://{$SITE_HOST} {
    reverse_proxy panel:80
}
EOF
fi
cat > "$APP_DIR/panel" <<'EOF'
#!/usr/bin/env bash
set -euo pipefail
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
exec docker compose --project-directory "$SCRIPT_DIR" --env-file "$SCRIPT_DIR/.local/vps/.env" -f "$SCRIPT_DIR/.local/vps/compose.yml" "$@"
EOF
chmod 700 "$APP_DIR/panel"
compose() { "$APP_DIR/panel" "$@"; }

log 'Building Panasaurus from source (the first build can take several minutes)'
compose config --quiet
compose build panel
log 'Starting the database, cache, panel, and web proxy'
compose up -d --wait --wait-timeout 300
log 'Verifying the administrator account and background workers'
compose exec -T panel php -r '
require "/app/vendor/autoload.php";
$app = require "/app/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = Pterodactyl\Models\User::where("email", getenv("PANASAURUS_ADMIN_EMAIL"))->first();
if (!$user || !$user->root_admin) { fwrite(STDERR, "Admin account was not created.\n"); exit(1); }
'
WORKERS="$(compose exec -T panel supervisorctl -c /etc/supervisor/conf.d/supervisord.conf status)"
for worker in nginx php-fpm queue-worker scheduler; do
    grep -Eq "^${worker}[[:space:]]+RUNNING" <<< "$WORKERS" || fail "$worker is not running. Check: $APP_DIR/panel logs panel"
done

log 'Verifying the public URL and TLS certificate'
if [[ "$SITE_SCHEME" == https ]]; then
    PUBLIC_PORT="$HTTPS_PORT"
else
    PUBLIC_PORT="$HTTP_PORT"
fi
PUBLIC_READY=false
for _ in {1..60}; do
    if curl -fsS --noproxy '*' --connect-timeout 3 --max-time 5 --resolve "$SITE_HOST:$PUBLIC_PORT:127.0.0.1" "$APP_URL/api/health" >/dev/null 2>&1; then
        PUBLIC_READY=true
        break
    fi
    sleep 3
done
"$PUBLIC_READY" || fail "The panel started, but $APP_URL is not ready. For HTTPS, point DNS at this VPS and allow ports 80/443. Check: $APP_DIR/panel logs caddy"

cat > "$CONFIG_DIR/admin-credentials.txt" <<EOF
URL: $APP_URL
Admin: $ADMIN_EMAIL
Username: $(env_value PANASAURUS_ADMIN_USERNAME)
Initial password: $(env_value PANASAURUS_ADMIN_PASSWORD)
EOF
chmod 600 "$CONFIG_DIR/admin-credentials.txt"
printf '\nPanasaurus is installed and healthy.\n'
cat "$CONFIG_DIR/admin-credentials.txt"
printf '\nCredentials: %s\nManage: sudo %s/panel ps\nLogs: sudo %s/panel logs -f\n' "$CONFIG_DIR/admin-credentials.txt" "$APP_DIR" "$APP_DIR"
printf 'Mail is logged locally. Configure SMTP before using password-reset emails.\n'
