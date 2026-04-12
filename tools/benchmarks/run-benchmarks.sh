#!/bin/bash
#
# VBP Benchmark Suite - CLI Runner
#
# Execute benchmarks from command line with various options.
#
# Usage:
#   ./run-benchmarks.sh                    # Interactive menu
#   ./run-benchmarks.sh --all              # Run all benchmarks
#   ./run-benchmarks.sh --benchmark=landing-simple
#   ./run-benchmarks.sh --automated        # Run with Playwright
#   ./run-benchmarks.sh --report           # Generate report only
#
# @package FlavorPlatform
# @since 3.5.0

set -euo pipefail

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color
BOLD='\033[1m'

# Script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$(dirname "$SCRIPT_DIR")")"
REPORTS_DIR="$PLUGIN_DIR/reports/benchmarks"

# Default configuration
WP_BASE_URL="${WP_BASE_URL:-http://sitio-prueba.local}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASS="${WP_ADMIN_PASS:-admin}"

# Available benchmarks
declare -A BENCHMARKS=(
    ["1"]="landing-simple:Landing Simple:Hero + 3 features + CTA"
    ["2"]="home-corporate:Home Corporativa:8 secciones completas"
    ["3"]="page-complex:Pagina Compleja:Animaciones, simbolos, responsive"
    ["4"]="blog-article:Articulo Blog:Contenido + sidebar"
    ["5"]="ecommerce-product:Pagina Producto:Galeria + tabs + reviews"
    ["6"]="portfolio-gallery:Portfolio:Grid masonry + lightbox"
)

# ============================================================================
# Helper Functions
# ============================================================================

print_header() {
    echo ""
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}              ${BOLD}VBP Benchmark Suite v1.0${NC}                       ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC}      Mide el rendimiento de Visual Builder Pro             ${CYAN}║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_menu() {
    echo -e "${BOLD}Selecciona un benchmark para ejecutar:${NC}"
    echo ""
    for key in $(echo "${!BENCHMARKS[@]}" | tr ' ' '\n' | sort -n); do
        IFS=':' read -r id name desc <<< "${BENCHMARKS[$key]}"
        echo -e "  ${CYAN}$key)${NC} ${BOLD}$name${NC}"
        echo -e "     ${YELLOW}$desc${NC}"
    done
    echo ""
    echo -e "  ${CYAN}a)${NC} ${BOLD}Ejecutar todos${NC}"
    echo -e "  ${CYAN}r)${NC} ${BOLD}Generar solo reporte${NC}"
    echo -e "  ${CYAN}h)${NC} ${BOLD}Ver historial${NC}"
    echo -e "  ${CYAN}c)${NC} ${BOLD}Limpiar historial${NC}"
    echo -e "  ${CYAN}q)${NC} ${BOLD}Salir${NC}"
    echo ""
}

