#!/bin/bash

# Railway CLI Setup Script - CORRECTED VERSION
# Dựa trên Railway CLI help output thực tế
# Sử dụng --skip-deploys để set tất cả variables rồi deploy 1 lần

set -e  # Exit on error

echo "🚂 Railway Variables Setup for Laravel Order Management System"
echo "================================================================"
echo ""

# Check if in Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found!"
    echo "Please run this script from your Laravel project root directory."
    exit 1
fi

# Check if railway CLI is installed
if ! command -v railway &> /dev/null; then
    echo "❌ Error: Railway CLI is not installed!"
    echo ""
    echo "Install Railway CLI:"
    echo "  curl -fsSL https://railway.app/install.sh | sh"
    echo "  or"
    echo "  npm install -g @railway/cli"
    exit 1
fi

# Check if logged in and linked
echo "📝 Checking Railway connection..."
if ! railway status &> /dev/null; then
    echo "❌ Error: Not logged in or project not linked!"
    echo ""
    echo "Please run:"
    echo "  railway login"
    echo "  railway link"
    exit 1
fi

echo "✅ Railway connection OK"
echo ""

# Generate APP_KEY
echo "🔑 Generating APP_KEY..."
APP_KEY=$(php artisan key:generate --show)

if [ -z "$APP_KEY" ]; then
    echo "❌ Error: Failed to generate APP_KEY"
    exit 1
fi

echo "✅ APP_KEY generated successfully"
echo ""

# Set all variables at once with --skip-deploys
echo "⚙️  Setting environment variables..."
echo "   (Using --skip-deploys to avoid multiple deployments)"
echo ""

railway variables --skip-deploys \
  --set "APP_NAME=Order Management System" \
  --set "APP_ENV=production" \
  --set "APP_KEY=$APP_KEY" \
  --set "APP_DEBUG=false" \
  --set "APP_URL=\${{RAILWAY_PUBLIC_DOMAIN}}" \
  --set "SESSION_DRIVER=file" \
  --set "CACHE_DRIVER=file" \
  --set "QUEUE_CONNECTION=database" \
  --set "FILESYSTEM_DISK=public" \
  --set "LOG_LEVEL=error" \
  --set "LOG_CHANNEL=daily" \
  --set "DB_CONNECTION=mysql" \
  --set "DB_HOST=\${{MYSQL_HOST}}" \
  --set "DB_PORT=\${{MYSQL_PORT}}" \
  --set "DB_DATABASE=\${{MYSQL_DATABASE}}" \
  --set "DB_USERNAME=\${{MYSQL_USER}}" \
  --set "DB_PASSWORD=\${{MYSQL_PASSWORD}}"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ All environment variables set successfully!"
else
    echo ""
    echo "❌ Error: Failed to set variables"
    exit 1
fi

# Verify variables
echo ""
echo "🔍 Verifying variables..."
echo ""
railway variables --kv | grep -E "APP_NAME|APP_ENV|APP_KEY|DB_CONNECTION"

echo ""
echo "================================================================"
echo "✅ Setup Complete!"
echo "================================================================"
echo ""
echo "📝 Next steps:"
echo ""
echo "1. Verify all variables (optional):"
echo "   railway variables --kv"
echo ""
echo "2. Deploy your application:"
echo "   railway up --detach"
echo ""
echo "3. Run database migrations:"
echo "   railway run php artisan migrate --force"
echo ""
echo "4. (Optional) Seed demo data:"
echo "   railway run php artisan db:seed --force"
echo ""
echo "5. Check deployment logs:"
echo "   railway logs"
echo ""
echo "6. Open your app in browser:"
echo "   railway open"
echo ""
echo "💡 TIP: You can also manage variables via Dashboard:"
echo "   railway open → Click service → Variables tab"
echo ""
