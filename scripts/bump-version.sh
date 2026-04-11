#!/bin/bash
# =============================================================================
# bump-version.sh - Actualiza la version en todos los archivos del plugin
#
# Uso:
#   bash scripts/bump-version.sh 3.6.0
#   bash scripts/bump-version.sh patch    # 3.5.0 -> 3.5.1
#   bash scripts/bump-version.sh minor    # 3.5.0 -> 3.6.0
#   bash scripts/bump-version.sh major    # 3.5.0 -> 4.0.0
#   bash scripts/bump-version.sh --check  # Solo verificar versiones actuales
#
# @package FlavorPlatform
# @since 3.5.0
# =============================================================================

set -e

# Colores para mensajes
COLOR_RESET='\033[0m'
COLOR_VERDE='\033[0;32m'
COLOR_AMARILLO='\033[0;33m'
COLOR_ROJO='\033[0;31m'
COLOR_CYAN='\033[0;36m'

# Directorio del plugin
DIRECTORIO_PLUGIN="$(cd "$(dirname "$0")/.." && pwd)"

# Archivos a actualizar
ARCHIVOS_VERSION=(
    "flavor-platform.php"
    "package.json"
    "includes/visual-builder-pro/class-vbp-loader.php"
)

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

mostrar_ayuda() {
    echo ""
    echo "Uso: bash scripts/bump-version.sh [version|patch|minor|major|--check]"
    echo ""
    echo "Ejemplos:"
    echo "  bash scripts/bump-version.sh 3.6.0   # Version especifica"
    echo "  bash scripts/bump-version.sh patch   # Incrementa patch: 3.5.0 -> 3.5.1"
    echo "  bash scripts/bump-version.sh minor   # Incrementa minor: 3.5.0 -> 3.6.0"
    echo "  bash scripts/bump-version.sh major   # Incrementa major: 3.5.0 -> 4.0.0"
    echo "  bash scripts/bump-version.sh --check # Solo muestra versiones actuales"
    echo ""
}

# =============================================================================
# Obtener version actual
# =============================================================================

obtener_version_actual() {
    local ARCHIVO="${DIRECTORIO_PLUGIN}/package.json"

    if [[ -f "${ARCHIVO}" ]]; then
        grep '"version"' "${ARCHIVO}" | head -1 | sed 's/.*"version": "\([^"]*\)".*/\1/'
    else
        echo "0.0.0"
    fi
}

# =============================================================================
# Calcular nueva version
# =============================================================================

calcular_nueva_version() {
    local VERSION_ACTUAL=$1
    local TIPO=$2

    IFS='.' read -ra PARTES <<< "${VERSION_ACTUAL}"
    local MAJOR="${PARTES[0]:-0}"
    local MINOR="${PARTES[1]:-0}"
    local PATCH="${PARTES[2]:-0}"

    case "${TIPO}" in
        major)
            MAJOR=$((MAJOR + 1))
            MINOR=0
            PATCH=0
            ;;
        minor)
            MINOR=$((MINOR + 1))
            PATCH=0
            ;;
        patch)
            PATCH=$((PATCH + 1))
            ;;
        *)
            # Asumir que es una version especifica
            echo "${TIPO}"
            return
            ;;
    esac

    echo "${MAJOR}.${MINOR}.${PATCH}"
}

# =============================================================================
# Verificar versiones en archivos
# =============================================================================

verificar_versiones() {
    imprimir_mensaje info "Verificando versiones actuales..."
    echo ""

    for ARCHIVO_RELATIVO in "${ARCHIVOS_VERSION[@]}"; do
        local ARCHIVO="${DIRECTORIO_PLUGIN}/${ARCHIVO_RELATIVO}"

        if [[ ! -f "${ARCHIVO}" ]]; then
            imprimir_mensaje warning "${ARCHIVO_RELATIVO}: Archivo no encontrado"
            continue
        fi

        local VERSION=""

        case "${ARCHIVO_RELATIVO}" in
            *.php)
                # Buscar Version: en header o define FLAVOR_PLATFORM_VERSION
                VERSION=$(grep -E "Version:|FLAVOR_PLATFORM_VERSION|VBP_VERSION" "${ARCHIVO}" | head -1 | grep -oE "[0-9]+\.[0-9]+\.[0-9]+")
                ;;
            package.json)
                VERSION=$(grep '"version"' "${ARCHIVO}" | head -1 | sed 's/.*"version": "\([^"]*\)".*/\1/')
                ;;
            *)
                VERSION="N/A"
                ;;
        esac

        if [[ -n "${VERSION}" ]]; then
            imprimir_mensaje success "${ARCHIVO_RELATIVO}: ${VERSION}"
        else
            imprimir_mensaje warning "${ARCHIVO_RELATIVO}: Version no encontrada"
        fi
    done

    echo ""
}

