# syntax=docker/dockerfile:1
# ----------------------------------------------------------------------------
# Panasaurus Panel — production image
# Multi-stage build: frontend assets → PHP runtime (nginx + php-fpm + supervisor)
# Blueprint extension framework is pre-installed inside the image.
# ----------------------------------------------------------------------------

# ---------- Stage 1: build the frontend -----------------------------------
FROM --platform=$TARGETOS/$TARGETARCH node:22-alpine AS frontend

WORKDIR /app

# Enable pnpm via corepack
RUN corepack enable && corepack prepare pnpm@10 --activate

COPY package.json pnpm-lock.yaml .npmrc* ./
RUN pnpm fetch

COPY . .
RUN pnpm install --frozen-lockfile --offline \
    && pnpm run build \
    && rm -rf node_modules

# ---------- Stage 2: PHP runtime -------------------------------------------
FROM --platform=$TARGETOS/$TARGETARCH php:8.4-fpm-alpine AS runtime

ARG VERSION=local
ENV VERSION=${VERSION}

LABEL org.opencontainers.image.title="Panasaurus"
LABEL org.opencontainers.image.description="Panasaurus — the dino-strong game server panel, with the Blueprint extension framework built in."
LABEL org.opencontainers.image.url="https://github.com/panasaurus/panel"
LABEL org.opencontainers.image.source="https://github.com/panasaurus/panel"
LABEL org.opencontainers.image.licenses="Apache-2.0"

WORKDIR /app

# PHP build extensions
RUN apk add --no-cache --virtual .build-deps \
        libpng-dev \
        libxml2-dev \
        libzip-dev \
        postgresql-dev \
        oniguruma-dev \
    && docker-php-ext-configure zip \
    && docker-php-ext-install bcmath gd mbstring pdo pdo_mysql pdo_pgsql zip opcache \
    && apk del .build-deps \
    && apk add --no-cache \
        ca-certificates \
        curl \
        git \
        libpng \
        libxml2 \
        libzip \
        oniguruma \
        postgresql-libs \
        supervisor \
        nginx \
        dcron \
        tar \
        unzip \
        zip \
        mariadb-client \
        postgresql-client \
        redis \
        bash \
        tini

# opcache — real speed for production
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.enable_cli=1"; \
    echo "opcache.memory_consumption=256"; \
    echo "opcache.interned_strings_buffer=32"; \
    echo "opcache.max_accelerated_files=32531"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.save_comments=1"; \
    echo "opcache.jit=tracing"; \
    echo "opcache.jit_buffer_size=64M"; \
    echo "realpath_cache_size=4096K"; \
    echo "realpath_cache_ttl=600"; \
  } > /usr/local/etc/php/conf.d/panasaurus-opcache.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Application source
COPY --chown=nginx:nginx . .
COPY --from=frontend --chown=nginx:nginx /app/public/build ./public/build
COPY --from=frontend --chown=nginx:nginx /app/public/assets ./public/assets

# PHP dependencies (production)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Blueprint CLI + shortcuts (pre-installed extension framework)
RUN mkdir -p /usr/local/bin \
    && cp .github/docker/panasaurus-cli /usr/local/bin/panasaurus \
    && chmod 755 /usr/local/bin/panasaurus blueprint.sh \
    && ln -sf /usr/local/bin/panasaurus /usr/local/bin/blueprint \
    && chmod 755 .github/docker/entrypoint.sh

# Runtime directory setup
RUN mkdir -p \
        bootstrap/cache \
        storage/logs \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        /var/run/php \
        /var/run/nginx \
        /etc/nginx/http.d \
    && rm -rf bootstrap/cache/*.php \
    && chown -R nginx:nginx . \
    && chmod -R 770 bootstrap storage \
    && cp .env.example .env 2>/dev/null || true

# Cron: Laravel scheduler every minute
RUN echo "* * * * * /usr/local/bin/php /app/artisan schedule:run >> /dev/null 2>&1" \
        > /var/spool/cron/crontabs/root

# NGINX / PHP-FPM / supervisor configuration
COPY --chown=nginx:nginx .github/docker/default.conf /etc/nginx/http.d/panasaurus.conf
COPY --chown=nginx:nginx .github/docker/www.conf /usr/local/etc/php-fpm.d/zz-panasaurus.conf
COPY --chown=nginx:nginx .github/docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Prevent the base image's default php-fpm config from double-loading
RUN rm -f /usr/local/etc/php-fpm.d/www.conf.default /usr/local/etc/php-fpm.conf.default

EXPOSE 80 443

HEALTHCHECK --interval=30s --timeout=5s --start-period=60s --retries=3 \
    CMD curl -fsS http://127.0.0.1/api/health || exit 1

ENTRYPOINT ["/sbin/tini", "--", "/app/.github/docker/entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
