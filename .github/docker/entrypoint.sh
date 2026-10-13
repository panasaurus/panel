#!/bin/bash
set -e

cd /app

log()  { printf '\033[1;32m[panasaurus]\033[0m %s\n' "$1"; }
warn() { printf '\033[1;33m[panasaurus]\033[0m %s\n' "$1"; }

if [ -z "$APP_KEY" ]; then
    log "No APP_KEY set — generating one.."
    APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    export APP_KEY
    warn "A generated APP_KEY is ephemeral. Set APP_KEY in your environment for stable encrypted data."
fi

if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "localhost" ] && [ "$DB_HOST" != "127.0.0.1" ]; then
    log "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}.."
    for _ in $(seq 1 60); do
        if php -r '
            try {
                new PDO(
                    sprintf("mysql:host=%s;port=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: "3306"),
                    getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 2]
                );
                exit(0);
            } catch (Throwable $e) { exit(1); }
        ' 2>/dev/null; then
            log "Database is up."
            break
        fi
        sleep 2
    done
fi

if [ ! -f /app/.panasaurus_bootstrapped ]; then
    log "First boot detected — running installer.."

    php artisan migrate --force || warn "Migrations failed — check database configuration."
    php artisan db:seed --class=BlueprintSeeder --force || true

    mkdir -p public/extensions public/assets/extensions
    ln -sfn /app/.blueprint/extensions/blueprint/public public/extensions/blueprint 2>/dev/null || true
    ln -sfn /app/scripts/libraries /app/.blueprint/lib 2>/dev/null || true

    if [ -n "$PANASAURUS_ADMIN_PASSWORD" ]; then
        log "Creating admin account (${PANASAURUS_ADMIN_EMAIL:-admin@example.com}).."
        php artisan p:user:make --no-interaction \
            --email="${PANASAURUS_ADMIN_EMAIL:-admin@example.com}" \
            --username="${PANASAURUS_ADMIN_USERNAME:-admin}" \
            --name-first="Panasaurus" \
            --name-last="Admin" \
            --password="${PANASAURUS_ADMIN_PASSWORD}" \
            --admin=1 2>/dev/null || warn "Admin user not created (it may already exist)."
    else
        warn "PANASAURUS_ADMIN_PASSWORD not set — create your admin account later with: docker compose exec panel php artisan p:user:make"
    fi

    log "Caching application.."
    php artisan view:cache || true
    php artisan config:cache || true
    php artisan route:cache || true

    touch /app/.panasaurus_bootstrapped

    log "First boot complete."
fi

log "Applying database migrations.."
php artisan migrate --force || warn "Migration failed."

mkdir -p /app/public/assets/extensions
for asset_directory in /app/.blueprint/extensions/*/assets; do
    [ -d "$asset_directory" ] || continue
    extension_id="$(basename "$(dirname "$asset_directory")")"
    case "$extension_id" in ''|*[!a-zA-Z0-9_-]*) continue ;; esac
    asset_link="/app/public/assets/extensions/$extension_id"
    if [ -L "$asset_link" ] || [ ! -e "$asset_link" ]; then
        ln -sfn "$asset_directory" "$asset_link"
    fi
done

log "Blueprint engine ready ($(ls /app/.blueprint/extensions 2>/dev/null | wc -l) extension slot(s))."

chown -R nginx:nginx storage bootstrap/cache || true

exec "$@"
