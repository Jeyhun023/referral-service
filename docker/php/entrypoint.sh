#!/bin/sh

# Install composer files if not exist
if [ ! -f "vendor/autoload.php" ]; then
    composer install --no-ansi --no-interaction --no-plugins --no-progress --no-scripts --optimize-autoloader
fi

# Set env variables
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Run php commands
if ! grep -qE '^APP_KEY=.+$' .env; then
    php artisan key:generate
    php artisan reverb:install
    php artisan optimize:clear
fi

# Fix files ownership
chown -R www-data:www-data /var/www/referral/storage /var/www/referral/bootstrap/cache
chmod -R 777 /var/www/referral/storage /var/www/referral/bootstrap/cache

# Execute php fpm to start the server
exec php-fpm
