#!/usr/bin/env bash
# ----------------------------------------------------------------------------
# Panasaurus Panel — Docker installer
#
#   curl -fsSL https://raw.githubusercontent.com/panasaurus/panel/main/install.sh | bash
#
# Or clone and run:  ./install.sh
#
# Prepares data directories, generates an .env with strong secrets, pulls the
# latest image, boots the stack, applies migrations and prints your admin
# bootstrap details.
# ----------------------------------------------------------------------------
set -euo pipefail

APP_DIR="${PANASAURUS_INSTALL_DIR:-$(pwd)/panasaurus}"
COMPOSE_FILE="$APP_DIR/docker-compose.yml"
PANEL_PORT="${PANEL_PORT:-80}"

green() { printf '\033[0;32m%s\033[0m\n' "$1"; }
yellow() { printf '\033[1;33m%s\033[0m\n' "$1"; }
red() { printf '\033[0;31m%s\033[0m\n' "$1"; }


banner() {
  cat <<'EOF'
 ____                             
|  _ \ __ _ _ __   __ _ ___  __ _ _   _ _ __ _   _ ___
| |_) / _` | '_ \ / _` / __|/ _` | | | | '__| | | / __|
|  __/ (_| | | | | (_| \__ \ (_| | |_| | |  | |_| \__ \
|_|   \__,_|_| |_|\__,_|___/\__,_|\__,_|_|   \__,_|___/
                         the dino-strong panel
  https://github.com/panasaurus/panel
EOF
}

banner

# ---- 0. Checks ----------------------------------------------------------------
if ! command -v docker >/dev/null 2>&1; then
  red "Docker is required but was not found in PATH."
  yellow "Install it from https://docs.docker.com/engine/install/"
  exit 1
fi

if docker compose version >/dev/null 2>&1; then
  DOCKER_COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  DOCKER_COMPOSE="docker-compose"
else
  red "Neither 'docker compose' nor 'docker-compose' is available."
  exit 1
fi

green "==> Using: $DOCKER_COMPOSE"

# ---- 1. Project skeleton ------------------------------------------------------
if [ ! -f "$COMPOSE_FILE" ]; then
  green "==> Fetching Panasaurus into $APP_DIR .."
  mkdir -p "$APP_DIR"
  curl -fsSL https://github.com/panasaurus/panel/archive/refs/heads/main.tar.gz \
    | tar -xz -C "$APP_DIR" --strip-components=1
fi

cd "$APP_DIR"

# ---- 2. Secrets ---------------------------------------------------------------
green "==> Generating configuration .."

DB_PASSWORD="${PANASAURUS_DB_PASSWORD:-$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 24)}"
ADMIN_PASSWORD="${PANASAURUS_ADMIN_PASSWORD:-$(head -c 24 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 16)}"

mkdir -p data/database data/var data/logs data/extensions data/nginx data/certs

export PANEL_PORT
export PANASAURUS_ADMIN_PASSWORD="$ADMIN_PASSWORD"
export PANASAURUS_ADMIN_EMAIL="${PANASAURUS_ADMIN_EMAIL:-admin@panasaurus.local}"

# Inject generated DB password into the compose file's anchor
if grep -q "CHANGE_ME_PLEASE" docker-compose.yml; then
  sed -i.bak "s/CHANGE_ME_PLEASE/${DB_PASSWORD}/g; s/CHANGE_ME_TOO_PLEASE/$(head -c 32 /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c 24)/g" docker-compose.yml
  rm -f docker-compose.yml.bak
fi

# Uncomment first-boot admin env in compose
sed -i.bak2 \
  -e "s|# PANASAURUS_ADMIN_EMAIL: 'you@example.com'|PANASAURUS_ADMIN_EMAIL: '${PANASAURUS_ADMIN_EMAIL}'|" \
  -e "s|# PANASAURUS_ADMIN_USERNAME: 'admin'|PANASAURUS_ADMIN_USERNAME: 'admin'|" \
  -e "s|# PANASAURUS_ADMIN_PASSWORD: 'change-me-please'|PANASAURUS_ADMIN_PASSWORD: '${ADMIN_PASSWORD}'|" \
  docker-compose.yml
rm -f docker-compose.yml.bak2

# ---- 3. Boot ------------------------------------------------------------------
green "==> Pulling images .."
$DOCKER_COMPOSE pull --quiet || true

green "==> Starting Panasaurus .."
$DOCKER_COMPOSE up -d

green "==> Waiting for the panel to become healthy .."
ATTEMPTS=0
until curl -fsS "http://127.0.0.1:${PANEL_PORT}/api/health" >/dev/null 2>&1 || [ $ATTEMPTS -ge 60 ]; do
  ATTEMPTS=$((ATTEMPTS + 1))
  sleep 3
done

if [ $ATTEMPTS -ge 60 ]; then
  yellow "The panel is still starting (databases can take a minute on first boot)."
  yellow "Check logs with:  $DOCKER_COMPOSE logs -f panel"
else
  green "==> Panasaurus is up and healthy!"
fi

cat <<EOF

  ╔══════════════════════════════════════════════════════╗
  ║              Panasaurus installed successfully       ║
  ╠══════════════════════════════════════════════════════╣
  ║  URL:      http://localhost:${PANEL_PORT}
  ║  Admin:    ${PANASAURUS_ADMIN_EMAIL}
  ║  Password: ${ADMIN_PASSWORD}
  ╠══════════════════════════════════════════════════════╣
  ║  Extensions (Blueprint built in):
  ║    $DOCKER_COMPOSE exec panel panasaurus -help
  ║  Marketplace:  Admin area → Panasaurus → Marketplace
  ╚══════════════════════════════════════════════════════╝

EOF

yellow "Keep these credentials safe — the password is only shown once."
