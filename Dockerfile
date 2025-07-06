FROM php:8.1-apache

# Copy application
COPY . /var/www/html

# Configure Apache to serve from /public
RUN sed -i 's#DocumentRoot /var/www/html#DocumentRoot /var/www/html/public#' \
    /etc/apache2/sites-available/000-default.conf \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
