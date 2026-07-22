FROM php:8.2-apache

# 1. Install dependensi sistem dan ekstensi PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl mysqli pdo_mysql gd zip

# 2. Aktifkan mod_rewrite Apache (Penting untuk routing CI4)
RUN a2enmod rewrite

# 3. Ubah DocumentRoot Apache ke /public milik CI4
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 4. TAMBAHAN: Izinkan .htaccess menimpa konfigurasi routing Apache
RUN sed -i '/<Directory ${APACHE_DOCUMENT_ROOT}>/,/<\/Directory>/s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html