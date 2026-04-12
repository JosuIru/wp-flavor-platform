#!/bin/bash
#
# VBP Performance Tests Runner
# Ejecuta tests de rendimiento completos para Visual Builder Pro
#
# Uso: bash tools/run-performance-tests.sh [SITE_URL] [WP_PATH]
#
# @package Flavor_Platform
# @since 2.3.0

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

# Parametros
SITE_URL="${1:-http://sitio-prueba.local}"
WP_PATH="${2:-$(dirname "$PLUGIN_DIR")/../../..}"
OUTPUT_DIR="$PLUGIN_DIR/lighthouse-results"
REPORT_FILE="$PLUGIN_DIR/PERFORMANCE-BASELINE.md"

# Timestamp para el reporte
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
DATE_SHORT=$(date +"%Y%m%d-%H%M%S")

echo -e "${CYAN}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║         VBP Performance Tests                                ║"
echo "║         Visual Builder Pro - Flavor Platform                 ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo -e "${BLUE}Configuracion:${NC}"
echo "  Site URL:    $SITE_URL"
echo "  WP Path:     $WP_PATH"
echo "  Output:      $OUTPUT_DIR"
echo ""

# Crear directorio de resultados
mkdir -p "$OUTPUT_DIR"

# ============================================================================
# PASO 1: Verificar prerrequisitos
# ============================================================================

echo -e "${YELLOW}[1/5] Verificando prerrequisitos...${NC}"

# Verificar Node.js
if ! command -v node &> /dev/null; then
    echo -e "${RED}Error: Node.js no esta instalado${NC}"
    exit 1
fi
NODE_VERSION=$(node --version)
echo "  Node.js: $NODE_VERSION"

# Verificar npm
if ! command -v npm &> /dev/null; then
    echo -e "${RED}Error: npm no esta instalado${NC}"
    exit 1
fi
NPM_VERSION=$(npm --version)
echo "  npm: $NPM_VERSION"

# Verificar Lighthouse CI
if ! npx lhci --version &> /dev/null 2>&1; then
    echo -e "${YELLOW}  Instalando Lighthouse CI...${NC}"
    npm install -g @lhci/cli
fi
LHCI_VERSION=$(npx lhci --version 2>/dev/null || echo "unknown")
echo "  Lighthouse CI: $LHCI_VERSION"

# Verificar WP-CLI
if ! command -v wp &> /dev/null; then
    echo -e "${YELLOW}  Advertencia: WP-CLI no encontrado (algunas funciones limitadas)${NC}"
    WP_CLI_AVAILABLE=false
else
    WP_CLI_AVAILABLE=true
    echo "  WP-CLI: $(wp cli version 2>/dev/null | head -1)"
fi

# Verificar conectividad al sitio
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$SITE_URL" 2>/dev/null || echo "000")
if [ "$HTTP_STATUS" != "200" ]; then
    echo -e "${RED}Error: No se puede conectar a $SITE_URL (HTTP $HTTP_STATUS)${NC}"
    exit 1
fi
echo -e "  Sitio accesible: ${GREEN}OK${NC}"

echo ""

# ============================================================================
# PASO 2: Crear paginas de test
# ============================================================================

echo -e "${YELLOW}[2/5] Preparando paginas de test...${NC}"

