#!/bin/bash
#
# Script de extracción de strings para traducción
# Genera archivo POT actualizado usando WP-CLI
#
# Uso: bash scripts/extract-strings.sh
#
# @package Flavor_Platform
# @since 2.3.0

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directorio base del plugin
PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LANGUAGES_DIR="${PLUGIN_DIR}/languages"
POT_FILE="${LANGUAGES_DIR}/flavor-platform.pot"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Extracción de Strings para i18n${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Verificar que wp-cli está disponible
if ! command -v wp &> /dev/null; then
    echo -e "${RED}Error: WP-CLI no está instalado o no está en el PATH${NC}"
    echo "Instala WP-CLI: https://wp-cli.org/#installing"
    exit 1
fi

# Verificar directorio de idiomas
if [ ! -d "$LANGUAGES_DIR" ]; then
    echo -e "${YELLOW}Creando directorio de idiomas...${NC}"
    mkdir -p "$LANGUAGES_DIR"
fi

# Backup del POT actual si existe
if [ -f "$POT_FILE" ]; then
    echo -e "${YELLOW}Guardando backup del POT actual...${NC}"
    cp "$POT_FILE" "${POT_FILE}.bak"
fi

echo -e "${GREEN}Extrayendo strings de PHP...${NC}"

# Generar archivo POT
cd "$PLUGIN_DIR"
wp i18n make-pot . "$POT_FILE" \
    --domain=flavor-platform \
    --exclude="node_modules,vendor,tests,archive,.git,mobile-apps/node_modules" \
    --include="*.php,includes/**/*.php,admin/**/*.php,templates/**/*.php" \
    --headers='{"Report-Msgid-Bugs-To": "https://github.com/gailu-labs/flavor-platform/issues", "Last-Translator": "Gailu Labs <soporte@gailu.net>", "Language-Team": "Gailu Labs <soporte@gailu.net>"}' \
    --skip-audit \
    2>/dev/null || {
        echo -e "${YELLOW}Advertencia: wp i18n make-pot tuvo algunos warnings${NC}"
    }

# Contar strings extraídos
if [ -f "$POT_FILE" ]; then
    STRING_COUNT=$(grep -c "^msgid " "$POT_FILE" || echo "0")
    echo -e "${GREEN}✓ Archivo POT generado: ${POT_FILE}${NC}"
    echo -e "${GREEN}  Total de strings: ${STRING_COUNT}${NC}"
else
    echo -e "${RED}Error: No se pudo generar el archivo POT${NC}"
    exit 1
fi

# Actualizar archivos PO existentes
echo ""
echo -e "${GREEN}Actualizando traducciones existentes...${NC}"

for PO_FILE in "$LANGUAGES_DIR"/*.po; do
    if [ -f "$PO_FILE" ]; then
        LANG=$(basename "$PO_FILE" .po | sed 's/flavor-platform-//')
        echo -e "  ${BLUE}Actualizando ${LANG}...${NC}"

        # Usar msgmerge para actualizar el PO con nuevos strings del POT
        if command -v msgmerge &> /dev/null; then
            msgmerge --update --no-fuzzy-matching "$PO_FILE" "$POT_FILE" 2>/dev/null || {
                echo -e "  ${YELLOW}Advertencia: msgmerge tuvo warnings para ${LANG}${NC}"
            }
        else
            echo -e "  ${YELLOW}msgmerge no disponible, saltando actualización de ${LANG}${NC}"
        fi
    fi
done

# Compilar archivos MO si msgfmt está disponible
echo ""
echo -e "${GREEN}Compilando archivos MO...${NC}"

if command -v msgfmt &> /dev/null; then
    for PO_FILE in "$LANGUAGES_DIR"/*.po; do
        if [ -f "$PO_FILE" ]; then
            MO_FILE="${PO_FILE%.po}.mo"
            LANG=$(basename "$PO_FILE" .po | sed 's/flavor-platform-//')
            echo -e "  ${BLUE}Compilando ${LANG}...${NC}"
            msgfmt "$PO_FILE" -o "$MO_FILE" 2>/dev/null || {
                echo -e "  ${YELLOW}Advertencia: msgfmt tuvo warnings para ${LANG}${NC}"
            }
        fi
    done
else
    echo -e "${YELLOW}msgfmt no disponible. Instala gettext para compilar archivos MO.${NC}"
fi

# Generar archivo JSON para JavaScript (si wp i18n lo soporta)
echo ""
echo -e "${GREEN}Generando traducciones JSON para JavaScript...${NC}"

cd "$PLUGIN_DIR"
wp i18n make-json "$LANGUAGES_DIR" --no-purge 2>/dev/null || {
    echo -e "${YELLOW}Advertencia: No se pudieron generar traducciones JSON${NC}"
}

# Resumen final
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}✓ Extracción completada${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo "Archivos generados/actualizados:"
echo "  - $POT_FILE"
ls -la "$LANGUAGES_DIR"/*.po 2>/dev/null || true
echo ""
echo "Próximos pasos:"
echo "  1. Revisa los nuevos strings en el archivo POT"
echo "  2. Traduce los strings pendientes en los archivos PO"
echo "  3. Compila con: msgfmt <archivo.po> -o <archivo.mo>"
echo ""
