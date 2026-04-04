#!/bin/bash
# =============================================================================
# Flavor Platform - Validador de Sitio
#
# USO: ./validate-site.sh <URL_SITIO> [<RUTA_WP>]
#
# Este script verifica que un sitio esté correctamente configurado
# antes de hacer modificaciones. Claude Code DEBE ejecutar esto primero.
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

SITE_URL="${1:-}"
WP_PATH="${2:-$(pwd)}"
API_KEY="${3:-}"  # Opcional: se puede pasar como tercer argumento
ERRORS=0
WARNINGS=0

# Obtener API key dinámicamente si no se proporcionó
if [ -z "$API_KEY" ]; then
    if command -v wp &> /dev/null && [ -f "$WP_PATH/wp-config.php" ]; then
        API_KEY=$(cd "$WP_PATH" && wp eval "echo flavor_get_vbp_api_key();" 2>/dev/null || echo "")
    fi
    if [ -z "$API_KEY" ]; then
        echo -e "${RED}ERROR: No se pudo obtener la API key automáticamente.${NC}"
        echo "Pasa la key como 3er argumento: ./validate-site.sh URL RUTA_WP API_KEY"
        exit 1
    fi
fi

echo "=============================================="
echo "  Flavor Platform - Validador de Sitio"
echo "=============================================="
echo ""

if [ -z "$SITE_URL" ]; then
    echo -e "${RED}ERROR: Debes proporcionar la URL del sitio${NC}"
    echo "Uso: ./validate-site.sh https://mi-sitio.local [/ruta/wp]"
    exit 1
fi

echo "Sitio: $SITE_URL"
echo "Ruta WP: $WP_PATH"
echo ""

# -----------------------------------------------------------------------------
# 1. Verificar que estamos en un directorio WordPress
# -----------------------------------------------------------------------------
echo "1. Verificando estructura WordPress..."
if [ -f "$WP_PATH/wp-config.php" ]; then
    echo -e "   ${GREEN}✓${NC} wp-config.php encontrado"
else
    echo -e "   ${RED}✗${NC} wp-config.php NO encontrado"
    ((ERRORS++))
fi

# -----------------------------------------------------------------------------
# 2. Verificar plugin Flavor activo
# -----------------------------------------------------------------------------
echo ""
echo "2. Verificando plugin Flavor..."
if [ -d "$WP_PATH/wp-content/plugins/flavor-chat-ia" ]; then
    echo -e "   ${GREEN}✓${NC} Plugin instalado"

    # Verificar si está activo via WP-CLI
    if command -v wp &> /dev/null; then
        cd "$WP_PATH"
        if wp plugin is-active flavor-chat-ia 2>/dev/null; then
            echo -e "   ${GREEN}✓${NC} Plugin activo"
        else
            echo -e "   ${RED}✗${NC} Plugin NO activo"
            ((ERRORS++))
        fi
    fi
else
    echo -e "   ${RED}✗${NC} Plugin NO instalado"
    ((ERRORS++))
fi

# -----------------------------------------------------------------------------
# 3. Verificar tema flavor-starter
# -----------------------------------------------------------------------------
echo ""
echo "3. Verificando tema flavor-starter..."
if [ -d "$WP_PATH/wp-content/themes/flavor-starter" ]; then
    echo -e "   ${GREEN}✓${NC} Tema instalado"

    if command -v wp &> /dev/null; then
        cd "$WP_PATH"
        CURRENT_THEME=$(wp option get template 2>/dev/null || echo "unknown")
        if [ "$CURRENT_THEME" = "flavor-starter" ]; then
            echo -e "   ${GREEN}✓${NC} Tema activo"
        else
            echo -e "   ${YELLOW}!${NC} Tema instalado pero no activo (actual: $CURRENT_THEME)"
            ((WARNINGS++))
        fi
    fi
else
    echo -e "   ${RED}✗${NC} Tema NO instalado"
    echo -e "   ${YELLOW}→${NC} Debes instalar flavor-starter antes de continuar"
    ((ERRORS++))
fi

# -----------------------------------------------------------------------------
# 4. Verificar API disponible
# -----------------------------------------------------------------------------
echo ""
echo "4. Verificando API Site Builder..."
API_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" \
    "$SITE_URL/wp-json/flavor-site-builder/v1/system/health" \
    -H "X-VBP-Key: $API_KEY" 2>/dev/null || echo "000")

if [ "$API_RESPONSE" = "200" ]; then
    echo -e "   ${GREEN}✓${NC} API accesible (HTTP $API_RESPONSE)"
else
    echo -e "   ${RED}✗${NC} API no accesible (HTTP $API_RESPONSE)"
    ((ERRORS++))
fi

# -----------------------------------------------------------------------------
# 5. Verificar Visual Builder Pro
# -----------------------------------------------------------------------------
echo ""
echo "5. Verificando Visual Builder Pro..."
VBP_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" \
    "$SITE_URL/wp-json/flavor-vbp/v1/blocks" \
    -H "X-VBP-Key: $API_KEY" 2>/dev/null || echo "000")

if [ "$VBP_RESPONSE" = "200" ]; then
    echo -e "   ${GREEN}✓${NC} VBP API accesible"
else
    echo -e "   ${YELLOW}!${NC} VBP API no responde (HTTP $VBP_RESPONSE)"
    ((WARNINGS++))
