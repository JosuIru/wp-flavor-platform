#!/bin/bash
#
# vbp-inventory.sh - Inventario completo del Visual Builder Pro
#
# Uso: bash tools/vbp-inventory.sh "http://sitio.local"
#
# Este script DEBE ejecutarse ANTES de componer cualquier página
# para conocer qué elementos están realmente disponibles.
#

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuración
SITE=${1:-"http://localhost"}
KEY="flavor-vbp-2024"
TIMEOUT=10

# Limpiar URL
SITE="${SITE%/}"

echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║       INVENTARIO VISUAL BUILDER PRO - FLAVOR PLATFORM        ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "Sitio: ${YELLOW}$SITE${NC}"
echo -e "Fecha: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""

# Función para hacer requests
api_get() {
    local endpoint=$1
    curl -s --max-time $TIMEOUT "$SITE/wp-json/$endpoint" -H "X-VBP-Key: $KEY" 2>/dev/null
}

# Verificar conectividad
echo -e "${BLUE}[1/7] Verificando conectividad...${NC}"
health=$(api_get "flavor-vbp/v1/claude/status")
if [ -z "$health" ] || echo "$health" | grep -q "error"; then
    echo -e "${RED}ERROR: No se puede conectar a la API de VBP${NC}"
    echo "Verifica que:"
    echo "  - El sitio está accesible"
    echo "  - Flavor Chat IA está activo"
    echo "  - Visual Builder Pro está habilitado"
    exit 1
fi
echo -e "${GREEN}✓ API accesible${NC}"
echo ""

# ============================================
# BLOQUES DISPONIBLES
# ============================================
echo -e "${BLUE}[2/7] Bloques disponibles${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

blocks=$(api_get "flavor-vbp/v1/claude/blocks")

if command -v jq &> /dev/null; then
    # Con jq - formato bonito
    echo "$blocks" | jq -r '
        if .blocks then
            .blocks | group_by(.category) | .[] |
            "\n\(.[0].category // "general" | ascii_upcase):",
            (.[] | "  • \(.id) - \(.name // .id)")
        else
            "  (sin bloques definidos)"
        end
    ' 2>/dev/null || echo "  (error al parsear bloques)"

    block_count=$(echo "$blocks" | jq '.blocks | length' 2>/dev/null || echo "0")
    echo ""
    echo -e "Total: ${GREEN}$block_count bloques${NC}"
else
    # Sin jq - formato básico
    echo "$blocks" | grep -oP '"id"\s*:\s*"\K[^"]+' | while read id; do
        echo "  • $id"
    done
fi
echo ""

# ============================================
# TIPOS DE SECCIÓN
# ============================================
echo -e "${BLUE}[3/7] Tipos de sección${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

sections=$(api_get "flavor-vbp/v1/claude/section-types")

if command -v jq &> /dev/null; then
    echo "$sections" | jq -r '
        if type == "array" then
            .[] | "  • \(.id): \(.description // .name // "-")"
        elif .sections then
            .sections[] | "  • \(.id): \(.description // .name // "-")"
        else
            "  (sin secciones definidas)"
        end
    ' 2>/dev/null || echo "  (usando secciones por defecto)"

    section_count=$(echo "$sections" | jq 'if type == "array" then length else (.sections // []) | length end' 2>/dev/null || echo "?")
    echo ""
    echo -e "Total: ${GREEN}$section_count secciones${NC}"
else
    echo "$sections"
fi
echo ""

# ============================================
# PRESETS DE DISEÑO
# ============================================
echo -e "${BLUE}[4/7] Presets de diseño${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

presets=$(api_get "flavor-vbp/v1/claude/design-presets")

if command -v jq &> /dev/null; then
    echo "$presets" | jq -r '
        if type == "array" then
            .[] | "  • \(.id): \(.name // .id) - \(.description // "")"
        elif .presets then
            .presets[] | "  • \(.id): \(.name // .id)"
        else
            "  modern, community, eco, nature, corporate, fundraising"
        end
    ' 2>/dev/null || echo "  modern, community, eco, nature, corporate, fundraising"
else
    echo "  modern, community, eco, nature, corporate, fundraising"
fi
echo ""

# ============================================
# PLANTILLAS VBP
# ============================================
echo -e "${BLUE}[5/7] Plantillas VBP${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

templates=$(api_get "flavor-vbp/v1/claude/templates")

if command -v jq &> /dev/null; then
    echo "$templates" | jq -r '
        if type == "array" then
            .[] | "  • \(.id): \(.name // .id)"
        elif .templates then
            .templates[] | "  • \(.id): \(.name // .id)"
        else
            "  (sin plantillas personalizadas)"
        end
    ' 2>/dev/null || echo "  (sin plantillas)"
else
    echo "$templates"
fi
echo ""

# ============================================
# MÓDULOS ACTIVOS
# ============================================
echo -e "${BLUE}[6/7] Módulos activos en este sitio${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

modules=$(api_get "flavor-site-builder/v1/modules")

if command -v jq &> /dev/null; then
    active=$(echo "$modules" | jq -r '
        if type == "array" then
            [.[] | select(.active == true or .is_active == true)] |
            if length > 0 then
                .[] | "  ✓ \(.id) - \(.name // .id)"
            else
                "  (ningún módulo activo)"
            end
        else
            "  (formato no reconocido)"
        end
    ' 2>/dev/null)

    if [ -n "$active" ]; then
        echo "$active"
    else
        echo "  (ningún módulo activo)"
    fi

    active_count=$(echo "$modules" | jq '[.[] | select(.active == true or .is_active == true)] | length' 2>/dev/null || echo "0")
    total_count=$(echo "$modules" | jq 'length' 2>/dev/null || echo "?")
    echo ""
    echo -e "Activos: ${GREEN}$active_count${NC} de $total_count módulos"
else
    echo "$modules"
fi
echo ""

# ============================================
# WIDGETS DISPONIBLES
# ============================================
echo -e "${BLUE}[7/7] Widgets disponibles${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

widgets=$(api_get "flavor-vbp/v1/claude/widgets")

if command -v jq &> /dev/null; then
    echo "$widgets" | jq -r '
        if type == "array" then
            .[] | "  • \(.id): \(.name // .id)"
        elif .widgets then
            .widgets[] | "  • \(.id): \(.name // .id)"
        else
            "  (widgets estándar de WordPress)"
        end
    ' 2>/dev/null || echo "  (widgets estándar)"
else
    echo "  (widgets estándar)"
fi
echo ""

# ============================================
# RESUMEN Y RECOMENDACIONES
# ============================================
echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║                         RESUMEN                              ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo -e "${YELLOW}REGLAS PARA COMPONER PÁGINAS:${NC}"
echo ""
echo "  1. Solo usa bloques que aparezcan en la lista de arriba"
echo "  2. Solo referencia módulos que estén ACTIVOS (✓)"
echo "  3. Usa presets de diseño existentes, no inventes colores"
echo "  4. Si necesitas algo que no existe, pregunta antes de improvisar"
echo ""
echo -e "${YELLOW}EJEMPLO DE USO CORRECTO:${NC}"
echo ""
echo '  curl -X POST "$SITE/wp-json/flavor-vbp/v1/claude/pages/styled" \'
echo '    -H "X-VBP-Key: flavor-vbp-2024" \'
echo '    -H "Content-Type: application/json" \'
echo '    -d '"'"'{"title": "Mi Página", "preset": "community", ...}'"'"
echo ""
echo -e "${GREEN}Inventario completado.${NC}"
echo ""
