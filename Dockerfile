FROM php:8.4-fpm

# Rozszerzenia PHP faktycznie używane przez Feng Office (patrz CLAUDE.md):
# gd (fpdf/simplegd), curl (PEAR/Zend HTTP), zip (FilesController/ToolController),
# mbstring, pdo_mysql (DB_ADAPTER=pdo_mysql), xml (Swift Mailer/Zend)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd curl zip mbstring pdo pdo_mysql xml opcache \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