# =============================================================================
# Actualizar version en archivo
# =============================================================================

actualizar_archivo() {
    local ARCHIVO=$1
    local VERSION_VIEJA=$2
    local VERSION_NUEVA=$3

    if [[ ! -f "${ARCHIVO}" ]]; then
        imprimir_mensaje warning "Archivo no existe: ${ARCHIVO}"
        return 1
    fi

    local ARCHIVO_RELATIVO="${ARCHIVO#$DIRECTORIO_PLUGIN/}"
    local CAMBIOS_REALIZADOS=0

    case "${ARCHIVO}" in
        *.php)
            # Actualizar header del plugin: Version: X.Y.Z
            if grep -q "Version: ${VERSION_VIEJA}" "${ARCHIVO}"; then
                sed -i "s/Version: ${VERSION_VIEJA}/Version: ${VERSION_NUEVA}/g" "${ARCHIVO}"
                CAMBIOS_REALIZADOS=$((CAMBIOS_REALIZADOS + 1))
            fi

            # Actualizar constantes FLAVOR_PLATFORM_VERSION
            if grep -q "FLAVOR_PLATFORM_VERSION', '${VERSION_VIEJA}'" "${ARCHIVO}"; then
                sed -i "s/FLAVOR_PLATFORM_VERSION', '${VERSION_VIEJA}'/FLAVOR_PLATFORM_VERSION', '${VERSION_NUEVA}'/g" "${ARCHIVO}"
                CAMBIOS_REALIZADOS=$((CAMBIOS_REALIZADOS + 1))
            fi

            # Actualizar constantes VBP_VERSION
            if grep -q "VBP_VERSION', '${VERSION_VIEJA}'" "${ARCHIVO}"; then
                sed -i "s/VBP_VERSION', '${VERSION_VIEJA}'/VBP_VERSION', '${VERSION_NUEVA}'/g" "${ARCHIVO}"
                CAMBIOS_REALIZADOS=$((CAMBIOS_REALIZADOS + 1))
            fi

            # Actualizar @version en PHPDoc
            if grep -q "@version ${VERSION_VIEJA}" "${ARCHIVO}"; then
                sed -i "s/@version ${VERSION_VIEJA}/@version ${VERSION_NUEVA}/g" "${ARCHIVO}"
                CAMBIOS_REALIZADOS=$((CAMBIOS_REALIZADOS + 1))
            fi
            ;;

        package.json)
            # Actualizar "version": "X.Y.Z"
            if grep -q "\"version\": \"${VERSION_VIEJA}\"" "${ARCHIVO}"; then
                sed -i "s/\"version\": \"${VERSION_VIEJA}\"/\"version\": \"${VERSION_NUEVA}\"/g" "${ARCHIVO}"
                CAMBIOS_REALIZADOS=$((CAMBIOS_REALIZADOS + 1))
            fi
            ;;

        *)
            imprimir_mensaje warning "Tipo de archivo no soportado: ${ARCHIVO}"
            return 1
            ;;
    esac

    if [[ ${CAMBIOS_REALIZADOS} -gt 0 ]]; then
        imprimir_mensaje success "${ARCHIVO_RELATIVO}: Actualizado (${CAMBIOS_REALIZADOS} cambios)"
    else
        imprimir_mensaje warning "${ARCHIVO_RELATIVO}: Sin cambios"
    fi

    return 0
}

# =============================================================================
# Actualizar todos los archivos
# =============================================================================

