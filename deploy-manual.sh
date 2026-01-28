#!/bin/bash

# Manual Deployment Script for Wasmer Edge
# Run this script locally to deploy your app manually

set -e

echo "🚀 Starting Manual Deployment to Wasmer Edge..."
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if wasmer CLI is installed
if ! command -v wasmer &> /dev/null; then
    echo -e "${RED}Error: Wasmer CLI is not installed.${NC}"
    echo "Install it with: curl https://get.wasmer.io -sSfL | sh"
    exit 1
fi

# Check if logged in
echo -e "${YELLOW}Checking Wasmer authentication...${NC}"
if ! wasmer whoami &> /dev/null; then
    echo -e "${YELLOW}Not logged in. Please login:${NC}"
    wasmer auth login
fi

# Ensure we're in the project root
if [ ! -f "wasmer.toml" ]; then
    echo -e "${RED}Error: wasmer.toml not found. Please run this script from the project root.${NC}"
    exit 1
fi

# Install PHP dependencies
echo -e "${YELLOW}Installing PHP dependencies...${NC}"
composer install --no-dev --optimize-autoloader --no-scripts
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

# Bump version (optional - you can comment this out if you want to manually set version)
echo -e "${YELLOW}Bumping version...${NC}"
CURRENT_VERSION=$(grep '^version = ' wasmer.toml | cut -d'"' -f2)
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"
PATCH=$((PATCH + 1))
NEW_VERSION="$MAJOR.$MINOR.$PATCH"

if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' "s/version = \"$CURRENT_VERSION\"/version = \"$NEW_VERSION\"/" wasmer.toml
else
    sed -i "s/version = \"$CURRENT_VERSION\"/version = \"$NEW_VERSION\"/" wasmer.toml
fi
echo -e "${GREEN}✓ Version updated to $NEW_VERSION${NC}"
echo ""

# Publish package
echo -e "${YELLOW}Publishing package to Wasmer registry...${NC}"
if wasmer publish --non-interactive; then
    echo -e "${GREEN}✓ Package published successfully${NC}"
else
    echo -e "${RED}✗ Package publish failed${NC}"
    exit 1
fi
echo ""

# Update app.yaml to use published package (if needed)
echo -e "${YELLOW}Updating app.yaml...${NC}"
if [[ "$OSTYPE" == "darwin"* ]]; then
    sed -i '' 's|^package: \.|package: vasu2152004/family-erp-package|' app.yaml || true
else
    sed -i 's|^package: \.|package: vasu2152004/family-erp-package|' app.yaml || true
fi
echo -e "${GREEN}✓ app.yaml updated${NC}"
echo ""

# Deploy app
echo -e "${YELLOW}Deploying app to Wasmer Edge...${NC}"
if wasmer deploy --owner vasu2152004; then
    echo ""
    echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
    echo ""
    echo "Your app should be available at: https://family-erp.wasmer.app"
else
    echo -e "${RED}✗ Deployment failed${NC}"
    exit 1
fi
