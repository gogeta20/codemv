#syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1.9.1-php8.4.12 AS frankenphp_upstream
ARG USER=www-data

# ---------------------------------------------------------------------------
# Base image
# ---------------------------------------------------------------------------
FROM frankenphp_upstream AS frankenphp_base

WORKDIR /app
VOLUME /app/var/

RUN apt-get update && apt-get upgrade -y && apt-get install -y --no-install-recommends \
    acl \
    file \
    gettext \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN set -eux; \
    install-php-extensions \
        @composer \
        apcu \
        intl \
        opcache \
        zip \
        pdo_pgsql \
    ;

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

COPY --link frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link --chmod=755 frankenphp/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link frankenphp/Caddyfile /etc/caddy/Caddyfile

RUN useradd --home-dir /app ${USER}; \
    usermod -d /app ${USER}; \
    setcap CAP_NET_BIND_SERVICE=+eip /usr/local/bin/frankenphp; \
    chown -R ${USER}:${USER} /config/caddy /data/caddy /app

ENTRYPOINT ["docker-entrypoint"]
HEALTHCHECK --start-period=60s CMD curl -f http://localhost:2019/metrics || exit 1
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

# ---------------------------------------------------------------------------
# Dev image
# ---------------------------------------------------------------------------
FROM frankenphp_base AS frankenphp_dev

ENV APP_ENV=dev XDEBUG_MODE=off

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

RUN set -eux; \
    install-php-extensions xdebug;

RUN (curl -sS https://get.symfony.com/cli/installer | bash) && \
    mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

COPY --link frankenphp/conf.d/20-app.dev.ini $PHP_INI_DIR/app.conf.d/

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile", "--watch"]

# ---------------------------------------------------------------------------
# Prod image
# ---------------------------------------------------------------------------
FROM frankenphp_base AS frankenphp_prod

ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="import worker.Caddyfile"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/
COPY --link frankenphp/worker.Caddyfile /etc/caddy/worker.Caddyfile

COPY --link composer.* symfony.* ./
RUN touch .env
RUN set -eux; \
    composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link . ./
RUN rm -Rf frankenphp/
RUN set -eux; \
    mkdir -p var/cache var/log; \
    composer config extra.runtime.disable_dotenv true; \
    composer dump-autoload --classmap-authoritative --no-dev; \
    chmod +x bin/console; \
    chown -R ${USER}:${USER} var; \
    sync;

USER ${USER}
