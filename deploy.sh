#!/bin/bash

# Family ERP Production Deployment Script
# This script handles the complete deployment workflow

set -e  # Exit on any error

echo "🚀 Starting Family ERP Deployment..."
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Please run this script from the project root.${NC}"
    exit 1
fi

# Step 1: Validate environment
echo -e "${YELLOW}Step 1: Validating environment...${NC}"
php artisan env:validate
if [ $? -ne 0 ]; then
    echo -e "${RED}Environment validation failed. Please fix the errors before deploying.${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Environment validation passed${NC}"
echo ""

# Step 2: Install/Update dependencies
echo -e "${YELLOW}Step 2: Installing dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-interaction
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

# Step 3: Run database migrations
echo -e "${YELLOW}Step 3: Running database migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

# Step 4: Clear all caches
echo -e "${YELLOW}Step 4: Clearing caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 5: Cache configuration for production
echo -e "${YELLOW}Step 5: Caching configuration...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
if php artisan list | grep -q "event:cache"; then
    php artisan event:cache
fi
echo -e "${GREEN}✓ Configuration cached${NC}"
echo ""

# Step 6: Optimize autoloader
echo -e "${YELLOW}Step 6: Optimizing autoloader...${NC}"
composer dump-autoload --optimize --classmap-authoritative
echo -e "${GREEN}✓ Autoloader optimized${NC}"
echo ""

# Step 7: Set proper permissions
echo -e "${YELLOW}Step 7: Setting permissions...${NC}"
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
echo -e "${GREEN}✓ Permissions set${NC}"
echo ""

# Step 8: Health check
echo -e "${YELLOW}Step 8: Running health check...${NC}"
if curl -f http://localhost/up > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Health check passed${NC}"
else
    echo -e "${YELLOW}⚠ Health check endpoint not accessible (this is normal if services aren't running)${NC}"
fi
echo ""

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo "Next steps:"
echo "  1. Restart your application server (php-fpm, nginx, etc.)"
echo "  2. Restart queue workers if using queues"
echo "  3. Verify the application is working correctly"
echo "  4. Monitor logs for any issues"