# Funcion para crear pagina de test con N elementos
create_test_page() {
    local ELEMENT_COUNT=$1
    local PAGE_SLUG="vbp-test-$ELEMENT_COUNT"
    local PAGE_TITLE="VBP Test - $ELEMENT_COUNT Elementos"

    if [ "$WP_CLI_AVAILABLE" = true ]; then
        cd "$WP_PATH"

        # Verificar si la pagina existe
        PAGE_ID=$(wp post list --post_type=page --name="$PAGE_SLUG" --field=ID 2>/dev/null || echo "")

        if [ -z "$PAGE_ID" ]; then
            echo "  Creando pagina: $PAGE_TITLE"

            # Generar contenido de test
            CONTENT="<!-- wp:html --><div class=\"vbp-test-container\" data-vbp-page=\"true\">"
            for i in $(seq 1 $ELEMENT_COUNT); do
                CONTENT="$CONTENT<div data-vbp-element=\"block-$i\" class=\"vbp-test-block\"><h3>Bloque $i</h3><p>Contenido de prueba</p></div>"
            done
            CONTENT="$CONTENT</div><!-- /wp:html -->"

            PAGE_ID=$(wp post create \
                --post_type=page \
                --post_title="$PAGE_TITLE" \
                --post_name="$PAGE_SLUG" \
                --post_status=publish \
                --post_content="$CONTENT" \
                --porcelain 2>/dev/null || echo "")

            if [ -n "$PAGE_ID" ]; then
                echo -e "    ${GREEN}Creada: $SITE_URL/$PAGE_SLUG/ (ID: $PAGE_ID)${NC}"
            else
                echo -e "    ${RED}Error al crear pagina${NC}"
            fi
        else
            echo -e "    Pagina existe: $SITE_URL/$PAGE_SLUG/ (ID: $PAGE_ID)"
        fi
    else
        echo -e "    ${YELLOW}Omitiendo creacion (sin WP-CLI)${NC}"
    fi
}

# Crear paginas de test
for COUNT in 10 50 100; do
    create_test_page $COUNT
done

echo ""

# ============================================================================
# PASO 3: Ejecutar tests de Lighthouse
# ============================================================================

echo -e "${YELLOW}[3/5] Ejecutando tests de Lighthouse...${NC}"

# Crear archivo de configuracion temporal con URLs correctas
TEMP_LHCI_CONFIG=$(mktemp)
cat > "$TEMP_LHCI_CONFIG" << EOF
module.exports = {
    ci: {
        collect: {
            url: [
                '$SITE_URL/vbp-test-10/',
                '$SITE_URL/vbp-test-50/',
                '$SITE_URL/vbp-test-100/'
            ],
            numberOfRuns: 3,
            settings: {
                preset: 'desktop',
                onlyCategories: ['performance'],
                throttlingMethod: 'provided',
                throttling: {
                    cpuSlowdownMultiplier: 1,
                    rttMs: 0,
                    throughputKbps: 0
                }
            }
        },
        assert: {
            assertions: {
                'first-contentful-paint': ['warn', { maxNumericValue: 3000 }],
                'largest-contentful-paint': ['warn', { maxNumericValue: 4000 }],
                'cumulative-layout-shift': ['warn', { maxNumericValue: 0.1 }],
                'total-blocking-time': ['warn', { maxNumericValue: 500 }]
            }
        },
        upload: {
            target: 'filesystem',
            outputDir: '$OUTPUT_DIR'
        }
    }
};
EOF

# Ejecutar Lighthouse CI
cd "$PLUGIN_DIR"
npx lhci autorun --config="$TEMP_LHCI_CONFIG" 2>&1 | tee "$OUTPUT_DIR/lighthouse-output-$DATE_SHORT.log" || true

# Limpiar config temporal
rm -f "$TEMP_LHCI_CONFIG"

echo ""

# ============================================================================
# PASO 4: Recoger metricas adicionales
# ============================================================================

echo -e "${YELLOW}[4/5] Recopilando metricas adicionales...${NC}"

# Archivo de metricas
METRICS_FILE="$OUTPUT_DIR/metrics-$DATE_SHORT.json"

# Iniciar JSON
echo "{" > "$METRICS_FILE"
echo "  \"timestamp\": \"$TIMESTAMP\"," >> "$METRICS_FILE"
echo "  \"siteUrl\": \"$SITE_URL\"," >> "$METRICS_FILE"

