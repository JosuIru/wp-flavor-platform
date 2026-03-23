#!/bin/bash
# =============================================================================
# release.sh - Script de release para Flavor Platform
#
# Uso:
#   bash scripts/release.sh [patch|minor|major]
#   bash scripts/release.sh --version=3.4.0
#
# Acciones:
#   - Bump de version en archivos del plugin
#   - Actualizacion del CHANGELOG
#   - Build de produccion
#   - Generacion de ZIP para distribucion
#   - Validaciones pre-release
#
# @package FlavorPlatform
# @since 3.3.0
# =============================================================================

set -e

# Colores para mensajes
COLOR_RESET='\033[0m'
COLOR_VERDE='\033[0;32m'
COLOR_AMARILLO='\033[0;33m'
COLOR_ROJO='\033[0;31m'
COLOR_CYAN='\033[0;36m'
COLOR_AZUL='\033[0;34m'

# Directorio del plugin
DIRECTORIO_PLUGIN="$(cd "$(dirname "$0")/.." && pwd)"
NOMBRE_PLUGIN="flavor-chat-ia"
ARCHIVO_PRINCIPAL="${DIRECTORIO_PLUGIN}/flavor-chat-ia.php"
ARCHIVO_PACKAGE="${DIRECTORIO_PLUGIN}/package.json"
ARCHIVO_CHANGELOG="${DIRECTORIO_PLUGIN}/CHANGELOG.md"
DIRECTORIO_DIST="${DIRECTORIO_PLUGIN}/dist"

# Variables de version
VERSION_ACTUAL=""
VERSION_NUEVA=""
TIPO_BUMP="${1:-patch}"

# =============================================================================
# Funciones de utilidad
# =============================================================================

imprimir_mensaje() {
    local TIPO=$1
    local MENSAJE=$2
    case $TIPO in
        info)    echo -e "${COLOR_CYAN}[INFO]${COLOR_RESET} ${MENSAJE}" ;;
        success) echo -e "${COLOR_VERDE}[OK]${COLOR_RESET} ${MENSAJE}" ;;
        error)   echo -e "${COLOR_ROJO}[ERROR]${COLOR_RESET} ${MENSAJE}" ;;
        warning) echo -e "${COLOR_AMARILLO}[WARN]${COLOR_RESET} ${MENSAJE}" ;;
        *)       echo "[LOG] ${MENSAJE}" ;;
    esac
}

imprimir_encabezado() {
    echo ""
    echo -e "${COLOR_AZUL}========================================${COLOR_RESET}"
    echo -e "${COLOR_AZUL}  Flavor Platform - Release Script${COLOR_RESET}"
    echo -e "${COLOR_AZUL}========================================${COLOR_RESET}"
    echo ""
}

# =============================================================================
# Obtener version actual
# =============================================================================

obtener_version_actual() {
    if [[ -f "${ARCHIVO_PACKAGE}" ]]; then
        VERSION_ACTUAL=$(grep '"version"' "${ARCHIVO_PACKAGE}" | head -1 | sed 's/.*"version": "\([^"]*\)".*/\1/')
    elif [[ -f "${ARCHIVO_PRINCIPAL}" ]]; then
        VERSION_ACTUAL=$(grep "Version:" "${ARCHIVO_PRINCIPAL}" | head -1 | sed 's/.*Version: \([0-9.]*\).*/\1/')
    fi

    if [[ -z "${VERSION_ACTUAL}" ]]; then
        imprimir_mensaje error "No se pudo determinar la version actual"
        exit 1
    fi

    imprimir_mensaje info "Version actual: ${VERSION_ACTUAL}"
}

# =============================================================================
# Calcular nueva version
# =============================================================================

calcular_nueva_version() {
    # Si se proporciona una version especifica
    if [[ "${TIPO_BUMP}" == --version=* ]]; then
        VERSION_NUEVA="${TIPO_BUMP#--version=}"
        imprimir_mensaje info "Version especificada: ${VERSION_NUEVA}"
        return
    fi

    # Parsear version actual (MAJOR.MINOR.PATCH)
    IFS='.' read -ra PARTES_VERSION <<< "${VERSION_ACTUAL}"
    local MAJOR="${PARTES_VERSION[0]}"
    local MINOR="${PARTES_VERSION[1]}"
    local PATCH="${PARTES_VERSION[2]}"

    case "${TIPO_BUMP}" in
        major)
            MAJOR=$((MAJOR + 1))
            MINOR=0
            PATCH=0
            ;;
        minor)
            MINOR=$((MINOR + 1))
            PATCH=0
            ;;
        patch|*)
            PATCH=$((PATCH + 1))
            ;;
    esac

    VERSION_NUEVA="${MAJOR}.${MINOR}.${PATCH}"
    imprimir_mensaje info "Nueva version (${TIPO_BUMP}): ${VERSION_NUEVA}"
}

# =============================================================================
# Validaciones pre-release
# =============================================================================

