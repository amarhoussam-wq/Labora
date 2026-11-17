FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache mod_rewrite & headers
RUN a2enmod rewrite headers

# Configure upload limits and post size for medical prescriptions
RUN echo "file_uploads = On\n" \
         "upload_max_filesize = 10M\n" \
         "post_max_size = 12M\n" \
         "max_execution_time = 60\n" \
         "memory_limit = 256M\n" \
         > /usr/local/etc/php/conf.d/uploads.ini

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/uploads/prescriptions

# Expose HTTP port
EXPOSE 80

CMD ["apache2-foreground"]
