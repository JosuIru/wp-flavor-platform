#!/usr/bin/env bash

# Script para instalar WordPress Test Framework
# Uso: ./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]

if [ $# -lt 3 ]; then
    echo "Uso: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version]"
    echo ""
    echo "Ejemplo:"
    echo "  $0 wordpress_test root root localhost latest"
    echo ""
    echo "NOTA: La base de datos de tests se BORRARÁ y recreará cada vez."
    exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}

# Directorio donde se instalará WordPress Test Framework
WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress}

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

# Obtener versión de WP si es "latest"
if [[ $WP_VERSION == 'latest' ]]; then
    WP_VERSION=$(curl -s https://api.wordpress.org/core/version-check/1.7/ | grep -o '"version":"[^"]*"' | head -1 | cut -d'"' -f4)
fi

echo "=== Instalando WordPress Test Framework ==="
echo "WordPress version: $WP_VERSION"
echo "DB: $DB_NAME@$DB_HOST"
echo "WP Tests Dir: $WP_TESTS_DIR"
echo "WP Core Dir: $WP_CORE_DIR"
echo ""

# Crear directorios
mkdir -p $WP_TESTS_DIR
mkdir -p $WP_CORE_DIR

# Descargar WordPress
if [ ! -f "$WP_CORE_DIR/wp-settings.php" ]; then
    echo "Descargando WordPress $WP_VERSION..."
    download https://wordpress.org/wordpress-$WP_VERSION.tar.gz /tmp/wordpress.tar.gz
    tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C $WP_CORE_DIR
    rm /tmp/wordpress.tar.gz
else
    echo "WordPress ya existe en $WP_CORE_DIR"
fi

# Descargar test suite
if [ ! -f "$WP_TESTS_DIR/includes/functions.php" ]; then
    echo "Descargando WordPress Test Suite..."
    
    # Determinar tag de SVN
    if [[ $WP_VERSION =~ [0-9]+\.[0-9]+(\.[0-9]+)? ]]; then
        WP_TESTS_TAG="tags/$WP_VERSION"
    else
        WP_TESTS_TAG="trunk"
    fi
    
    svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/includes/ $WP_TESTS_DIR/includes
    svn co --quiet https://develop.svn.wordpress.org/${WP_TESTS_TAG}/tests/phpunit/data/ $WP_TESTS_DIR/data
else
    echo "Test suite ya existe en $WP_TESTS_DIR"
fi

# Crear wp-tests-config.php
if [ ! -f "$WP_TESTS_DIR/wp-tests-config.php" ]; then
    echo "Creando wp-tests-config.php..."
    download https://develop.svn.wordpress.org/trunk/wp-tests-config-sample.php "$WP_TESTS_DIR/wp-tests-config.php"
    
    # Configurar
    sed -i "s|dirname( __FILE__ ) . '/src/'|'$WP_CORE_DIR/'|" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/youremptytestdbnamehere/$DB_NAME/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/yourusernamehere/$DB_USER/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s/yourpasswordhere/$DB_PASS/" "$WP_TESTS_DIR/wp-tests-config.php"
    sed -i "s|localhost|$DB_HOST|" "$WP_TESTS_DIR/wp-tests-config.php"
fi

# Crear base de datos de tests
echo "Configurando base de datos de tests..."
mysql -u$DB_USER -p$DB_PASS -h$DB_HOST -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME;" 2>/dev/null || {
    echo "ADVERTENCIA: No se pudo crear la base de datos. Créala manualmente:"
    echo "  mysql -u$DB_USER -p -e 'CREATE DATABASE $DB_NAME;'"
}

echo ""
echo "=== Instalación completada ==="
echo ""
echo "Ahora configura las variables de entorno o edita phpunit.xml.dist:"
echo ""
echo "  export WP_TESTS_DIR=$WP_TESTS_DIR"
echo "  export WP_CORE_DIR=$WP_CORE_DIR"
echo ""
echo "O ejecuta los tests con:"
echo "  WP_TESTS_DIR=$WP_TESTS_DIR ./vendor/bin/phpunit --testsuite=integration"
