#!/bin/bash
#
# full-inventory.sh - Inventario completo de Flavor Platform
#
# Uso: bash tools/full-inventory.sh "http://sitio.local" "/ruta/wordpress" "mobile-apps"
#
# Ejecuta los 3 inventarios en secuencia:
#   1. Validación del sitio WordPress
#   2. Inventario VBP (bloques, secciones, presets)
#   3. Inventario APK (3 niveles)
#

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
BOLD='\033[1m'
NC='\033[0m'

# Configuración
SITE=${1:-"http://localhost"}
WP_PATH=${2:-"."}
MOBILE_PATH=${3:-"mobile-apps"}
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Limpiar URL
SITE="${SITE%/}"

# Timestamp
TIMESTAMP=$(date '+%Y-%m-%d_%H-%M-%S')
REPORT_FILE="inventory-report-$TIMESTAMP.txt"

echo ""
echo -e "${MAGENTA}╔══════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${MAGENTA}║                                                                      ║${NC}"
echo -e "${MAGENTA}║            ${BOLD}FLAVOR PLATFORM - INVENTARIO COMPLETO${NC}${MAGENTA}                     ║${NC}"
echo -e "${MAGENTA}║                                                                      ║${NC}"
echo -e "${MAGENTA}╚══════════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "Sitio:    ${YELLOW}$SITE${NC}"
echo -e "WordPress: ${YELLOW}$WP_PATH${NC}"
echo -e "Mobile:   ${YELLOW}$MOBILE_PATH${NC}"
echo -e "Fecha:    $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Contadores
ERRORS=0
WARNINGS=0

