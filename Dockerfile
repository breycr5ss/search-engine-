# Search Engine - PHP Application Container
# PHP 8.2 with PostgreSQL support

FROM php:8.2-cli-alpine

# Install system dependencies and PostgreSQL client
RUN apk add --no-cache \
    postgresql-dev \
    postgresql-client \
    && docker-php-ext-install pdo_pgsql pgsql

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Create a non-root user for running the application
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser && \
    chown -R appuser:appuser /var/www/html

# Switch to non-root user
USER appuser

# Expose port 8000 for PHP built-in server
EXPOSE 8000

# Health check to ensure the app is responsive
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD php -r "exit(file_get_contents('http://localhost:8000') ? 0 : 1);" || exit 1

# Start PHP built-in development server
CMD ["php", "-S", "0.0.0.0:8000", "-t", "/var/www/html"]
