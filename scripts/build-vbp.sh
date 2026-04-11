#!/bin/bash
#
# VBP Build Script
#
# Script de conveniencia para ejecutar el build de Visual Builder Pro.
#
# Uso:
#   ./scripts/build-vbp.sh              # Build de produccion
#   ./scripts/build-vbp.sh dev          # Build de desarrollo
#   ./scripts/build-vbp.sh analyze      # Analizar tamanos
#   ./scripts/build-vbp.sh clean        # Limpiar dist
#
# @package Flavor_Platform
# @since 3.5.0

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directorio del plugin
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VBP_BUILD_DIR="${PLUGIN_DIR}/assets/vbp/build"
VBP_DIST_DIR="${PLUGIN_DIR}/assets/vbp/dist"

# Verificar que estamos en el directorio correcto
if [ ! -f "${PLUGIN_DIR}/flavor-platform.php" ]; then
    echo -e "${RED}Error: No se encontro flavor-platform.php${NC}"
    echo "Ejecuta este script desde el directorio del plugin"
    exit 1
fi

# Verificar Node.js
if ! command -v node &> /dev/null; then
    echo -e "${RED}Error: Node.js no esta instalado${NC}"
    echo "Instala Node.js 18+ para continuar"
    exit 1
fi

# Verificar version de Node
NODE_VERSION=$(node -v | cut -d'v' -f2 | cut -d'.' -f1)
if [ "$NODE_VERSION" -lt 18 ]; then
    echo -e "${YELLOW}Advertencia: Se recomienda Node.js 18+${NC}"
    echo "Version actual: $(node -v)"
fi

# Verificar dependencias
check_dependencies() {
    echo -e "${BLUE}Verificando dependencias...${NC}"

    cd "${PLUGIN_DIR}"

    if [ ! -d "node_modules" ]; then
        echo -e "${YELLOW}Instalando dependencias npm...${NC}"
        npm install
    fi

    # Verificar terser y postcss
    if [ ! -f "node_modules/.bin/terser" ] || [ ! -f "node_modules/postcss/package.json" ]; then
        echo -e "${YELLOW}Algunas dependencias faltan, reinstalando...${NC}"
        npm install
    fi

    echo -e "${GREEN}Dependencias OK${NC}"
}

# Limpiar directorio dist
clean_dist() {
    echo -e "${BLUE}Limpiando directorio dist...${NC}"

    if [ -d "${VBP_DIST_DIR}" ]; then
        rm -rf "${VBP_DIST_DIR}"
        echo -e "${GREEN}Directorio dist eliminado${NC}"
    else
        echo "Directorio dist no existe, nada que limpiar"
    fi
}

# Build de produccion
build_production() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  VBP Build - Produccion${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    check_dependencies

    # Crear directorio dist
    mkdir -p "${VBP_DIST_DIR}"

    # Ejecutar build
    cd "${VBP_BUILD_DIR}"
    node vbp-build.js --mode=production

    echo ""
    echo -e "${GREEN}Build de produccion completado${NC}"
    echo -e "Bundles en: ${VBP_DIST_DIR}"
}

# Build de desarrollo
build_development() {
    echo ""
    echo -e "${YELLOW}========================================${NC}"
    echo -e "${YELLOW}  VBP Build - Desarrollo${NC}"
    echo -e "${YELLOW}========================================${NC}"
    echo ""

    check_dependencies

    # Crear directorio dist
    mkdir -p "${VBP_DIST_DIR}"

    # Ejecutar build
    cd "${VBP_BUILD_DIR}"
    node vbp-build.js --mode=development

    echo ""
    echo -e "${GREEN}Build de desarrollo completado${NC}"
}

# Analizar tamanos
analyze_bundles() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  VBP Build - Analisis${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    check_dependencies

    mkdir -p "${VBP_DIST_DIR}"

    cd "${VBP_BUILD_DIR}"
    node vbp-build.js --mode=production --analyze
}

# Build de un bundle especifico
build_bundle() {
    local BUNDLE_NAME=$1

    echo ""
    echo -e "${BLUE}Construyendo bundle: ${BUNDLE_NAME}${NC}"
    echo ""

    check_dependencies

    mkdir -p "${VBP_DIST_DIR}"

    cd "${VBP_BUILD_DIR}"
    node vbp-build.js --mode=production --bundle="${BUNDLE_NAME}"
}

# Solo generar manifiesto
build_manifest() {
    echo ""
    echo -e "${BLUE}Generando solo manifiesto...${NC}"
    echo ""

    check_dependencies

    cd "${VBP_BUILD_DIR}"
    node vbp-build.js --manifest-only
}

# Mostrar ayuda
show_help() {
    echo ""
    echo "VBP Build Script - Construye bundles optimizados para Visual Builder Pro"
    echo ""
    echo "Uso: $0 [comando] [opciones]"
    echo ""
    echo "Comandos:"
    echo "  (sin args)    Build de produccion completo"
    echo "  dev           Build de desarrollo (con sourcemaps)"
    echo "  analyze       Analizar tamanos de bundles"
    echo "  clean         Limpiar directorio dist"
    echo "  manifest      Solo generar manifiesto"
    echo "  bundle NAME   Construir un bundle especifico"
    echo "  help          Mostrar esta ayuda"
    echo ""
    echo "Ejemplos:"
    echo "  $0                    # Build produccion"
    echo "  $0 dev                # Build desarrollo"
    echo "  $0 bundle vbp-core    # Solo bundle core"
    echo "  $0 analyze            # Ver analisis de tamanos"
    echo ""
}

# Procesar argumentos
case "${1:-}" in
    "dev"|"development")
        build_development
        ;;
    "analyze"|"analysis")
        analyze_bundles
        ;;
    "clean")
        clean_dist
        ;;
    "manifest")
        build_manifest
        ;;
    "bundle")
        if [ -z "${2:-}" ]; then
            echo -e "${RED}Error: Especifica el nombre del bundle${NC}"
            echo "Ejemplo: $0 bundle vbp-core"
            exit 1
        fi
        build_bundle "$2"
        ;;
    "help"|"-h"|"--help")
        show_help
        ;;
    "")
        build_production
        ;;
    *)
        echo -e "${RED}Comando desconocido: $1${NC}"
        show_help
        exit 1
        ;;
esac