# ============================================
# FASE 1: VALIDACIÓN DEL SITIO
# ============================================
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}  FASE 1: VALIDACIÓN DEL SITIO WORDPRESS${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Verificar conectividad
echo -n "  Conectividad API... "
health=$(curl -s --max-time 5 "$SITE/wp-json/flavor-vbp/v1/claude/status" -H "X-VBP-Key: flavor-vbp-2024" 2>/dev/null)
if [ -n "$health" ] && ! echo "$health" | grep -q "error"; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}FALLO${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Verificar tema
echo -n "  Tema flavor-starter... "
if [ -d "$WP_PATH/wp-content/themes/flavor-starter" ]; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${YELLOW}NO ENCONTRADO${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

# Verificar plugin activo
echo -n "  Plugin flavor-chat-ia... "
if [ -f "$WP_PATH/wp-content/plugins/flavor-chat-ia/flavor-chat-ia.php" ]; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${RED}NO ENCONTRADO${NC}"
    ERRORS=$((ERRORS + 1))
fi

echo ""

# ============================================
# FASE 2: INVENTARIO VBP
# ============================================
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}  FASE 2: INVENTARIO VISUAL BUILDER PRO${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

KEY="flavor-vbp-2024"

# Bloques
echo -n "  Bloques disponibles... "
blocks=$(curl -s --max-time 5 "$SITE/wp-json/flavor-vbp/v1/claude/blocks" -H "X-VBP-Key: $KEY" 2>/dev/null)
if command -v jq &> /dev/null && [ -n "$blocks" ]; then
    block_count=$(echo "$blocks" | jq '.blocks | length' 2>/dev/null || echo "0")
    echo -e "${GREEN}$block_count${NC}"
else
    echo -e "${YELLOW}?${NC}"
fi

# Secciones
echo -n "  Tipos de sección... "
sections=$(curl -s --max-time 5 "$SITE/wp-json/flavor-vbp/v1/claude/section-types" -H "X-VBP-Key: $KEY" 2>/dev/null)
if command -v jq &> /dev/null && [ -n "$sections" ]; then
    section_count=$(echo "$sections" | jq 'if type == "array" then length else (.sections // []) | length end' 2>/dev/null || echo "0")
    echo -e "${GREEN}$section_count${NC}"
else
    echo -e "${YELLOW}?${NC}"
fi

# Presets
echo -n "  Presets de diseño... "
presets=$(curl -s --max-time 5 "$SITE/wp-json/flavor-vbp/v1/claude/design-presets" -H "X-VBP-Key: $KEY" 2>/dev/null)
if command -v jq &> /dev/null && [ -n "$presets" ]; then
    preset_count=$(echo "$presets" | jq 'if type == "array" then length else (.presets // []) | length end' 2>/dev/null || echo "6")
    echo -e "${GREEN}$preset_count${NC}"
else
    echo -e "${GREEN}6 (default)${NC}"
fi

# Módulos WordPress
echo -n "  Módulos WordPress... "
modules=$(curl -s --max-time 5 "$SITE/wp-json/flavor-site-builder/v1/modules" -H "X-VBP-Key: $KEY" 2>/dev/null)
if command -v jq &> /dev/null && [ -n "$modules" ]; then
    active_count=$(echo "$modules" | jq '[.[] | select(.active == true or .is_active == true)] | length' 2>/dev/null || echo "0")
    total_count=$(echo "$modules" | jq 'length' 2>/dev/null || echo "0")
    echo -e "${GREEN}$active_count activos${NC} de $total_count"
else
    echo -e "${YELLOW}?${NC}"
fi

echo ""

# ============================================
# FASE 3: INVENTARIO APK (3 NIVELES)
# ============================================
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}  FASE 3: INVENTARIO APK (3 NIVELES)${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

FLUTTER_MODULES_PATH="$MOBILE_PATH/lib/features/modules"

# Templates Flutter
echo -n "  Templates Flutter... "
if [ -d "$FLUTTER_MODULES_PATH" ]; then
    flutter_count=$(find "$FLUTTER_MODULES_PATH" -maxdepth 2 -name "*_screen.dart" 2>/dev/null | wc -l)
    echo -e "${GREEN}$flutter_count módulos${NC}"
else
    echo -e "${RED}No encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi

# Configuración actual
echo -n "  app_config.dart... "
APP_CONFIG="$MOBILE_PATH/lib/core/config/app_config.dart"
if [ -f "$APP_CONFIG" ]; then
    enabled_count=$(grep -A 20 "enabledModules = \[" "$APP_CONFIG" | grep "'" | wc -l)
    echo -e "${GREEN}$enabled_count módulos habilitados${NC}"
else
    echo -e "${RED}No encontrado${NC}"
    ERRORS=$((ERRORS + 1))
fi

# API móvil
echo -n "  API app-discovery... "
discovery=$(curl -s --max-time 3 "$SITE/wp-json/app-discovery/v1/info" 2>/dev/null)
if [ -n "$discovery" ] && ! echo "$discovery" | grep -q "error"; then
    echo -e "${GREEN}OK${NC}"
else
    echo -e "${YELLOW}No disponible${NC}"
    WARNINGS=$((WARNINGS + 1))
fi

echo ""

# ============================================
# MATRIZ DE COMPATIBILIDAD RÁPIDA
# ============================================
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${CYAN}  MATRIZ DE COMPATIBILIDAD (Top 10 módulos)${NC}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

printf "  %-20s %-10s %-10s %-10s %-8s\n" "MÓDULO" "WORDPRESS" "FLUTTER" "API" "SOPORTE"
echo "  ────────────────────────────────────────────────────────────────"

# Obtener IDs activos de WordPress
wp_active_ids=""
if command -v jq &> /dev/null && [ -n "$modules" ]; then
    wp_active_ids=$(echo "$modules" | jq -r '.[] | select(.active == true or .is_active == true) | .id' 2>/dev/null)
fi

# Top módulos a verificar
top_modules=("eventos" "socios" "foros" "marketplace" "reservas" "cursos" "talleres" "grupos-consumo" "carpooling" "transparencia")

for module in "${top_modules[@]}"; do
    flutter_id=$(echo "$module" | tr '-' '_')
    support=0

    # WordPress
    if echo "$wp_active_ids" | grep -q "^$module$"; then
        wp_status="${GREEN}✓${NC}"
        support=$((support + 1))
    else
        wp_status="${RED}✗${NC}"
    fi

    # Flutter
    if [ -d "$FLUTTER_MODULES_PATH/$flutter_id" ] && ls "$FLUTTER_MODULES_PATH/$flutter_id/"*_screen.dart 1> /dev/null 2>&1; then
        flutter_status="${GREEN}✓${NC}"
        support=$((support + 1))
    else
        flutter_status="${RED}✗${NC}"
    fi

    # API
    api_check=$(curl -s --max-time 2 "$SITE/wp-json/flavor-$module/v1" 2>/dev/null)
    if [ -n "$api_check" ] && ! echo "$api_check" | grep -q "rest_no_route"; then
        api_status="${GREEN}✓${NC}"
        support=$((support + 1))
    else
        api_status="${RED}✗${NC}"
    fi

    # Nivel de soporte
    case $support in
        3) support_text="${GREEN}3/3${NC}" ;;
        2) support_text="${YELLOW}2/3${NC}" ;;
        1) support_text="${RED}1/3${NC}" ;;
        0) support_text="${RED}0/3${NC}" ;;
    esac

    printf "  %-20s %-18b %-18b %-18b %-16b\n" "$module" "$wp_status" "$flutter_status" "$api_status" "$support_text"
done

echo ""

# ============================================
# RESUMEN FINAL
# ============================================
echo -e "${MAGENTA}╔══════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${MAGENTA}║                         RESUMEN FINAL                                ║${NC}"
echo -e "${MAGENTA}╚══════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo -e "  Estado: ${GREEN}${BOLD}TODO OK${NC}"
elif [ $ERRORS -eq 0 ]; then
    echo -e "  Estado: ${YELLOW}${BOLD}OK CON ADVERTENCIAS${NC}"
else
    echo -e "  Estado: ${RED}${BOLD}HAY ERRORES${NC}"
fi

echo ""
echo -e "  Errores:      ${RED}$ERRORS${NC}"
echo -e "  Advertencias: ${YELLOW}$WARNINGS${NC}"
echo ""

echo -e "${CYAN}Próximos pasos:${NC}"
echo ""
if [ $ERRORS -gt 0 ]; then
    echo "  1. Corregir los errores indicados"
    echo "  2. Volver a ejecutar este inventario"
else
    echo "  1. Para páginas web: usar bloques/secciones del inventario VBP"
    echo "  2. Para APKs: habilitar solo módulos con soporte 3/3"
    echo "  3. Para módulos 2/3: pedir permiso al usuario"
fi
echo ""

echo -e "${GREEN}Inventario completo finalizado.${NC}"
echo ""
