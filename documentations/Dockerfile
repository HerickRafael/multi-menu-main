FROM php:8.4-apache

# Instalar extensões PHP e dependências
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd mbstring xml curl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && a2enmod rewrite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar código
WORKDIR /var/www/html
COPY . .

# Remover .env para usar variáveis de ambiente do Docker
RUN rm -f .env .env.*

# Instalar dependências
RUN composer install --no-dev --optimize-autoloader

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Apache configs - Configurar DocumentRoot e AllowOverride
RUN sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot /var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!<Directory /var/www/>!<Directory /var/www/html/public>!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!<Directory /var/www/html>!<Directory /var/www/html/public>!g' /etc/apache2/apache2.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# PHP configs - Aumentar limites de upload
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

# Configurações de sessão persistente
RUN echo "session.save_handler = files" > /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.save_path = \"/tmp/php_sessions\"" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.use_strict_mode = 1" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.use_cookies = 1" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.use_only_cookies = 1" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.name = MULTIMENU_SESS" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.cookie_lifetime = 2592000" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.gc_maxlifetime = 2592000" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.cookie_httponly = 1" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.cookie_secure = 0" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.cookie_samesite = Lax" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.gc_probability = 1" >> /usr/local/etc/php/conf.d/sessions.ini \
    && echo "session.gc_divisor = 100" >> /usr/local/etc/php/conf.d/sessions.ini \
    && mkdir -p /tmp/php_sessions \
    && chmod 777 /tmp/php_sessions

EXPOSE 80

CMD ["apache2-foreground"]
