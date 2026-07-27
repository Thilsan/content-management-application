#!/bin/sh
set -e

cd /var/www/html

if [ ! -d vendor ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f .env ]; then
    echo "Creating .env..."
    cp .env.example .env
    php artisan key:generate --force
fi

# The database container reports healthy before it will accept application
# credentials, so keep trying until a real connection succeeds.
until php -r '
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: 3306, getenv("DB_DATABASE"));
    try {
        new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
        exit(0);
    } catch (Throwable $e) {
        exit(1);
    }
' 2>/dev/null; do
    echo "Waiting for the database..."
    sleep 2
done

php artisan migrate --force

# Seeded once only, so demo content edited during a review survives a restart.
if [ ! -f storage/.seeded ]; then
    echo "Seeding..."
    php artisan db:seed --force
    touch storage/.seeded
fi

# Settle is_live for anything already in the database before the first request.
php artisan pages:publish-due

php artisan storage:link 2>/dev/null || true
php artisan l5-swagger:generate

exec "$@"
