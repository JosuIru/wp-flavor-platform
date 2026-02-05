#!/bin/bash
# =============================================================================
# Build Custom APK Script
# Genera APKs personalizados con el logo y configuración del sitio WordPress
# =============================================================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Configuración por defecto
FLUTTER_BIN="${FLUTTER_BIN:-flutter}"
OUTPUT_DIR="${OUTPUT_DIR:-$SCRIPT_DIR/build/custom-apks}"

# Funciones de utilidad
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Mostrar ayuda
show_help() {
    echo ""
    echo "Uso: $0 [opciones]"
    echo ""
    echo "Genera APKs personalizados con el logo y configuración del sitio WordPress."
    echo ""
    echo "Opciones:"
    echo "  -u, --url URL         URL del sitio WordPress (requerido)"
    echo "  -o, --output DIR      Directorio de salida (default: build/custom-apks)"
    echo "  -t, --type TYPE       Tipo de app: client, admin, both (default: both)"
    echo "  -l, --logo PATH       Ruta local al logo (PNG/SVG, min 512x512)"
    echo "  -n, --name NAME       Nombre de la app"
    echo "  -c, --color COLOR     Color primario en hex (ej: #2E7D32)"
    echo "  --flutter PATH        Ruta al ejecutable de Flutter"
    echo "  -h, --help            Mostrar esta ayuda"
    echo ""
    echo "Ejemplos:"
    echo "  $0 -u https://mi-negocio.com"
    echo "  $0 -u https://mi-negocio.com -l /ruta/logo.png -n 'Mi App'"
    echo "  $0 -u https://mi-negocio.com -t client -c '#FF5722'"
    echo ""
}

# Verificar dependencias
check_dependencies() {
    log_info "Verificando dependencias..."

    # Flutter
    if ! command -v "$FLUTTER_BIN" &> /dev/null; then
        # Intentar encontrar Flutter
        if [ -f "$HOME/flutter/bin/flutter" ]; then
            FLUTTER_BIN="$HOME/flutter/bin/flutter"
        elif [ -f "/opt/flutter/bin/flutter" ]; then
            FLUTTER_BIN="/opt/flutter/bin/flutter"
        else
            log_error "Flutter no encontrado. Instálalo o usa --flutter para especificar la ruta."
            exit 1
        fi
    fi
    log_success "Flutter: $FLUTTER_BIN"

    # ImageMagick (convert)
    if ! command -v convert &> /dev/null; then
        log_error "ImageMagick (convert) no encontrado. Instálalo con: sudo apt install imagemagick"
        exit 1
    fi
    log_success "ImageMagick: $(which convert)"

    # curl
    if ! command -v curl &> /dev/null; then
        log_error "curl no encontrado."
        exit 1
    fi
    log_success "curl: $(which curl)"

    # jq (opcional pero recomendado)
    if command -v jq &> /dev/null; then
        HAS_JQ=true
        log_success "jq: $(which jq)"
    else
        HAS_JQ=false
        log_warning "jq no encontrado. Usando python para JSON."
    fi
}

# Obtener configuración del sitio
fetch_site_config() {
    local url="$1"
    log_info "Obteniendo configuración del sitio: $url"

    local api_url="${url}/wp-json/chat-ia-mobile/v1/site-info"
    local response

    response=$(curl -s --max-time 30 "$api_url" 2>/dev/null)

    if [ -z "$response" ]; then
        log_error "No se pudo conectar con el servidor"
        return 1
    fi

    # Verificar si es JSON válido
    if ! echo "$response" | python3 -c "import sys,json; json.load(sys.stdin)" 2>/dev/null; then
        log_error "Respuesta inválida del servidor"
        return 1
    fi

    # Extraer datos
    if [ "$HAS_JQ" = true ]; then
        SITE_NAME=$(echo "$response" | jq -r '.name // "App"')
        SITE_LOGO=$(echo "$response" | jq -r '.logo_url // ""')
        SITE_COLOR=$(echo "$response" | jq -r '.config.primary_color // "#2196F3"')
    else
        SITE_NAME=$(echo "$response" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('name', 'App'))")
        SITE_LOGO=$(echo "$response" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('logo_url', ''))")
        SITE_COLOR=$(echo "$response" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('config', {}).get('primary_color', '#2196F3'))")
    fi

    log_success "Sitio: $SITE_NAME"
    log_success "Logo: $SITE_LOGO"
    log_success "Color: $SITE_COLOR"

    return 0
}

