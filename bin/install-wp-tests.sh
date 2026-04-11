#!/usr/bin/env bash
# =============================================================================
# WordPress Test Suite Installation Script
# =============================================================================
#
# This script installs the WordPress test suite for running PHPUnit tests.
# It's designed to work both locally and in CI environments.
#
# Usage:
#   ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]
#
# Arguments:
#   db-name                  Database name (required)
#   db-user                  Database user (required)
#   db-pass                  Database password (required)
#   db-host                  Database host (default: localhost)
#   wp-version               WordPress version (default: latest)
#   skip-database-creation   Skip database creation (default: false)
#
# Examples:
#   ./bin/install-wp-tests.sh wordpress_test root root localhost latest
#   ./bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 6.4 true
#
# =============================================================================

set -e

# Arguments
DB_NAME=${1:-wordpress_test}
DB_USER=${2:-root}
DB_PASS=${3:-''}
DB_HOST=${4:-localhost}
WP_VERSION=${5:-latest}
SKIP_DB_CREATE=${6:-false}

# Directories
WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# =============================================================================
# Helper Functions
# =============================================================================

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Download a file
download() {
    if [ $(which curl) ]; then
        curl -s "$1" > "$2"
    elif [ $(which wget) ]; then
        wget -nv -O "$2" "$1"
    else
        log_error "Neither curl nor wget is installed"
        exit 1
    fi
}

# Get WordPress version
get_wp_version() {
    if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        echo $WP_VERSION
    elif [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+$ ]]; then
        # Get latest patch version for major.minor
        local LATEST=$(download "https://api.wordpress.org/core/version-check/1.7/" - 2>/dev/null | \
            grep -oP '"version":"\K[0-9]+\.[0-9]+\.[0-9]+' | \
            grep "^$WP_VERSION" | head -1)
        echo ${LATEST:-$WP_VERSION.0}
    else
        # Get latest version
        download "https://api.wordpress.org/core/version-check/1.7/" - 2>/dev/null | \
            grep -oP '"version":"\K[0-9]+\.[0-9]+\.[0-9]+' | head -1
    fi
}

# =============================================================================
# Main Script
# =============================================================================

log_info "Installing WordPress Test Suite"
log_info "Database: $DB_NAME"
log_info "WordPress Version: $WP_VERSION"
log_info "Tests Directory: $WP_TESTS_DIR"
log_info "Core Directory: $WP_CORE_DIR"

# Resolve WordPress version
if [ "$WP_VERSION" == "latest" ]; then
    WP_VERSION=$(get_wp_version)
    log_info "Resolved latest version: $WP_VERSION"
fi

# Determine archive format (changed in WP 5.9)
if [[ $WP_VERSION =~ ^[0-9]+\.[0-9]+ ]]; then
    WP_MAJOR=$(echo $WP_VERSION | cut -d. -f1)
    WP_MINOR=$(echo $WP_VERSION | cut -d. -f2)
    if [ "$WP_MAJOR" -ge 5 ] && [ "$WP_MINOR" -ge 9 ] || [ "$WP_MAJOR" -ge 6 ]; then
        WP_TESTS_TAG="tags/$WP_VERSION"
    else
        WP_TESTS_TAG="tags/$WP_VERSION"
    fi
else
    WP_TESTS_TAG="trunk"
fi

log_info "Using tests tag: $WP_TESTS_TAG"

# =============================================================================
# Download WordPress
# =============================================================================

if [ ! -d "$WP_CORE_DIR" ]; then
    log_info "Downloading WordPress $WP_VERSION..."
    mkdir -p $WP_CORE_DIR

    if [ "$WP_VERSION" == "trunk" ]; then
        # Download from GitHub for trunk
        download https://github.com/WordPress/WordPress/archive/master.tar.gz /tmp/wordpress.tar.gz
        tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
    else
        # Download from official release
        download https://wordpress.org/wordpress-$WP_VERSION.tar.gz /tmp/wordpress.tar.gz
        tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
    fi

    log_info "WordPress downloaded successfully"
else
    log_info "WordPress already installed at $WP_CORE_DIR"
fi

# =============================================================================
# Download WordPress Test Suite
# =============================================================================

if [ ! -d "$WP_TESTS_DIR" ]; then
    log_info "Downloading WordPress test suite..."
    mkdir -p $WP_TESTS_DIR

    # Download test suite from WordPress develop repo
    svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/" $WP_TESTS_DIR/includes
    svn co --quiet "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/" $WP_TESTS_DIR/data

    log_info "Test suite downloaded successfully"
else
    log_info "Test suite already installed at $WP_TESTS_DIR"
fi

# =============================================================================
# Download wp-tests-config.php
# =============================================================================

if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
    log_info "Creating wp-tests-config.php..."

    download "https://develop.svn.wordpress.org/${WP_TESTS_TAG}/wp-tests-config-sample.php" "$WP_TESTS_DIR/wp-tests-config.php"

    # Configure database settings
    # Use @ as delimiter to avoid issues with special characters
    sed -i "s@youremptytestdbnamehere@${DB_NAME}@" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s@yourusernamehere@${DB_USER}@" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s@yourpasswordhere@${DB_PASS}@" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|localhost|${DB_HOST}|" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s@/tmp/wordpress/@${WP_CORE_DIR}/@" "$WP_TESTS_DIR/wp-tests-config.php"

    log_info "wp-tests-config.php created"
else
    log_info "wp-tests-config.php already exists"
fi

# =============================================================================
# Create Database
# =============================================================================

if [ "$SKIP_DB_CREATE" != "true" ]; then
    log_info "Creating database..."

    # Build MySQL command
    MYSQL_CMD="mysql -u${DB_USER}"
    if [ -n "$DB_PASS" ]; then
        MYSQL_CMD="$MYSQL_CMD -p${DB_PASS}"
    fi
    if [ "$DB_HOST" != "localhost" ]; then
        MYSQL_CMD="$MYSQL_CMD -h${DB_HOST}"
    fi

    # Create database
    $MYSQL_CMD -e "DROP DATABASE IF EXISTS ${DB_NAME};" 2>/dev/null || true
    $MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME};" 2>/dev/null

    if [ $? -eq 0 ]; then
        log_info "Database created successfully"
    else
        log_warn "Could not create database. It may already exist or you may not have permissions."
    fi
else
    log_info "Skipping database creation"
fi

# =============================================================================
# Summary
# =============================================================================

log_info "Installation complete!"
echo ""
echo "WordPress Test Suite installed at: $WP_TESTS_DIR"
echo "WordPress Core installed at: $WP_CORE_DIR"
echo "WordPress Version: $WP_VERSION"
echo ""
echo "You can now run PHPUnit tests:"
echo "  vendor/bin/phpunit"
echo ""
