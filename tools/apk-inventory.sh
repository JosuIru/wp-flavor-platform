#!/bin/bash
#
# apk-inventory.sh - Inventario completo para configuración de APKs
#
# Uso: bash tools/apk-inventory.sh "http://sitio.local" "/ruta/mobile-apps"
#
# Este script DEBE ejecutarse ANTES de configurar cualquier APK
# para conocer qué módulos están realmente disponibles en los 3 niveles:
#   1. WordPress (módulos activos)
#   2. Flutter (templates implementados)
#   3. API (endpoints disponibles)
#

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Configuración
SITE=${1:-"http://localhost"}
MOBILE_PATH=${2:-"mobile-apps"}
KEY="flavor-vbp-2024"
TIMEOUT=10

# Limpiar URL
SITE="${SITE%/}"

echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║         INVENTARIO APK - FLAVOR PLATFORM 3 NIVELES          ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "Sitio: ${YELLOW}$SITE${NC}"
echo -e "Mobile: ${YELLOW}$MOBILE_PATH${NC}"
echo -e "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Función para hacer requests
api_get() {
    local endpoint=$1
    curl -s --max-time $TIMEOUT "$SITE/wp-json/$endpoint" -H "X-VBP-Key: $KEY" 2>/dev/null
}

# Arrays para almacenar datos
declare -A WP_MODULES
declare -A FLUTTER_MODULES
declare -A API_ENDPOINTS

# ============================================
# NIVEL 1: MÓDULOS WORDPRESS
# ============================================
echo -e "${BLUE}[1/5] Módulos WordPress activos${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

wp_modules=$(api_get "flavor-site-builder/v1/modules")

if [ -z "$wp_modules" ] || echo "$wp_modules" | grep -q "error"; then
    echo -e "${RED}ERROR: No se puede conectar a la API de módulos${NC}"
    echo "Verifica que el sitio esté accesible"
else
    if command -v jq &> /dev/null; then
        echo "$wp_modules" | jq -r '
            if type == "array" then
                .[] |
                if (.active == true or .is_active == true) then
                    "  \u001b[32m✓\u001b[0m \(.id) - \(.name // .id)"
                else
                    "  \u001b[90m○ \(.id)\u001b[0m"
                end
            else
                "  (formato no reconocido)"
            end
        ' 2>/dev/null || echo "  (error al parsear)"

        active_wp=$(echo "$wp_modules" | jq '[.[] | select(.active == true or .is_active == true)] | length' 2>/dev/null || echo "0")
        total_wp=$(echo "$wp_modules" | jq 'length' 2>/dev/null || echo "?")
        echo ""
        echo -e "WordPress: ${GREEN}$active_wp activos${NC} de $total_wp módulos"

        # Guardar IDs activos
        wp_active_ids=$(echo "$wp_modules" | jq -r '.[] | select(.active == true or .is_active == true) | .id' 2>/dev/null)
    else
        echo "  (instala jq para ver detalles)"
    fi
fi
echo ""

# ============================================
# NIVEL 2: TEMPLATES FLUTTER
# ============================================
echo -e "${BLUE}[2/5] Templates Flutter disponibles${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

FLUTTER_MODULES_PATH="$MOBILE_PATH/lib/features/modules"

if [ -d "$FLUTTER_MODULES_PATH" ]; then
    flutter_modules=$(find "$FLUTTER_MODULES_PATH" -maxdepth 1 -type d | tail -n +2 | xargs -I {} basename {} | sort)
    flutter_count=0

    for module in $flutter_modules; do
        # Normalizar ID (guiones bajos a guiones)
        normalized_id=$(echo "$module" | tr '_' '-')

        # Verificar si tiene screen
        if ls "$FLUTTER_MODULES_PATH/$module/"*_screen.dart 1> /dev/null 2>&1; then
            echo -e "  ${GREEN}✓${NC} $module (con pantalla)"
            flutter_count=$((flutter_count + 1))
        else
            echo -e "  ${YELLOW}⚠${NC} $module (sin pantalla principal)"
        fi
    done

    echo ""
    echo -e "Flutter: ${GREEN}$flutter_count módulos${NC} con pantalla implementada"
else
    echo -e "${RED}ERROR: No se encuentra $FLUTTER_MODULES_PATH${NC}"
    echo "Verifica la ruta al proyecto Flutter"