# Descargar y procesar logo
process_logo() {
    local logo_source="$1"
    local color="$2"
    local output_path="$SCRIPT_DIR/assets/icon/app_icon.png"

    mkdir -p "$(dirname "$output_path")"

    log_info "Procesando logo..."

    # Si es URL, descargar
    if [[ "$logo_source" =~ ^https?:// ]]; then
        log_info "Descargando logo desde: $logo_source"
        local temp_logo="/tmp/app_logo_temp"

        if [[ "$logo_source" =~ \.svg$ ]]; then
            temp_logo="${temp_logo}.svg"
        else
            temp_logo="${temp_logo}.png"
        fi

        curl -s -o "$temp_logo" "$logo_source"

        if [ ! -f "$temp_logo" ] || [ ! -s "$temp_logo" ]; then
            log_error "No se pudo descargar el logo"
            return 1
        fi

        logo_source="$temp_logo"
    fi

    # Verificar que existe
    if [ ! -f "$logo_source" ]; then
        log_error "Archivo de logo no encontrado: $logo_source"
        return 1
    fi

    # Convertir a PNG 1024x1024 con fondo de color
    log_info "Convirtiendo logo a icono de app..."

    if [[ "$logo_source" =~ \.svg$ ]]; then
        # SVG: convertir con densidad alta
        convert -background "$color" -density 300 "$logo_source" \
            -resize 800x800 -gravity center -extent 1024x1024 \
            "$output_path" 2>/dev/null
    else
        # PNG/JPG: redimensionar
        convert "$logo_source" -resize 800x800 \
            -background "$color" -gravity center -extent 1024x1024 \
            "$output_path" 2>/dev/null
    fi

    if [ ! -f "$output_path" ]; then
        log_error "Error al procesar el logo"
        return 1
    fi

    # Verificar dimensiones
    local dims=$(identify -format "%wx%h" "$output_path" 2>/dev/null)
    if [ "$dims" != "1024x1024" ]; then
        log_warning "Dimensiones del icono: $dims (esperado: 1024x1024)"
    fi

    log_success "Icono generado: $output_path"
    return 0
}

# Actualizar configuración del servidor
update_server_config() {
    local url="$1"

    log_info "Actualizando server_config.dart..."

    local config_file="$SCRIPT_DIR/lib/core/config/server_config.dart"

    if [ ! -f "$config_file" ]; then
        log_error "Archivo de configuración no encontrado: $config_file"
        return 1
    fi

    # Backup
    cp "$config_file" "${config_file}.bak"

    # Reemplazar URL por defecto
    sed -i "s|static const String defaultServerUrl = '.*';|static const String defaultServerUrl = '$url';|" "$config_file"

    log_success "Configuración actualizada"
    return 0
}

# Generar iconos con flutter_launcher_icons
generate_icons() {
    log_info "Generando iconos de la app..."

    cd "$SCRIPT_DIR"

    # Ejecutar flutter_launcher_icons
    "$FLUTTER_BIN" pub get > /dev/null 2>&1
    dart run flutter_launcher_icons 2>&1 | grep -v "^$"

    log_success "Iconos generados"
}

# Construir APK
build_apk() {
    local app_type="$1"  # client o admin
    local target_file="lib/main_${app_type}.dart"

    log_info "Construyendo APK de $app_type..."

    cd "$SCRIPT_DIR"

    if [ ! -f "$target_file" ]; then
        log_error "Archivo de entrada no encontrado: $target_file"
        return 1
    fi

    # Build
    "$FLUTTER_BIN" build apk --release --target="$target_file" 2>&1 | tail -5

    # Buscar APK generado
    local apk_path=$(find build -name "app-${app_type}-release.apk" -o -name "app-release.apk" 2>/dev/null | head -1)

    if [ -z "$apk_path" ] || [ ! -f "$apk_path" ]; then
        # Intentar ubicación alternativa
        apk_path="build/app/outputs/flutter-apk/app-${app_type}-release.apk"
    fi

    if [ -f "$apk_path" ]; then
        mkdir -p "$OUTPUT_DIR"
        local final_name="${SITE_NAME// /-}-${app_type}.apk"
        final_name=$(echo "$final_name" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9.-]/-/g')
        cp "$apk_path" "$OUTPUT_DIR/$final_name"
        log_success "APK generado: $OUTPUT_DIR/$final_name"
    else
        log_error "No se encontró el APK generado"
        return 1
    fi
}

# Restaurar configuración original
restore_config() {
    local config_file="$SCRIPT_DIR/lib/core/config/server_config.dart"

    if [ -f "${config_file}.bak" ]; then
        mv "${config_file}.bak" "$config_file"
        log_info "Configuración restaurada"
    fi
}

# Parsear argumentos
SITE_URL=""
APP_TYPE="both"
LOGO_PATH=""
APP_NAME=""
PRIMARY_COLOR=""

while [[ $# -gt 0 ]]; do
    case $1 in
        -u|--url)
            SITE_URL="$2"
            shift 2
            ;;
        -o|--output)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        -t|--type)
            APP_TYPE="$2"
            shift 2
            ;;
        -l|--logo)
            LOGO_PATH="$2"
            shift 2
            ;;
        -n|--name)
            APP_NAME="$2"
            shift 2
            ;;
        -c|--color)
            PRIMARY_COLOR="$2"
            shift 2
            ;;
        --flutter)
            FLUTTER_BIN="$2"
            shift 2
            ;;
        -h|--help)
            show_help
            exit 0
            ;;
        *)
            log_error "Opción desconocida: $1"
            show_help
            exit 1
            ;;
    esac
