FROM php:8.2-apache

# Enable SQLite database access and PDO extensions
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Set the proper working directory
WORKDIR /var/www/html/

# Copy your source code files directly into the web server container workspace
COPY . /var/www/html/

# Grant rewrite permissions to Apache and expose port 80
RUN chown -R www-data:www-data /var/www/html/
EXPOSE 80