validar_pre_release() {
    imprimir_mensaje info "Ejecutando validaciones pre-release..."

    # Verificar que estamos en una rama limpia
    if [[ -n "$(git status --porcelain)" ]]; then
        imprimir_mensaje warning "Hay cambios sin commitear en el repositorio"
        read -p "Deseas continuar de todos modos? (s/N): " RESPUESTA
        if [[ "${RESPUESTA}" != "s" && "${RESPUESTA}" != "S" ]]; then
            imprimir_mensaje error "Release cancelado"
            exit 1
        fi
    fi

    # Verificar que node_modules existe
    if [[ ! -d "${DIRECTORIO_PLUGIN}/node_modules" ]]; then
        imprimir_mensaje warning "node_modules no existe. Instalando dependencias..."
        cd "${DIRECTORIO_PLUGIN}" && npm install
    fi

    # Ejecutar linting (opcional, no bloquea)
    imprimir_mensaje info "Verificando lint..."
    if command -v npm &> /dev/null; then
        cd "${DIRECTORIO_PLUGIN}"
        npm run lint:js 2>/dev/null || imprimir_mensaje warning "Lint JS: hay advertencias"
        npm run lint:css 2>/dev/null || imprimir_mensaje warning "Lint CSS: hay advertencias"
    fi

    # Verificar archivos criticos
    local ARCHIVOS_REQUERIDOS=(
        "flavor-chat-ia.php"
        "uninstall.php"
        "README.md"
    )

    for ARCHIVO in "${ARCHIVOS_REQUERIDOS[@]}"; do
        if [[ ! -f "${DIRECTORIO_PLUGIN}/${ARCHIVO}" ]]; then
            imprimir_mensaje error "Archivo requerido no encontrado: ${ARCHIVO}"
            exit 1
        fi
    done

    imprimir_mensaje success "Validaciones pre-release completadas"
}

# =============================================================================
# Actualizar version en archivos
# =============================================================================

actualizar_version_archivos() {
    imprimir_mensaje info "Actualizando version en archivos..."

    # Actualizar package.json
    if [[ -f "${ARCHIVO_PACKAGE}" ]]; then
        sed -i "s/\"version\": \"${VERSION_ACTUAL}\"/\"version\": \"${VERSION_NUEVA}\"/" "${ARCHIVO_PACKAGE}"
        imprimir_mensaje success "package.json actualizado"
    fi

    # Actualizar archivo principal del plugin
    if [[ -f "${ARCHIVO_PRINCIPAL}" ]]; then
        sed -i "s/Version: ${VERSION_ACTUAL}/Version: ${VERSION_NUEVA}/" "${ARCHIVO_PRINCIPAL}"

        # Actualizar constante de version si existe
        sed -i "s/define('FLAVOR_VERSION', '${VERSION_ACTUAL}')/define('FLAVOR_VERSION', '${VERSION_NUEVA}')/" "${ARCHIVO_PRINCIPAL}"

        imprimir_mensaje success "flavor-chat-ia.php actualizado"
    fi

    # Actualizar readme.txt si existe
    if [[ -f "${DIRECTORIO_PLUGIN}/readme.txt" ]]; then
        sed -i "s/Stable tag: ${VERSION_ACTUAL}/Stable tag: ${VERSION_NUEVA}/" "${DIRECTORIO_PLUGIN}/readme.txt"
        imprimir_mensaje success "readme.txt actualizado"
    fi
}

# =============================================================================
# Actualizar CHANGELOG
# =============================================================================

actualizar_changelog() {
    imprimir_mensaje info "Actualizando CHANGELOG.md..."

    local FECHA_HOY=$(date +"%Y-%m-%d")
    local ENTRADA_CHANGELOG="## [${VERSION_NUEVA}] - ${FECHA_HOY}\n\n### Cambios\n- Release ${VERSION_NUEVA}\n\n"

    if [[ -f "${ARCHIVO_CHANGELOG}" ]]; then
        # Insertar nueva entrada despues del encabezado
        sed -i "s/# Changelog/# Changelog\n\n${ENTRADA_CHANGELOG}/" "${ARCHIVO_CHANGELOG}"
        imprimir_mensaje success "CHANGELOG.md actualizado"
    else
        # Crear CHANGELOG si no existe
        echo -e "# Changelog\n\nTodos los cambios notables del proyecto.\n\n${ENTRADA_CHANGELOG}" > "${ARCHIVO_CHANGELOG}"
        imprimir_mensaje success "CHANGELOG.md creado"
    fi
}

# =============================================================================
# Build de produccion
# =============================================================================

ejecutar_build_produccion() {
    imprimir_mensaje info "Ejecutando build de produccion..."

    cd "${DIRECTORIO_PLUGIN}"

    # Build de assets
    if [[ -f "scripts/build.js" ]]; then
        node scripts/build.js --mode=production
    elif [[ -f "build.sh" ]]; then
        bash build.sh
    fi

    imprimir_mensaje success "Build de produccion completado"
}

