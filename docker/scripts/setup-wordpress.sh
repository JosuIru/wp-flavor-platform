#!/bin/bash
# =============================================================================
# WordPress Setup Script for E2E Testing
# =============================================================================
#
# This script sets up a fresh WordPress installation with Flavor Platform
# for E2E testing purposes.
#
# Usage:
#   docker-compose run --rm wpcli /scripts/setup-wordpress.sh
#
# =============================================================================

set -e

echo "================================="
echo "Setting up WordPress for testing"
echo "================================="

# Wait for database
echo "Waiting for database..."
sleep 5

# Check if WordPress is already installed
if wp core is-installed --allow-root 2>/dev/null; then
    echo "WordPress is already installed"
else
    echo "Installing WordPress..."

    wp core install \
        --url=http://localhost:8080 \
        --title="Flavor Platform Test" \
        --admin_user=admin \
        --admin_password=admin \
        --admin_email=admin@example.com \
        --skip-email \
        --allow-root

    echo "WordPress installed successfully"
fi

# Activate plugin
echo "Activating Flavor Platform..."
wp plugin activate flavor-platform --allow-root || true

# Install and activate theme (if available)
if wp theme is-installed flavor-starter --allow-root 2>/dev/null; then
    echo "Activating Flavor Starter theme..."
    wp theme activate flavor-starter --allow-root
else
    echo "Flavor Starter theme not found, using default theme"
    wp theme activate twentytwentyfour --allow-root 2>/dev/null || true
fi

# Set up permalinks
echo "Setting up permalinks..."
wp rewrite structure '/%postname%/' --allow-root
wp rewrite flush --allow-root

# Create test pages
echo "Creating test pages..."

wp post create \
    --post_type=page \
    --post_title="Test Page" \
    --post_content="This is a test page for E2E testing." \
    --post_status=publish \
    --allow-root 2>/dev/null || true

wp post create \
    --post_type=page \
    --post_title="VBP Test Page" \
    --post_content="[flavor_vbp_test]" \
    --post_status=publish \
    --allow-root 2>/dev/null || true

# Set homepage
echo "Setting up homepage..."
HOME_ID=$(wp post list --post_type=page --name=test-page --field=ID --allow-root 2>/dev/null || echo "")
if [ -n "$HOME_ID" ]; then
    wp option update show_on_front page --allow-root
    wp option update page_on_front $HOME_ID --allow-root
fi

# Create test user
echo "Creating test user..."
wp user create testuser test@example.com \
    --role=subscriber \
    --user_pass=testpass \
    --allow-root 2>/dev/null || true

# Configure Flavor Platform options
echo "Configuring Flavor Platform..."
wp option update flavor_modules_active '["eventos","socios","marketplace"]' --format=json --allow-root 2>/dev/null || true

# Flush caches
echo "Flushing caches..."
wp cache flush --allow-root 2>/dev/null || true
wp transient delete --all --allow-root 2>/dev/null || true

echo ""
echo "================================="
echo "Setup complete!"
echo "================================="
echo ""
echo "WordPress URL: http://localhost:8080"
echo "Admin URL: http://localhost:8080/wp-admin"
echo "Username: admin"
echo "Password: admin"
echo ""
