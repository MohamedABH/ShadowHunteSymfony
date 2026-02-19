FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql intl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]