done

# Validar URL requerida
if [ -z "$SITE_URL" ]; then
    log_error "URL del sitio requerida. Usa -u o --url"
    show_help
    exit 1
fi

# Limpiar URL
SITE_URL="${SITE_URL%/}"  # Quitar / final

# Banner
echo ""
echo "=============================================="
echo "   Chat IA - Custom APK Builder"
echo "=============================================="
echo ""

# Ejecutar pasos
check_dependencies

# Obtener config del sitio si no se proporcionaron valores
if fetch_site_config "$SITE_URL"; then
    # Usar valores del sitio si no se especificaron
    [ -z "$APP_NAME" ] && APP_NAME="$SITE_NAME"
    [ -z "$LOGO_PATH" ] && LOGO_PATH="$SITE_LOGO"
    [ -z "$PRIMARY_COLOR" ] && PRIMARY_COLOR="$SITE_COLOR"
else
    # Usar valores por defecto
    [ -z "$APP_NAME" ] && APP_NAME="Chat App"
    [ -z "$PRIMARY_COLOR" ] && PRIMARY_COLOR="#2196F3"
fi

echo ""
log_info "Configuración final:"
log_info "  Nombre: $APP_NAME"
log_info "  URL: $SITE_URL"
log_info "  Logo: ${LOGO_PATH:-'(por defecto)'}"
log_info "  Color: $PRIMARY_COLOR"
log_info "  Tipo: $APP_TYPE"
echo ""

# Procesar logo si se especificó
if [ -n "$LOGO_PATH" ]; then
    if ! process_logo "$LOGO_PATH" "$PRIMARY_COLOR"; then
        log_warning "Usando icono por defecto"
    else
        generate_icons
    fi
fi

# Actualizar configuración
update_server_config "$SITE_URL"

# Trap para restaurar config en caso de error
trap restore_config EXIT

# Construir APKs
case $APP_TYPE in
    client)
        build_apk "client"
        ;;
    admin)
        build_apk "admin"
        ;;
    both)
        build_apk "client"
        build_apk "admin"
        ;;
    *)
        log_error "Tipo de app inválido: $APP_TYPE"
        exit 1
        ;;
esac

# Resumen final
echo ""
echo "=============================================="
log_success "Build completado!"
echo ""
echo "APKs generados en: $OUTPUT_DIR"
ls -la "$OUTPUT_DIR"/*.apk 2>/dev/null || true
echo ""
echo "=============================================="
