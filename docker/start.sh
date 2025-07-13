#!/bin/bash

# Laravel Docker Startup Script for Production with External Database
set -e

# Copy production environment if exists
if [ -f .env.production ]; then
    echo "Using production environment configuration..."
    cp .env.production .env
fi

# Generate app key if not set
echo "Generating application key..."
php artisan key:generate --force || true

# Wait for external database to be ready (with timeout)
echo "Waiting for external database connection..."
timeout=60
count=0
while ! php artisan migrate:status > /dev/null 2>&1; do
    if [ $count -ge $timeout ]; then
        echo "Database connection timeout. Please check your database configuration."
        exit 1
    fi
    echo "Database not ready yet, waiting... ($count/$timeout)"
    sleep 2
    ((count++))
done

echo "External database is ready!"

# Run migrations
echo "Running database migrations..."
php artisan migrate --force

# Create storage link if it doesn't exist
echo "Creating storage link..."
php artisan storage:link || true

# Clear and cache config
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions
echo "Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "Laravel application is ready!"

# Start Apache
exec apache2-foreground
