FROM php:8.0-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite (optional but often needed for PHP projects)
RUN a2enmod rewrite
