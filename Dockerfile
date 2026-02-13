FROM php:7.4-apache

# Install mysqli and pdo_mysql extensions
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache mod_rewrite if needed (good practice for PHP apps)
RUN a2enmod rewrite

# Copy application source
COPY . /var/www/html/

# Update permissions
RUN chown -R www-data:www-data /var/www/html