# =============================================================================
# Generar ZIP de distribucion
# =============================================================================

generar_zip_distribucion() {
    imprimir_mensaje info "Generando ZIP de distribucion..."

    # Crear directorio dist si no existe
    mkdir -p "${DIRECTORIO_DIST}"

    local ARCHIVO_ZIP="${DIRECTORIO_DIST}/${NOMBRE_PLUGIN}-${VERSION_NUEVA}.zip"
    local DIRECTORIO_TEMPORAL="${DIRECTORIO_DIST}/${NOMBRE_PLUGIN}"

    # Limpiar directorio temporal si existe
    rm -rf "${DIRECTORIO_TEMPORAL}"
    mkdir -p "${DIRECTORIO_TEMPORAL}"

    # Copiar archivos del plugin (excluyendo desarrollo)
    rsync -av --progress "${DIRECTORIO_PLUGIN}/" "${DIRECTORIO_TEMPORAL}/" \
        --exclude='.git' \
        --exclude='.gitignore' \
        --exclude='.github' \
        --exclude='.husky' \
        --exclude='.claude' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='tests' \
        --exclude='dev-scripts' \
        --exclude='scripts' \
        --exclude='docs' \
        --exclude='reports' \
        --exclude='dist' \
        --exclude='*.md' \
        --exclude='*.log' \
        --exclude='*.map' \
        --exclude='package.json' \
        --exclude='package-lock.json' \
        --exclude='composer.json' \
        --exclude='composer.lock' \
        --exclude='phpunit.xml' \
        --exclude='postcss.config.js' \
        --exclude='.eslintrc*' \
        --exclude='.stylelintrc*' \
        --exclude='jest.config.js' \
        --exclude='*.php' --include='*.php' \
        2>/dev/null

    # Copiar README.md (es necesario para WordPress)
    cp "${DIRECTORIO_PLUGIN}/README.md" "${DIRECTORIO_TEMPORAL}/" 2>/dev/null || true

    # Crear ZIP
    cd "${DIRECTORIO_DIST}"
    rm -f "${ARCHIVO_ZIP}"
    zip -r "${ARCHIVO_ZIP}" "${NOMBRE_PLUGIN}"

    # Limpiar directorio temporal
    rm -rf "${DIRECTORIO_TEMPORAL}"

    local TAMANO_ZIP=$(du -h "${ARCHIVO_ZIP}" | cut -f1)
    imprimir_mensaje success "ZIP generado: ${ARCHIVO_ZIP} (${TAMANO_ZIP})"
}

# =============================================================================
# Crear tag de Git
# =============================================================================

crear_tag_git() {
    imprimir_mensaje info "Creando tag de Git..."

    read -p "Deseas crear un tag de Git para v${VERSION_NUEVA}? (s/N): " RESPUESTA

    if [[ "${RESPUESTA}" == "s" || "${RESPUESTA}" == "S" ]]; then
        # Commitear cambios de version
        cd "${DIRECTORIO_PLUGIN}"
        git add -A
        git commit -m "release: v${VERSION_NUEVA}"

        # Crear tag
        git tag -a "v${VERSION_NUEVA}" -m "Release v${VERSION_NUEVA}"

        imprimir_mensaje success "Tag v${VERSION_NUEVA} creado"
        imprimir_mensaje info "Ejecuta 'git push && git push --tags' para publicar"
    else
        imprimir_mensaje info "Tag de Git omitido"
    fi
}

# =============================================================================
# Resumen final
# =============================================================================

imprimir_resumen() {
    echo ""
    echo -e "${COLOR_AZUL}========================================${COLOR_RESET}"
    echo -e "${COLOR_AZUL}  Release Completado${COLOR_RESET}"
    echo -e "${COLOR_AZUL}========================================${COLOR_RESET}"
    echo -e "  Version anterior: ${VERSION_ACTUAL}"
    echo -e "  Version nueva: ${COLOR_VERDE}${VERSION_NUEVA}${COLOR_RESET}"
    echo -e "  ZIP: dist/${NOMBRE_PLUGIN}-${VERSION_NUEVA}.zip"
    echo -e "${COLOR_AZUL}========================================${COLOR_RESET}"
    echo ""
}

# =============================================================================
# Funcion principal
# =============================================================================

main() {
    imprimir_encabezado

    obtener_version_actual
    calcular_nueva_version

    echo ""
    read -p "Confirmar release ${VERSION_ACTUAL} -> ${VERSION_NUEVA}? (s/N): " CONFIRMACION
    if [[ "${CONFIRMACION}" != "s" && "${CONFIRMACION}" != "S" ]]; then
        imprimir_mensaje error "Release cancelado por el usuario"
        exit 1
    fi
    echo ""

    validar_pre_release
    actualizar_version_archivos
    actualizar_changelog
    ejecutar_build_produccion
    generar_zip_distribucion
    crear_tag_git
    imprimir_resumen
}

# Ejecutar script
main
