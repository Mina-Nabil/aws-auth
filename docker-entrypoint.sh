#!/bin/bash
set -e

echo "🚀 Starting Laravel application initialization..."

# When ./services/laravel is mounted over /var/www, the image's vendor/ is hidden.
# Install dependencies if vendor is missing (e.g. bind mount without vendor on host).
if [ ! -f vendor/autoload.php ]; then
    echo "📦 vendor/ not found (e.g. bind mount overwrote image). Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

echo "Clearing cache to make sure we start with a fresh environment..."
php artisan config:clear

# Simple check: if .env doesn't exist, copy from .env.example
if [ ! -f .env ]; then
    echo "📝 Fresh installation detected - copying .env.example to .env..."
    cp .env.example .env
    sleep 5
fi

# Check if APP_KEY is empty (indicates fresh install or needs initialization)
APP_KEY_VALUE=$(grep "^APP_KEY=" .env 2>/dev/null | cut -d '=' -f2- | sed 's/#.*$//' | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' || echo "")

if [ -z "$APP_KEY_VALUE" ]; then
    echo "🔑 Generating application key..."
    # Generate the key
    php artisan key:generate --force 2>&1
    sleep 1
    
    # Update DB vars to Docker defaults on fresh install
    echo "🔧 Setting DB configuration for Docker..."
    sed -i.bak 's|^DB_HOST=.*|DB_HOST=mysql-image|' .env 2>/dev/null || sed -i '' 's|^DB_HOST=.*|DB_HOST=mysql-image|' .env
    sed -i.bak 's|^DB_PORT=.*|DB_PORT=3306|' .env 2>/dev/null || sed -i '' 's|^DB_PORT=.*|DB_PORT=3306|' .env
    sed -i.bak 's|^DB_DATABASE=.*|DB_DATABASE=laravel|' .env 2>/dev/null || sed -i '' 's|^DB_DATABASE=.*|DB_DATABASE=laravel|' .env
    sed -i.bak 's|^DB_USERNAME=.*|DB_USERNAME=laravel|' .env 2>/dev/null || sed -i '' 's|^DB_USERNAME=.*|DB_USERNAME=laravel|' .env
    sed -i.bak 's|^DB_PASSWORD=.*|DB_PASSWORD=laravel|' .env 2>/dev/null || sed -i '' 's|^DB_PASSWORD=.*|DB_PASSWORD=laravel|' .env
    rm -f .env.bak 2>/dev/null || true
else
    echo "🔑 Application key already exists, skipping generation..."
fi

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
DB_HOST="${DB_HOST:-mysql-image}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-laravel}"
DB_PASSWORD="${DB_PASSWORD:-laravel}"

MAX_ATTEMPTS=30
ATTEMPT=0

until php -r "
\$host = '${DB_HOST}';
\$port = ${DB_PORT};
\$username = '${DB_USERNAME}';
\$password = '${DB_PASSWORD}';
try {
    \$pdo = new PDO(\"mysql:host=\$host;port=\$port\", \$username, \$password, [
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo 'connected';
    exit(0);
} catch (PDOException \$e) {
    exit(1);
}
" 2>/dev/null; do
  ATTEMPT=$((ATTEMPT + 1))
  if [ $ATTEMPT -ge $MAX_ATTEMPTS ]; then
    echo ""
    echo "❌ MySQL connection failed after $MAX_ATTEMPTS attempts"
    echo "   Host: ${DB_HOST}"
    echo "   Port: ${DB_PORT}"
    exit 1
  fi
  if [ $((ATTEMPT % 5)) -eq 0 ]; then
    echo "   Still waiting... (attempt $ATTEMPT/$MAX_ATTEMPTS)"
  fi
  sleep 2
done
echo "✅ MySQL is ready!"

# Run migrations
echo "📦 Running database migrations..."
php artisan migrate --force

sleep 2


# Setup Passport keys if they don't exist
if [ ! -f storage/oauth-private.key ]; then
    echo "🔑 Generating Passport keys..."
    php artisan passport:keys --force
fi

sleep 2

# Create Passport password client if it doesn't exist
echo "🔐 Checking for Passport password client..."
CLIENT_EXISTS=$(php artisan tinker --execute="echo \Laravel\Passport\Client::where('grant_types', 'like', '%password%')->exists() ? '1' : '0';" 2>/dev/null || echo "0")

if [ "$CLIENT_EXISTS" -eq "0" ]; then
    echo "📝 Creating Passport password client..."
    OUTPUT=$(php artisan passport:client --password --no-interaction --name="Web Password Client" 2>&1)
    
    # Extract client ID and secret
    CLIENT_ID=$(echo "$OUTPUT" | grep -i "Client ID" | grep -oE '[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}' | head -n1 | tr -d '[:space:]')
    CLIENT_SECRET=$(echo "$OUTPUT" | grep -i "Client Secret" | sed -E 's/.*Client Secret[^A-Za-z0-9]*([A-Za-z0-9]{40,}).*/\1/' | head -n1 | tr -d '[:space:]')
    
    if [ -n "$CLIENT_ID" ] && [ ${#CLIENT_ID} -gt 30 ] && [ -n "$CLIENT_SECRET" ] && [ ${#CLIENT_SECRET} -gt 20 ]; then
        echo "✅ Passport client created successfully"
        
        # Update .env with client credentials
        if grep -q "^CLIENT_WEB_ID=" .env 2>/dev/null; then
            sed -i.bak "s|^CLIENT_WEB_ID=.*|CLIENT_WEB_ID=$CLIENT_ID|" .env 2>/dev/null || sed -i '' "s|^CLIENT_WEB_ID=.*|CLIENT_WEB_ID=$CLIENT_ID|" .env
        else
            echo "CLIENT_WEB_ID=$CLIENT_ID" >> .env
        fi
        
        if grep -q "^CLIENT_WEB_SECRET=" .env 2>/dev/null; then
            sed -i.bak "s|^CLIENT_WEB_SECRET=.*|CLIENT_WEB_SECRET=$CLIENT_SECRET|" .env 2>/dev/null || sed -i '' "s|^CLIENT_WEB_SECRET=.*|CLIENT_WEB_SECRET=$CLIENT_SECRET|" .env
        else
            echo "CLIENT_WEB_SECRET=$CLIENT_SECRET" >> .env
        fi
        rm -f .env.bak 2>/dev/null || true
    fi
else
    echo "✅ Passport password client already exists"
fi

# Copy COGNITO_USER_POOL_ID from root .env into Laravel .env (read from mounted file)
ROOT_ENV="${ROOT_ENV:-/root/.env.project}"
get_pool_id() { grep '^COGNITO_USER_POOL_ID=' "$1" 2>/dev/null | cut -d'=' -f2- | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | sed 's/#.*$//'; }
ROOT_POOL_ID=""
LARAVEL_POOL_ID=$(get_pool_id .env)
if [ -f "$ROOT_ENV" ]; then
    ROOT_POOL_ID=$(get_pool_id "$ROOT_ENV")
fi
if [ -n "$ROOT_POOL_ID" ] && [ "$LARAVEL_POOL_ID" = "$ROOT_POOL_ID" ]; then
    echo "📋 COGNITO_USER_POOL_ID was already set (matches root .env)"
else
    ATTEMPT=1
    MAX_ATTEMPTS=3
    while [ $ATTEMPT -le $MAX_ATTEMPTS ]; do
        if [ -f "$ROOT_ENV" ]; then
            ROOT_POOL_ID=$(get_pool_id "$ROOT_ENV")
        else
            ROOT_POOL_ID=""
        fi
        if [ -n "$ROOT_POOL_ID" ]; then
            if grep -q '^COGNITO_USER_POOL_ID=' .env 2>/dev/null; then
                sed -i.bak "s|^COGNITO_USER_POOL_ID=.*|COGNITO_USER_POOL_ID=$ROOT_POOL_ID|" .env 2>/dev/null || sed -i '' "s|^COGNITO_USER_POOL_ID=.*|COGNITO_USER_POOL_ID=$ROOT_POOL_ID|" .env
            else
                echo "COGNITO_USER_POOL_ID=$ROOT_POOL_ID" >> .env
            fi
            rm -f .env.bak 2>/dev/null || true
            echo "📋 Found COGNITO_USER_POOL_ID in root .env and set in services/laravel/.env"
            break
        fi
        if [ $ATTEMPT -lt $MAX_ATTEMPTS ]; then
            echo "⏳ COGNITO_USER_POOL_ID not in root .env yet, waiting 5s (attempt $ATTEMPT/$MAX_ATTEMPTS)..."
            sleep 5
        fi
        ATTEMPT=$((ATTEMPT + 1))
    done
    if [ -z "$ROOT_POOL_ID" ]; then
        echo "⚠️ COGNITO_USER_POOL_ID not found in root .env after $MAX_ATTEMPTS attempts, skipping"
    fi
fi

# Copy COGNITO_APP_CLIENT_ID | COGNITO_APP_CLIENT_SECRET from root .env into Laravel .env
get_cognito_val() { grep "^$1=" "$2" 2>/dev/null | cut -d'=' -f2- | sed 's/^[[:space:]]*//;s/[[:space:]]*$//' | sed 's/#.*$//'; }
if [ -f "$ROOT_ENV" ]; then
    ROOT_APP_CLIENT_ID=$(get_cognito_val "COGNITO_APP_CLIENT_ID" "$ROOT_ENV")
    if [ -n "$ROOT_APP_CLIENT_ID" ]; then
        if grep -q '^COGNITO_APP_CLIENT_ID=' .env 2>/dev/null; then
            sed -i.bak "s|^COGNITO_APP_CLIENT_ID=.*|COGNITO_APP_CLIENT_ID=$ROOT_APP_CLIENT_ID|" .env 2>/dev/null || sed -i '' "s|^COGNITO_APP_CLIENT_ID=.*|COGNITO_APP_CLIENT_ID=$ROOT_APP_CLIENT_ID|" .env
        else
            echo "COGNITO_APP_CLIENT_ID=$ROOT_APP_CLIENT_ID" >> .env
        fi
        rm -f .env.bak 2>/dev/null || true
        echo "📋 Found COGNITO_APP_CLIENT_ID in root .env and set in services/laravel/.env"
    fi
    ROOT_APP_CLIENT_SECRET=$(get_cognito_val "COGNITO_APP_CLIENT_SECRET" "$ROOT_ENV")
    if [ -n "$ROOT_APP_CLIENT_SECRET" ]; then
        if grep -q '^COGNITO_APP_CLIENT_SECRET=' .env 2>/dev/null; then
            sed -i.bak "s|^COGNITO_APP_CLIENT_SECRET=.*|COGNITO_APP_CLIENT_SECRET=$ROOT_APP_CLIENT_SECRET|" .env 2>/dev/null || sed -i '' "s|^COGNITO_APP_CLIENT_SECRET=.*|COGNITO_APP_CLIENT_SECRET=$ROOT_APP_CLIENT_SECRET|" .env
        else
            echo "COGNITO_APP_CLIENT_SECRET=$ROOT_APP_CLIENT_SECRET" >> .env
        fi
        rm -f .env.bak 2>/dev/null || true
        echo "📋 Found COGNITO_APP_CLIENT_SECRET in root .env and set in services/laravel/.env"
    fi
fi

# Cache configuration and routes
echo "⚡ Caching configuration and routes..."
php artisan config:clear
php artisan config:cache
php artisan route:cache
echo "✅ Configuration and routes cached!"

sleep 2

# Seed database
echo "🌱 Seeding database..."
php artisan apiato:seed-deploy --force

sleep 2

# Sync permissions
echo "🔐 Syncing permissions..."
php artisan permissions:sync

sleep 2

# Generate api docs
echo "📚 Generating API docs..."
php artisan l5-swagger:generate

sleep 2

# Dump autoload
echo "🔄 Dumping autoload..."
composer dump-autoload

echo "✅ Initialization complete!"
echo "🎉 Starting PHP-FPM..."

# Execute the main command (php-fpm)
exec "$@"