# Metricas de WordPress (si WP-CLI disponible)
if [ "$WP_CLI_AVAILABLE" = true ]; then
    cd "$WP_PATH"

    # Modulos activos
    MODULES_COUNT=$(wp option get flavor_active_modules --format=json 2>/dev/null | jq 'length' 2>/dev/null || echo "0")
    echo "  \"activeModules\": $MODULES_COUNT," >> "$METRICS_FILE"
    echo "  Modulos activos: $MODULES_COUNT"

    # Paginas VBP
    VBP_PAGES=$(wp db query "SELECT COUNT(*) FROM ${WPDB_PREFIX:-wp_}postmeta WHERE meta_key='_flavor_vbp_data'" --skip-column-names 2>/dev/null || echo "0")
    echo "  \"vbpPages\": $VBP_PAGES," >> "$METRICS_FILE"
    echo "  Paginas VBP: $VBP_PAGES"

    # Tamano de base de datos
    DB_SIZE=$(wp db size --format=json 2>/dev/null | jq -r '.[] | select(.Name=="Total Size") | .Size' 2>/dev/null || echo "N/A")
    echo "  \"databaseSize\": \"$DB_SIZE\"," >> "$METRICS_FILE"
    echo "  Tamano BD: $DB_SIZE"
fi

# Cerrar JSON
echo "  \"completed\": true" >> "$METRICS_FILE"
echo "}" >> "$METRICS_FILE"

echo ""

# ============================================================================
# PASO 5: Generar reporte
# ============================================================================

echo -e "${YELLOW}[5/5] Generando reporte...${NC}"