fi

# -----------------------------------------------------------------------------
# 6. Verificar módulos activos
# -----------------------------------------------------------------------------
echo ""
echo "6. Verificando módulos..."
if command -v wp &> /dev/null; then
    cd "$WP_PATH"
    MODULES=$(wp option get flavor_active_modules --format=json 2>/dev/null || echo "[]")
    MODULE_COUNT=$(echo "$MODULES" | grep -o '"' | wc -l)
    MODULE_COUNT=$((MODULE_COUNT / 2))

    if [ "$MODULE_COUNT" -gt 0 ]; then
        echo -e "   ${GREEN}✓${NC} $MODULE_COUNT módulos activos"

        if [ "$MODULE_COUNT" -gt 20 ]; then
            echo -e "   ${YELLOW}!${NC} Demasiados módulos activos (recomendado: <20)"
            ((WARNINGS++))
        fi
    else
        echo -e "   ${YELLOW}!${NC} Ningún módulo activo"
        ((WARNINGS++))
    fi
fi

# -----------------------------------------------------------------------------
# 7. Verificar menús
# -----------------------------------------------------------------------------
echo ""
echo "7. Verificando menús..."
if command -v wp &> /dev/null; then
    cd "$WP_PATH"
    MENU_LOCATIONS=$(wp menu location list --format=json 2>/dev/null || echo "[]")
    ASSIGNED=$(echo "$MENU_LOCATIONS" | grep -c '"menu":' || echo "0")

    if [ "$ASSIGNED" -gt 0 ]; then
        echo -e "   ${GREEN}✓${NC} $ASSIGNED ubicaciones con menú asignado"
    else
        echo -e "   ${YELLOW}!${NC} Ningún menú asignado a ubicaciones"
        ((WARNINGS++))
    fi
fi

# -----------------------------------------------------------------------------
# 8. Verificar páginas con VBP
# -----------------------------------------------------------------------------
echo ""
echo "8. Verificando páginas con Visual Builder..."
if command -v wp &> /dev/null; then
    cd "$WP_PATH"
    VBP_PAGES=$(wp db query "SELECT COUNT(*) FROM wp_postmeta WHERE meta_key='_vbp_content'" --skip-column-names 2>/dev/null || echo "0")

    if [ "$VBP_PAGES" -gt 0 ]; then
        echo -e "   ${GREEN}✓${NC} $VBP_PAGES páginas usan Visual Builder"
    else
        echo -e "   ${YELLOW}!${NC} Ninguna página usa Visual Builder"
        ((WARNINGS++))
    fi
fi

# -----------------------------------------------------------------------------
# 9. Verificar Addon Multilingual (opcional)
# -----------------------------------------------------------------------------
echo ""
echo "9. Verificando Addon Multilingual..."
ML_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" \
    "$SITE_URL/wp-json/flavor-multilingual/v1/languages" \
    -H "X-VBP-Key: $API_KEY" 2>/dev/null || echo "000")

if [ "$ML_RESPONSE" = "200" ]; then
    LANGS=$(curl -s "$SITE_URL/wp-json/flavor-multilingual/v1/languages" \
        -H "X-VBP-Key: $API_KEY" 2>/dev/null | grep -o '"code"' | wc -l || echo "0")
    echo -e "   ${GREEN}✓${NC} Multilingual activo ($LANGS idiomas)"
else
    echo -e "   ${YELLOW}○${NC} Multilingual no activo (opcional)"
fi

# -----------------------------------------------------------------------------
# 10. Verificar Addons disponibles
# -----------------------------------------------------------------------------
echo ""
echo "10. Addons instalados..."
if [ -d "$WP_PATH/wp-content/plugins/flavor-chat-ia/addons" ]; then
    ADDONS=$(ls -1 "$WP_PATH/wp-content/plugins/flavor-chat-ia/addons" 2>/dev/null | wc -l)
    echo -e "   ${GREEN}✓${NC} $ADDONS addons disponibles"
    ls -1 "$WP_PATH/wp-content/plugins/flavor-chat-ia/addons" 2>/dev/null | while read addon; do
        echo -e "      - $addon"
    done
fi

# -----------------------------------------------------------------------------
# Resumen
# -----------------------------------------------------------------------------
echo ""
echo "=============================================="
echo "  RESUMEN"
echo "=============================================="

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "${GREEN}✓ Sitio correctamente configurado${NC}"
    echo ""
    echo "Puedes proceder con las modificaciones."
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo -e "${YELLOW}! Sitio con advertencias: $WARNINGS${NC}"
    echo ""
    echo "Revisa las advertencias antes de continuar."
    exit 0
else
    echo -e "${RED}✗ Errores encontrados: $ERRORS${NC}"
    echo -e "${YELLOW}! Advertencias: $WARNINGS${NC}"
    echo ""
    echo "DEBES corregir los errores antes de continuar."
    echo ""
    echo "Acciones requeridas:"

    if [ ! -d "$WP_PATH/wp-content/themes/flavor-starter" ]; then
        echo "  - Instalar tema flavor-starter"
    fi

    if [ ! -d "$WP_PATH/wp-content/plugins/flavor-chat-ia" ]; then
        echo "  - Instalar plugin flavor-chat-ia"
    fi

    exit 1
fi