actualizar_todos_los_archivos() {
    local VERSION_VIEJA=$1
    local VERSION_NUEVA=$2

    imprimir_mensaje info "Actualizando archivos..."
    echo ""

    local ARCHIVOS_ACTUALIZADOS=0
    local ARCHIVOS_FALLIDOS=0

    for ARCHIVO_RELATIVO in "${ARCHIVOS_VERSION[@]}"; do
        local ARCHIVO="${DIRECTORIO_PLUGIN}/${ARCHIVO_RELATIVO}"

        if actualizar_archivo "${ARCHIVO}" "${VERSION_VIEJA}" "${VERSION_NUEVA}"; then
            ARCHIVOS_ACTUALIZADOS=$((ARCHIVOS_ACTUALIZADOS + 1))
        else
            ARCHIVOS_FALLIDOS=$((ARCHIVOS_FALLIDOS + 1))
        fi
    done

    echo ""
    imprimir_mensaje info "Archivos actualizados: ${ARCHIVOS_ACTUALIZADOS}"

    if [[ ${ARCHIVOS_FALLIDOS} -gt 0 ]]; then
        imprimir_mensaje warning "Archivos con errores: ${ARCHIVOS_FALLIDOS}"
    fi
}

# =============================================================================
# Actualizar CHANGELOG con fecha si hay seccion Unreleased
# =============================================================================

actualizar_changelog_fecha() {
    local VERSION=$1
    local FECHA_HOY=$(date +"%Y-%m-%d")
    local ARCHIVO_CHANGELOG="${DIRECTORIO_PLUGIN}/CHANGELOG.md"

    if [[ ! -f "${ARCHIVO_CHANGELOG}" ]]; then
        imprimir_mensaje warning "CHANGELOG.md no encontrado"
        return
    fi

    # Buscar si existe [Unreleased] y tiene contenido
    if grep -q "## \[Unreleased\]" "${ARCHIVO_CHANGELOG}"; then
        # Verificar si hay contenido despues de [Unreleased]
        local CONTENIDO_UNRELEASED=$(sed -n '/## \[Unreleased\]/,/## \[/p' "${ARCHIVO_CHANGELOG}" | head -n -1 | tail -n +2 | grep -v "^$" | grep -v "^### " | head -1)

        if [[ -n "${CONTENIDO_UNRELEASED}" ]]; then
            imprimir_mensaje info "Encontrado contenido en [Unreleased], considera actualizar CHANGELOG.md manualmente"
        fi
    fi
}

# =============================================================================
# Funcion principal
# =============================================================================

main() {
    local ARGUMENTO="${1:-}"

    # Mostrar ayuda
    if [[ "${ARGUMENTO}" == "-h" || "${ARGUMENTO}" == "--help" || -z "${ARGUMENTO}" ]]; then
        mostrar_ayuda
        exit 0
    fi

    # Solo verificar
    if [[ "${ARGUMENTO}" == "--check" ]]; then
        verificar_versiones
        exit 0
    fi

    # Obtener version actual
    local VERSION_ACTUAL=$(obtener_version_actual)
    imprimir_mensaje info "Version actual: ${VERSION_ACTUAL}"

    # Calcular nueva version
    local VERSION_NUEVA
    if [[ "${ARGUMENTO}" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        # Version especifica proporcionada
        VERSION_NUEVA="${ARGUMENTO}"
    else
        # Calcular basado en tipo (patch, minor, major)
        VERSION_NUEVA=$(calcular_nueva_version "${VERSION_ACTUAL}" "${ARGUMENTO}")
    fi

    imprimir_mensaje info "Nueva version: ${VERSION_NUEVA}"
    echo ""

    # Confirmar
    read -p "Actualizar de ${VERSION_ACTUAL} a ${VERSION_NUEVA}? (s/N): " CONFIRMACION
    if [[ "${CONFIRMACION}" != "s" && "${CONFIRMACION}" != "S" ]]; then
        imprimir_mensaje error "Operacion cancelada"
        exit 1
    fi
    echo ""

    # Actualizar archivos
    actualizar_todos_los_archivos "${VERSION_ACTUAL}" "${VERSION_NUEVA}"

    # Verificar CHANGELOG
    actualizar_changelog_fecha "${VERSION_NUEVA}"

    # Verificar cambios
    echo ""
    imprimir_mensaje info "Verificando cambios..."
    verificar_versiones

    # Instrucciones siguientes
    echo ""
    echo "Siguiente paso:"
    echo "  1. Revisar cambios: git diff"
    echo "  2. Actualizar CHANGELOG.md si es necesario"
    echo "  3. Commit: git add -A && git commit -m 'release: v${VERSION_NUEVA}'"
    echo "  4. Tag: git tag -a v${VERSION_NUEVA} -m 'Release v${VERSION_NUEVA}'"
    echo ""
}

# Ejecutar
main "$@"