# Leer resultados de Lighthouse
LIGHTHOUSE_RESULTS=""
for JSON_FILE in "$OUTPUT_DIR"/*.json; do
    if [ -f "$JSON_FILE" ] && [ "$(basename "$JSON_FILE")" != "metrics-$DATE_SHORT.json" ]; then
        # Extraer metricas del archivo JSON de Lighthouse
        if jq -e '.lhr' "$JSON_FILE" > /dev/null 2>&1; then
            URL=$(jq -r '.lhr.requestedUrl // "unknown"' "$JSON_FILE" 2>/dev/null)
            PERF_SCORE=$(jq -r '.lhr.categories.performance.score // 0' "$JSON_FILE" 2>/dev/null)
            FCP=$(jq -r '.lhr.audits["first-contentful-paint"].numericValue // 0' "$JSON_FILE" 2>/dev/null)
            LCP=$(jq -r '.lhr.audits["largest-contentful-paint"].numericValue // 0' "$JSON_FILE" 2>/dev/null)
            CLS=$(jq -r '.lhr.audits["cumulative-layout-shift"].numericValue // 0' "$JSON_FILE" 2>/dev/null)
            TBT=$(jq -r '.lhr.audits["total-blocking-time"].numericValue // 0' "$JSON_FILE" 2>/dev/null)

            LIGHTHOUSE_RESULTS="$LIGHTHOUSE_RESULTS
| $(basename "$URL") | $(echo "$PERF_SCORE * 100" | bc 2>/dev/null || echo "N/A")% | ${FCP}ms | ${LCP}ms | $CLS | ${TBT}ms |"
        fi
    fi
done

# Generar reporte markdown
cat > "$REPORT_FILE" << EOF
# VBP Performance Baseline

> Generado: $TIMESTAMP
> Sitio: $SITE_URL
> Version: 1.0.0

## Resumen Ejecutivo

Este reporte contiene las metricas de rendimiento baseline del Visual Builder Pro.

## Core Web Vitals

| Pagina | Score | FCP | LCP | CLS | TBT |
|--------|-------|-----|-----|-----|-----|
$LIGHTHOUSE_RESULTS

### Umbrales de Referencia

| Metrica | Bueno | Aceptable | Pobre |
|---------|-------|-----------|-------|
| FCP | <= 1.8s | <= 3.0s | > 3.0s |
| LCP | <= 2.5s | <= 4.0s | > 4.0s |
| CLS | <= 0.1 | <= 0.25 | > 0.25 |
| TBT | <= 200ms | <= 600ms | > 600ms |
| FID | <= 100ms | <= 300ms | > 300ms |

## Metricas del Editor

### Tiempo de Carga

| Metrica | Bueno | Aceptable | Pobre |
|---------|-------|-----------|-------|
| Tiempo de carga | <= 2s | <= 4s | > 6s |
| TTI | <= 3s | <= 5s | > 8s |
| Memoria inicial | <= 50MB | <= 100MB | > 200MB |

### Rendimiento en Operacion

| Metrica | Bueno | Aceptable | Pobre |
|---------|-------|-----------|-------|
| FPS (drag) | >= 55 | >= 45 | < 30 |
| FPS (idle) | >= 58 | >= 50 | < 40 |
| Render time | <= 16ms | <= 33ms | > 100ms |
| Save time | <= 500ms | <= 1.5s | > 3s |

## Escalabilidad

| Elementos | Carga | Render | FPS | Memoria |
|-----------|-------|--------|-----|---------|
| 10 | <= 50ms | <= 16ms | ~60 | ~50MB |
| 50 | <= 150ms | <= 50ms | ~55 | ~80MB |
| 100 | <= 300ms | <= 100ms | ~50 | ~100MB |
| 500 | <= 1000ms | <= 300ms | ~40 | ~200MB |

## Recomendaciones

### Alta Prioridad

1. **Mantener LCP < 2.5s**: Optimizar carga de imagenes hero, usar preload
2. **CLS < 0.1**: Definir dimensiones de imagenes, reservar espacio para ads
3. **FPS > 50 durante drag**: Optimizar event handlers, usar requestAnimationFrame

### Media Prioridad

1. **Reducir JS bundle**: Code splitting, lazy loading de modulos
2. **Optimizar CSS**: Purgar estilos no usados, critical CSS inline
3. **Virtualizacion**: Implementar para listas > 100 elementos

### Mejoras Continuas

1. Monitorear metricas en produccion
2. Configurar alertas para regresiones
3. Ejecutar tests de performance en CI/CD

## Archivos Generados

- \`lighthouse-results/\` - Reportes completos de Lighthouse
- \`metrics-$DATE_SHORT.json\` - Metricas adicionales

## Como Ejecutar Este Test

\`\`\`bash
# Test completo
bash tools/run-performance-tests.sh "$SITE_URL"

# Solo Lighthouse
npx lhci autorun

# Test de escalabilidad (en browser)
# Abrir consola y ejecutar:
# VBPScalabilityTest.quickScalabilityTest()
\`\`\`

---

*Generado automaticamente por VBP Performance Tests v1.0.0*
EOF

echo -e "  Reporte generado: ${GREEN}$REPORT_FILE${NC}"
echo ""

# ============================================================================
# Resumen final
# ============================================================================

echo -e "${GREEN}"
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║                    Tests Completados                         ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo -e "${NC}"

echo "Resultados guardados en:"
echo "  - Reporte:    $REPORT_FILE"
echo "  - Lighthouse: $OUTPUT_DIR/"
echo "  - Metricas:   $METRICS_FILE"
echo ""

# Mostrar resumen de resultados si hay archivos
if ls "$OUTPUT_DIR"/*.json 1> /dev/null 2>&1; then
    echo "Resumen de scores:"
    for JSON_FILE in "$OUTPUT_DIR"/*.json; do
        if jq -e '.lhr.categories.performance.score' "$JSON_FILE" > /dev/null 2>&1; then
            URL=$(jq -r '.lhr.requestedUrl' "$JSON_FILE" 2>/dev/null)
            SCORE=$(jq -r '.lhr.categories.performance.score' "$JSON_FILE" 2>/dev/null)
            SCORE_PCT=$(echo "$SCORE * 100" | bc 2>/dev/null || echo "N/A")

            if (( $(echo "$SCORE >= 0.9" | bc -l) )); then
                COLOR=$GREEN
            elif (( $(echo "$SCORE >= 0.5" | bc -l) )); then
                COLOR=$YELLOW
            else
                COLOR=$RED
            fi

            echo -e "  - $(basename "$URL"): ${COLOR}${SCORE_PCT}%${NC}"
        fi
    done
fi

echo ""
echo "Para ver el reporte completo:"
echo "  cat $REPORT_FILE"
echo ""
