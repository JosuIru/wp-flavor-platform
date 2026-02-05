#!/bin/bash
# =============================================================================
# build.sh - Minificacion de assets CSS/JS para flavor-chat-ia
# Uso: bash build.sh [js|css]
# Cubre: assets/, admin/css/, admin/js/, addons/*/assets/css/, addons/*/assets/js/
# =============================================================================

set -e

DIRECTORIO_PLUGIN="$(cd "$(dirname "$0")" && pwd)"
TIPO_ASSET="${1:-all}"

CONTADOR_JS=0
CONTADOR_CSS=0
ERRORES=0

# --- Minificar un archivo JS ---
minificar_archivo_js() {
    local ARCHIVO_JS="$1"
    [ -f "${ARCHIVO_JS}" ] || return 0

    local NOMBRE_BASE="$(basename "${ARCHIVO_JS}")"

    # Ignorar archivos que ya son .min.js
    if [[ "${NOMBRE_BASE}" == *.min.js ]]; then
        return 0
    fi

    local ARCHIVO_SALIDA="${ARCHIVO_JS%.js}.min.js"
    echo "  [JS] ${ARCHIVO_JS#${DIRECTORIO_PLUGIN}/}"

    if npx terser "${ARCHIVO_JS}" --compress --mangle -o "${ARCHIVO_SALIDA}" 2>/dev/null; then
        CONTADOR_JS=$((CONTADOR_JS + 1))
    else
        echo "  [ERROR] Fallo al minificar ${NOMBRE_BASE}"
        ERRORES=$((ERRORES + 1))
    fi
}

# --- Minificar un archivo CSS ---
minificar_archivo_css() {
    local ARCHIVO_CSS="$1"
    [ -f "${ARCHIVO_CSS}" ] || return 0

    local NOMBRE_BASE="$(basename "${ARCHIVO_CSS}")"

    # Ignorar archivos que ya son .min.css
    if [[ "${NOMBRE_BASE}" == *.min.css ]]; then
        return 0
    fi

    local ARCHIVO_SALIDA="${ARCHIVO_CSS%.css}.min.css"
    echo "  [CSS] ${ARCHIVO_CSS#${DIRECTORIO_PLUGIN}/}"

    if npx cssnano-cli "${ARCHIVO_CSS}" "${ARCHIVO_SALIDA}" 2>/dev/null; then
        CONTADOR_CSS=$((CONTADOR_CSS + 1))
    else
        echo "  [ERROR] Fallo al minificar ${NOMBRE_BASE}"
        ERRORES=$((ERRORES + 1))
    fi
}

# --- Minificar todos los JS ---
minificar_javascript() {
    echo ""
    echo "===== Minificando archivos JavaScript ====="
    echo ""

    # assets/js/
    for ARCHIVO in "${DIRECTORIO_PLUGIN}"/assets/js/*.js; do
        minificar_archivo_js "${ARCHIVO}"
    done

    # admin/js/ (si existe)
    for ARCHIVO in "${DIRECTORIO_PLUGIN}"/admin/js/*.js; do
        minificar_archivo_js "${ARCHIVO}"
    done

    # addons/*/assets/js/
    for ARCHIVO in "${DIRECTORIO_PLUGIN}"/addons/*/assets/js/*.js; do
        minificar_archivo_js "${ARCHIVO}"
    done

    echo ""
    echo "  Total JS minificados: ${CONTADOR_JS}"
}

# --- Minificar todos los CSS ---
minificar_css() {
    echo ""
    echo "===== Minificando archivos CSS ====="
    echo ""

    # assets/css/
    for ARCHIVO in "${DIRECTORIO_PLUGIN}"/assets/css/*.css; do
        minificar_archivo_css "${ARCHIVO}"
    done

    # admin/css/
    for ARCHIVO in "${DIRECTORIO_PLUGIN}"/admin/css/*.css; do
        minificar_archivo_css "${ARCHIVO}"
    done

    # addons/*/assets/css/
    for ARCHIVO in "${DIRECTORIO_PLUGIN}"/addons/*/assets/css/*.css; do
        minificar_archivo_css "${ARCHIVO}"
    done

    echo ""
    echo "  Total CSS minificados: ${CONTADOR_CSS}"
}

# --- Ejecutar segun parametro ---
echo "========================================"
echo "  Build de assets - flavor-chat-ia"
echo "========================================"

case "${TIPO_ASSET}" in
    js)
        minificar_javascript
        ;;
    css)
        minificar_css
        ;;
    all|*)
        minificar_javascript
        minificar_css
        ;;
esac

echo ""
echo "========================================"
if [ "${ERRORES}" -gt 0 ]; then
    echo "  Build completado con ${ERRORES} error(es)"
    exit 1
else
    echo "  Build completado exitosamente"
fi
echo "========================================"