check_dependencies() {
    local missing=()

    if ! command -v node &> /dev/null; then
        missing+=("node")
    fi

    if ! command -v npx &> /dev/null; then
        missing+=("npx")
    fi

    if [ ${#missing[@]} -gt 0 ]; then
        echo -e "${RED}Error: Dependencias faltantes: ${missing[*]}${NC}"
        echo "Instala Node.js para continuar."
        exit 1
    fi
}

ensure_reports_dir() {
    if [ ! -d "$REPORTS_DIR" ]; then
        mkdir -p "$REPORTS_DIR"
        echo -e "${GREEN}Directorio de reportes creado: $REPORTS_DIR${NC}"
    fi
}

# ============================================================================
# Benchmark Execution
# ============================================================================

run_manual_benchmark() {
    local benchmark_id="$1"
    local benchmark_name="$2"

    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}Ejecutando: $benchmark_name${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""

    echo -e "${YELLOW}Instrucciones:${NC}"
    echo "1. El navegador se abrira con el editor VBP"
    echo "2. Completa los pasos del benchmark manualmente"
    echo "3. Presiona Alt+B para abrir el panel de benchmark"
    echo "4. Marca cada paso como completado (click o Alt+S)"
    echo "5. Presiona 'Finalizar' cuando termines"
    echo ""

    read -p "Presiona Enter para continuar..."

    # Open browser with VBP editor and inject benchmark UI
    local url="$WP_BASE_URL/wp-admin/admin.php?page=vbp-editor&post_id=new&benchmark=$benchmark_id"

    # Try to open browser
    if command -v xdg-open &> /dev/null; then
        xdg-open "$url" &
    elif command -v open &> /dev/null; then
        open "$url" &
    else
        echo -e "${YELLOW}Abre manualmente: $url${NC}"
    fi

    echo ""
    echo -e "${GREEN}Navegador abierto. Completa el benchmark y vuelve aqui.${NC}"
    echo ""
    read -p "Presiona Enter cuando hayas terminado..."
}

run_automated_benchmark() {
    local benchmark_id="${1:-all}"

    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}Ejecutando benchmarks automatizados con Playwright${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""

    # Check if Playwright is installed
    if [ ! -d "$PLUGIN_DIR/node_modules/@playwright" ]; then
        echo -e "${YELLOW}Instalando Playwright...${NC}"
        cd "$PLUGIN_DIR"
        npm install @playwright/test
        npx playwright install chromium
    fi

    # Set environment variables
    export WP_BASE_URL
    export WP_ADMIN_USER
    export WP_ADMIN_PASS

    # Run Playwright tests
    cd "$PLUGIN_DIR"

    if [ "$benchmark_id" = "all" ]; then
        echo -e "${CYAN}Ejecutando todos los benchmarks...${NC}"
        npx playwright test tools/benchmarks/benchmark-automated.js \
            --reporter=list \
            --timeout=120000
    else
        echo -e "${CYAN}Ejecutando benchmark: $benchmark_id${NC}"
        npx playwright test tools/benchmarks/benchmark-automated.js \
            --grep "$benchmark_id" \
            --reporter=list \
            --timeout=120000
    fi

    echo ""
    echo -e "${GREEN}Benchmarks completados!${NC}"
    echo -e "Resultados en: $REPORTS_DIR"
}

run_quick_benchmark() {
    local benchmark_id="$1"

    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}Benchmark rapido: $benchmark_id${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""

    # Run Node.js script for quick benchmark simulation
    node -e "
        const { BenchmarkRunner } = require('$SCRIPT_DIR/benchmark-runner.js');
        const { BENCHMARKS, COMPETITORS } = require('$SCRIPT_DIR/benchmark-config.js');

        const benchmark = BENCHMARKS['$benchmark_id'];
        if (!benchmark) {
            console.error('Benchmark no encontrado: $benchmark_id');
            process.exit(1);
        }

        console.log('Benchmark: ' + benchmark.name);
        console.log('Descripcion: ' + benchmark.description);
        console.log('Pasos: ' + benchmark.steps.length);
        console.log('');
        console.log('Metricas esperadas:');
        console.log('  Tiempo: ' + benchmark.expectedMetrics.time.target + 's (min: ' + benchmark.expectedMetrics.time.min + 's, max: ' + benchmark.expectedMetrics.time.max + 's)');
        console.log('  Clicks: ' + benchmark.expectedMetrics.clicks.target + ' (min: ' + benchmark.expectedMetrics.clicks.min + ', max: ' + benchmark.expectedMetrics.clicks.max + ')');
        console.log('');
        console.log('Comparacion con competidores:');

        Object.entries(COMPETITORS).forEach(([id, comp]) => {
            const data = comp.benchmarks['$benchmark_id'];
            if (data) {
                console.log('  ' + comp.name + ': ' + data.avgTime + 's, ' + data.avgClicks + ' clicks');
            }
        });
    "
}

# ============================================================================
# Reporting
# ============================================================================

generate_report() {
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}Generando reporte de benchmarks${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""

    ensure_reports_dir

    local report_file="$REPORTS_DIR/BENCHMARK-RESULTS.md"
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")

    # Find latest JSON results
    local latest_json=$(ls -t "$REPORTS_DIR"/benchmark-results-*.json 2>/dev/null | head -1)

    if [ -z "$latest_json" ]; then
        echo -e "${YELLOW}No hay resultados de benchmarks disponibles.${NC}"
        echo "Ejecuta algunos benchmarks primero."
        return 1
    fi

    echo -e "${CYAN}Procesando: $latest_json${NC}"

    # Generate report using Node.js
    node -e "
        const fs = require('fs');
        const path = require('path');
        const { BENCHMARKS, COMPETITORS } = require('$SCRIPT_DIR/benchmark-config.js');

        const resultsPath = '$latest_json';
        const data = JSON.parse(fs.readFileSync(resultsPath, 'utf8'));

        let report = '# VBP Benchmark Results\n\n';
        report += 'Generated: $timestamp\n\n';
        report += '## Summary\n\n';
        report += '| Benchmark | Time | Score | Status |\n';
        report += '|-----------|------|-------|--------|\n';

        if (data.results && data.results.length > 0) {
            data.results.forEach(result => {
                const status = result.scores.overall >= 75 ? 'OK' : result.scores.overall >= 50 ? 'WARN' : 'FAIL';
                report += '| ' + result.benchmarkName + ' | ' + result.vbp.time.toFixed(1) + 's | ' + result.scores.overall.toFixed(0) + '/100 | ' + status + ' |\n';
            });
        } else {
            report += '| No hay resultados | - | - | - |\n';
        }

        report += '\n## Available Benchmarks\n\n';
        Object.values(BENCHMARKS).forEach(b => {
            report += '### ' + b.name + '\n\n';
            report += '- **ID**: ' + b.id + '\n';
            report += '- **Description**: ' + b.description + '\n';
            report += '- **Category**: ' + b.category + '\n';
            report += '- **Difficulty**: ' + b.difficulty + '\n';
            report += '- **Target Time**: ' + b.expectedMetrics.time.target + 's\n';
            report += '- **Steps**: ' + b.steps.length + '\n\n';
        });

        fs.writeFileSync('$report_file', report);
        console.log('Reporte generado: $report_file');
    "

    echo ""
    echo -e "${GREEN}Reporte generado exitosamente!${NC}"
    echo -e "Ubicacion: $report_file"
}

show_history() {
    echo ""
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${BOLD}Historial de Benchmarks${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════════${NC}"
    echo ""

    ensure_reports_dir

    local json_files=$(ls -t "$REPORTS_DIR"/benchmark-results-*.json 2>/dev/null)

    if [ -z "$json_files" ]; then
        echo -e "${YELLOW}No hay historial de benchmarks.${NC}"
        return
    fi

    echo -e "${BOLD}Archivos de resultados disponibles:${NC}"
    echo ""

    local count=0
    while IFS= read -r json_file; do
        count=$((count + 1))
        local filename=$(basename "$json_file")
        local filesize=$(du -h "$json_file" | cut -f1)
        local filedate=$(stat -c %y "$json_file" 2>/dev/null | cut -d'.' -f1 || stat -f "%Sm" "$json_file" 2>/dev/null)

        echo -e "  ${CYAN}$count)${NC} $filename ($filesize)"
        echo -e "     ${YELLOW}$filedate${NC}"

        if [ $count -ge 10 ]; then
            echo ""
            echo -e "  ${YELLOW}(mostrando ultimos 10 de $(echo "$json_files" | wc -l) archivos)${NC}"
            break
        fi
    done <<< "$json_files"

    echo ""
}

clear_history() {
    echo ""
    echo -e "${YELLOW}ADVERTENCIA: Esto eliminara todos los resultados de benchmarks.${NC}"
    read -p "Estas seguro? (s/N): " confirm

    if [[ "$confirm" =~ ^[sS]$ ]]; then
        rm -f "$REPORTS_DIR"/benchmark-results-*.json
        rm -f "$REPORTS_DIR"/*.png
        echo -e "${GREEN}Historial limpiado.${NC}"
    else
        echo "Operacion cancelada."
    fi
}

# ============================================================================
# Main
# ============================================================================

main() {
    print_header
    check_dependencies
    ensure_reports_dir

    # Parse command line arguments
    local benchmark_id=""
    local run_all=false
    local automated=false
    local report_only=false

    while [[ $# -gt 0 ]]; do
        case $1 in
            --all|-a)
                run_all=true
                shift
                ;;
            --benchmark=*)
                benchmark_id="${1#*=}"
                shift
                ;;
            --automated|-A)
                automated=true
                shift
                ;;
            --report|-r)
                report_only=true
                shift
                ;;
            --help|-h)
                echo "Uso: $0 [opciones]"
                echo ""
                echo "Opciones:"
                echo "  --all, -a              Ejecutar todos los benchmarks"
                echo "  --benchmark=ID         Ejecutar benchmark especifico"
                echo "  --automated, -A        Usar Playwright (automatizado)"
                echo "  --report, -r           Solo generar reporte"
                echo "  --help, -h             Mostrar ayuda"
                exit 0
                ;;
            *)
                echo -e "${RED}Opcion desconocida: $1${NC}"
                exit 1
                ;;
        esac
    done

    # Handle command line arguments
    if [ "$report_only" = true ]; then
        generate_report
        exit 0
    fi

    if [ "$run_all" = true ]; then
        if [ "$automated" = true ]; then
            run_automated_benchmark "all"
        else
            for key in $(echo "${!BENCHMARKS[@]}" | tr ' ' '\n' | sort -n); do
                IFS=':' read -r id name desc <<< "${BENCHMARKS[$key]}"
                run_quick_benchmark "$id"
                echo ""
            done
        fi
        generate_report
        exit 0
    fi

    if [ -n "$benchmark_id" ]; then
        if [ "$automated" = true ]; then
            run_automated_benchmark "$benchmark_id"
        else
            run_quick_benchmark "$benchmark_id"
        fi
        exit 0
    fi

    # Interactive mode
    while true; do
        print_menu
        read -p "Selecciona opcion: " choice

        case $choice in
            [1-6])
                IFS=':' read -r id name desc <<< "${BENCHMARKS[$choice]}"
                echo ""
                echo -e "${BOLD}Tipo de ejecucion:${NC}"
                echo "  1) Manual (navegador)"
                echo "  2) Automatizado (Playwright)"
                echo "  3) Info rapida"
                read -p "Selecciona: " exec_type

                case $exec_type in
                    1)
                        run_manual_benchmark "$id" "$name"
                        ;;
                    2)
                        run_automated_benchmark "$id"
                        ;;
                    3)
                        run_quick_benchmark "$id"
                        ;;
                    *)
                        echo -e "${RED}Opcion invalida${NC}"
                        ;;
                esac
                ;;
            a|A)
                run_automated_benchmark "all"
                generate_report
                ;;
            r|R)
                generate_report
                ;;
            h|H)
                show_history
                ;;
            c|C)
                clear_history
                ;;
            q|Q)
                echo ""
                echo -e "${GREEN}Hasta luego!${NC}"
                exit 0
                ;;
            *)
                echo -e "${RED}Opcion invalida. Intenta de nuevo.${NC}"
                ;;
        esac

        echo ""
        read -p "Presiona Enter para continuar..."
        clear 2>/dev/null || true
        print_header
    done
}

# Run main
main "$@"
