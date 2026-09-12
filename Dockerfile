# 1. Base Image: Use the official, lightweight PHP 8.2 image with Apache pre-configured.
FROM php:8.2-apache

# 2. Server Configuration: Enable Apache's mod_rewrite module (standard practice for PHP routing).
RUN a2enmod rewrite

# 3. Environment Variables: Define defaults for Redis connection.
# These can be overridden at runtime via docker run -e or Kubernetes env vars.
ENV REDIS_HOST=redis \
    REDIS_PORT=6379

# 4. Working Directory: Set the default directory where Apache serves files.
WORKDIR /var/www/html

# 5. Copy Application Files: Transfer the frontend source code into the container.
COPY app/ /var/www/html/

# 6. Permissions & Directories: Ensure the data directory exists and Apache owns the files.
# This prevents permission errors and ensures local runs work even without K8s volume mounts.
RUN mkdir -p /var/www/html/data && \
    chown -R www-data:www-data /var/www/html

# 7. Expose Port: Declare that the container listens on port 80.
EXPOSE 80

# 8. Startup Command: Inherited from the base image, but explicitly declared for clarity.
CMD ["apache2-foreground"]
