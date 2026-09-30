FROM php:8.3-fpm-alpine

# System deps + PHP extensions required by the lab
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo pdo_mysql opcache \
    && apk del .build-deps

# Sensible production-ish opcache defaults (safe for the lab)
RUN { \
      echo 'opcache.enable=1'; \
      echo 'opcache.enable_cli=0'; \
      echo 'opcache.memory_consumption=128'; \
      echo 'opcache.max_accelerated_files=10000'; \
      echo 'opcache.validate_timestamps=1'; \
      echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

RUN { \
      echo 'expose_php=0'; \
      echo 'display_errors=Off'; \
      echo 'log_errors=On'; \
      echo 'session.cookie_httponly=1'; \
      echo 'session.cookie_samesite=Lax'; \
      echo 'session.use_strict_mode=1'; \
    } > /usr/local/etc/php/conf.d/lunatic.ini

WORKDIR /var/www/html

# Ensure writable storage
RUN mkdir -p storage/logs storage/cache \
    && chown -R www-data:www-data storage

USER www-data
