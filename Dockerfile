# Use an official PHP image with Apache pre-installed
FROM php:8.2-apache

# Install MySQL extensions for PHP (mysqli and pdo_mysql)
RUN docker-php-ext-install mysqli pdo_mysql

# Enable Apache mod_rewrite if your project needs clean URLs later
RUN a2enmod rewrite

# Copy all your project files into the container's web root
COPY . /var/www/html/

# Set correct permissions for Apache
RUN chown -R www-data:www-data /var/www/html

# Expose port 80 for web traffic
EXPOSE 80