fi
echo ""

# ============================================
# NIVEL 3: API ENDPOINTS
# ============================================
echo -e "${BLUE}[3/5] API endpoints para móvil${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Verificar API app-discovery
app_discovery=$(api_get "app-discovery/v1/info")

if [ -n "$app_discovery" ] && ! echo "$app_discovery" | grep -q "error"; then
    echo -e "${GREEN}✓ API app-discovery disponible${NC}"

    if command -v jq &> /dev/null; then
        echo "$app_discovery" | jq -r '
            "  Site: \(.site_name // .app_name // "?")",
            "  API Version: \(.api_version // "?")",
            "",
            "  Sistemas activos:",
            ((.active_systems // [])[] | "    • \(.id): \(.api_namespace)")
        ' 2>/dev/null || echo "  (error al parsear)"
    fi
else
    echo -e "${YELLOW}⚠ API app-discovery no disponible, usando fallback${NC}"
fi

# Verificar API chat-ia-mobile
mobile_api=$(api_get "chat-ia-mobile/v1/site-info")
if [ -n "$mobile_api" ] && ! echo "$mobile_api" | grep -q "error"; then
    echo -e "${GREEN}✓ API chat-ia-mobile disponible${NC}"
fi

# Verificar endpoints de módulos
echo ""
echo "  Endpoints por módulo:"
module_endpoints=("eventos" "socios" "foros" "marketplace" "reservas" "cursos" "talleres")
for mod in "${module_endpoints[@]}"; do
    endpoint_check=$(curl -s --max-time 3 "$SITE/wp-json/flavor-$mod/v1" 2>/dev/null)
    if [ -n "$endpoint_check" ] && ! echo "$endpoint_check" | grep -q "rest_no_route"; then
        echo -e "    ${GREEN}✓${NC} flavor-$mod/v1"
    else
        echo -e "    ${YELLOW}○${NC} flavor-$mod/v1"
    fi
done
echo ""

# ============================================
# CONFIGURACIÓN ACTUAL DE LA APP
# ============================================
echo -e "${BLUE}[4/5] Configuración actual de la app${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

APP_CONFIG="$MOBILE_PATH/lib/core/config/app_config.dart"

if [ -f "$APP_CONFIG" ]; then
    echo "  Archivo: $APP_CONFIG"
    echo ""

    # Extraer datos de configuración
    app_name=$(grep "static const String appName" "$APP_CONFIG" | sed "s/.*= '\([^']*\)'.*/\1/")
    app_id=$(grep "static const String appId" "$APP_CONFIG" | sed "s/.*= '\([^']*\)'.*/\1/")
    server_url=$(grep "static const String serverUrl" "$APP_CONFIG" | sed "s/.*= '\([^']*\)'.*/\1/")

    echo -e "  App Name: ${GREEN}$app_name${NC}"
    echo -e "  App ID: $app_id"
    echo -e "  Server URL: $server_url"
    echo ""

    # Extraer módulos habilitados
    echo "  Módulos habilitados en app_config.dart:"
    grep -A 20 "enabledModules = \[" "$APP_CONFIG" | grep "'" | sed "s/.*'\([^']*\)'.*/    • \1/" | head -10
else
    echo -e "${YELLOW}⚠ No se encuentra app_config.dart${NC}"
fi
echo ""

# ============================================
# MATRIZ DE COMPATIBILIDAD
# ============================================
echo -e "${BLUE}[5/5] Matriz de compatibilidad 3 niveles${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo -e "${CYAN}Leyenda:${NC}"
echo -e "  ${GREEN}✓${NC} = Disponible    ${YELLOW}⚠${NC} = Parcial    ${RED}✗${NC} = No disponible"
echo ""
printf "%-25s %-12s %-12s %-12s\n" "MÓDULO" "WORDPRESS" "FLUTTER" "API"
echo "─────────────────────────────────────────────────────────────"

# Lista de módulos a verificar
modules_to_check=(
    "eventos"
    "socios"
    "foros"
    "marketplace"
    "reservas"
    "cursos"
    "talleres"
    "grupos-consumo"
    "banco-tiempo"
    "carpooling"
    "bicicletas-compartidas"
    "espacios-comunes"
    "transparencia"
    "participacion"
    "incidencias"
    "multimedia"
    "radio"
    "podcast"
)

full_support=0
partial_support=0

for module in "${modules_to_check[@]}"; do
    # WordPress check
    flutter_id=$(echo "$module" | tr '-' '_')

    if echo "$wp_active_ids" | grep -q "^$module$"; then
        wp_status="${GREEN}✓${NC}"
        wp_ok=1
    else
        wp_status="${RED}✗${NC}"
        wp_ok=0
    fi

    # Flutter check
    if [ -d "$FLUTTER_MODULES_PATH/$flutter_id" ]; then
        if ls "$FLUTTER_MODULES_PATH/$flutter_id/"*_screen.dart 1> /dev/null 2>&1; then
            flutter_status="${GREEN}✓${NC}"
            flutter_ok=1
        else
            flutter_status="${YELLOW}⚠${NC}"
            flutter_ok=0
        fi
    else
        flutter_status="${RED}✗${NC}"
        flutter_ok=0
    fi

    # API check (simplificado)
    api_check=$(curl -s --max-time 2 "$SITE/wp-json/flavor-$module/v1" 2>/dev/null)
    if [ -n "$api_check" ] && ! echo "$api_check" | grep -q "rest_no_route"; then
        api_status="${GREEN}✓${NC}"
        api_ok=1
    else
        api_status="${RED}✗${NC}"
        api_ok=0
    fi

    # Contar soporte
    if [ $wp_ok -eq 1 ] && [ $flutter_ok -eq 1 ] && [ $api_ok -eq 1 ]; then
        full_support=$((full_support + 1))
    elif [ $wp_ok -eq 1 ] || [ $flutter_ok -eq 1 ]; then
        partial_support=$((partial_support + 1))
    fi

    printf "%-25s %-20b %-20b %-20b\n" "$module" "$wp_status" "$flutter_status" "$api_status"
done

echo ""
echo "─────────────────────────────────────────────────────────────"
echo -e "Soporte completo (3/3): ${GREEN}$full_support módulos${NC}"
echo -e "Soporte parcial: ${YELLOW}$partial_support módulos${NC}"
echo ""

# ============================================
# CLASIFICACIÓN POR ACCIÓN
# ============================================
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                 CLASIFICACIÓN POR ACCIÓN                     ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${GREEN}✓ HABILITAR AUTOMÁTICAMENTE (3/3):${NC}"
echo "  Módulos con soporte completo en WordPress + Flutter + API"
echo "  → Añadir directamente a enabledModules"
echo ""

echo -e "${YELLOW}⚠ PEDIR PERMISO AL USUARIO (2/3):${NC}"
echo "  Módulos con soporte parcial que podrían ser útiles"
echo "  → Preguntar: '¿Habilitar X usando WebView fallback?'"
echo "  → Explicar qué nivel falta y las consecuencias"
echo ""

echo -e "${RED}✗ ADVERTIR Y NO RECOMENDAR (1/3 o menos):${NC}"
echo "  Módulos sin suficiente soporte"
echo "  → Informar al usuario pero no habilitar"
echo ""

# ============================================
# RESUMEN Y RECOMENDACIONES
# ============================================
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                    RECOMENDACIONES                           ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}FLUJO DE DECISIÓN:${NC}"
echo ""
echo "  1. Módulos 3/3 → Habilitar sin preguntar"
echo "  2. Módulos 2/3 → Preguntar al usuario explicando:"
echo "     - Qué nivel falta (WordPress, Flutter o API)"
echo "     - Qué fallback se usará (WebView, datos limitados, etc.)"
echo "     - Si el usuario acepta, habilitar con advertencia"
echo "  3. Módulos 1/3 → Informar que no se recomienda habilitar"
echo ""
echo -e "${YELLOW}PARA ACTUALIZAR app_config.dart:${NC}"
echo ""
echo "  1. Copiar los IDs de módulos aprobados (3/3 + 2/3 con permiso)"
echo "  2. Editar: $MOBILE_PATH/lib/core/config/app_config.dart"
echo "  3. Actualizar el array enabledModules"
echo "  4. Reconstruir la APK: flutter build apk --release"
echo ""
echo -e "${GREEN}Inventario completado.${NC}"
echo ""
