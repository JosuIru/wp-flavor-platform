#!/bin/bash

# =============================================================================
# Script de Personalización de Apps Móviles
# =============================================================================
# Este script personaliza las apps Flutter para un negocio específico.
# Lee la configuración de config.json y modifica todos los archivos necesarios.
#
# Uso:
#   ./personalizar-app.sh                    # Usa config.json local
#   ./personalizar-app.sh mi-config.json     # Usa archivo específico
#   ./personalizar-app.sh --from-url https://ejemplo.com  # Autoconfigura desde URL
#
# =============================================================================

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${1:-config.json}"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# =============================================================================
# Autoconfiguración desde URL
# =============================================================================
if [[ "$1" == "--from-url" && -n "$2" ]]; then
    URL="$2"
    log_info "Obteniendo configuración automática desde: $URL"

    # Llamar al endpoint de autoconfiguración
    API_URL="${URL}/wp-json/chat-ia-mobile/v1/app-config/generate"

    RESPONSE=$(curl -s -w "\n%{http_code}" "$API_URL" 2>/dev/null)
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    if [[ "$HTTP_CODE" == "200" ]]; then
        echo "$BODY" > "$SCRIPT_DIR/config.json"
        log_success "Configuración guardada en config.json"
        CONFIG_FILE="config.json"
    else
        log_error "No se pudo obtener la configuración. HTTP $HTTP_CODE"
        log_info "Asegúrate de que el plugin Chat IA esté activado en $URL"
        exit 1
    fi
fi

# =============================================================================
# Verificar requisitos
# =============================================================================
log_info "Verificando requisitos..."

if ! command -v jq &> /dev/null; then
    log_error "jq no está instalado. Instálalo con: sudo apt install jq"
    exit 1
fi

if [[ ! -f "$SCRIPT_DIR/$CONFIG_FILE" ]]; then
    log_error "No se encontró $CONFIG_FILE"
    log_info "Crea el archivo o usa: ./personalizar-app.sh --from-url https://tu-sitio.com"
    exit 1
fi

log_success "Requisitos OK"

# =============================================================================
# Leer configuración
# =============================================================================
log_info "Leyendo configuración de $CONFIG_FILE..."

CONFIG="$SCRIPT_DIR/$CONFIG_FILE"

BUSINESS_NAME=$(jq -r '.business_name // "Mi Negocio"' "$CONFIG")
APP_ID=$(jq -r '.app_id // "com.ejemplo.app"' "$CONFIG")
CLIENT_APP_NAME=$(jq -r '.client_app_name // "Mi App"' "$CONFIG")
ADMIN_APP_NAME=$(jq -r '.admin_app_name // "Mi App Admin"' "$CONFIG")
DEEP_LINK_SCHEME=$(jq -r '.deep_link_scheme // "miapp"' "$CONFIG")
PRIMARY_COLOR=$(jq -r '.colors.primary // "#2196F3"' "$CONFIG")
SECONDARY_COLOR=$(jq -r '.colors.secondary // "#4CAF50"' "$CONFIG")
ACCENT_COLOR=$(jq -r '.colors.accent // "#FF9800"' "$CONFIG")
DEVELOPER_NAME=$(jq -r '.developer.name // "Desarrollador"' "$CONFIG")
DEVELOPER_EMAIL=$(jq -r '.developer.email // "info@ejemplo.com"' "$CONFIG")
DEVELOPER_PHONE=$(jq -r '.developer.phone // "+34 600 000 000"' "$CONFIG")
SERVER_URL=$(jq -r '.server_url // ""' "$CONFIG")

log_success "Configuración cargada:"
echo "  - Negocio: $BUSINESS_NAME"
echo "  - App ID: $APP_ID"
echo "  - App Cliente: $CLIENT_APP_NAME"
echo "  - App Admin: $ADMIN_APP_NAME"
echo "  - Deep Link: $DEEP_LINK_SCHEME://"
echo "  - Color primario: $PRIMARY_COLOR"

# =============================================================================
# Convertir colores hex a Flutter (0xFF...)
# =============================================================================
hex_to_flutter() {
    local hex="${1#\#}"
    echo "0xFF${hex^^}"
}

PRIMARY_FLUTTER=$(hex_to_flutter "$PRIMARY_COLOR")
SECONDARY_FLUTTER=$(hex_to_flutter "$SECONDARY_COLOR")
ACCENT_FLUTTER=$(hex_to_flutter "$ACCENT_COLOR")

# =============================================================================
# Modificar build.gradle
# =============================================================================
log_info "Modificando android/app/build.gradle..."

BUILD_GRADLE="$SCRIPT_DIR/android/app/build.gradle"

if [[ -f "$BUILD_GRADLE" ]]; then
    # Cambiar applicationId
    sed -i "s|applicationId = \"com\.[^\"]*\"|applicationId = \"$APP_ID\"|g" "$BUILD_GRADLE"

    # Cambiar nombres de apps en flavors
    sed -i "s|resValue \"string\", \"app_name\", \"[^\"]*Admin\"|resValue \"string\", \"app_name\", \"$ADMIN_APP_NAME\"|g" "$BUILD_GRADLE"
    sed -i "s|resValue \"string\", \"app_name\", \"[^\"]*\"|resValue \"string\", \"app_name\", \"$CLIENT_APP_NAME\"|g" "$BUILD_GRADLE"

    log_success "build.gradle actualizado"
else
    log_warning "build.gradle no encontrado"
fi

# =============================================================================
# Modificar app_config.dart
# =============================================================================
log_info "Modificando lib/core/config/app_config.dart..."

APP_CONFIG="$SCRIPT_DIR/lib/core/config/app_config.dart"

if [[ -f "$APP_CONFIG" ]]; then
    # Crear backup
    cp "$APP_CONFIG" "$APP_CONFIG.bak"

    # Reemplazar valores
    sed -i "s|static const String businessName = '[^']*'|static const String businessName = '$BUSINESS_NAME'|g" "$APP_CONFIG"
    sed -i "s|static const String clientAppName = '[^']*'|static const String clientAppName = '$CLIENT_APP_NAME'|g" "$APP_CONFIG"
    sed -i "s|static const String adminAppName = '[^']*'|static const String adminAppName = '$ADMIN_APP_NAME'|g" "$APP_CONFIG"
    sed -i "s|static const String developerName = '[^']*'|static const String developerName = '$DEVELOPER_NAME'|g" "$APP_CONFIG"
    sed -i "s|static const String developerEmail = '[^']*'|static const String developerEmail = '$DEVELOPER_EMAIL'|g" "$APP_CONFIG"
    sed -i "s|static const String developerPhone = '[^']*'|static const String developerPhone = '$DEVELOPER_PHONE'|g" "$APP_CONFIG"
    sed -i "s|static const String adminPackageName = '[^']*'|static const String adminPackageName = '$APP_ID.admin'|g" "$APP_CONFIG"
    sed -i "s|static const String clientPackageName = '[^']*'|static const String clientPackageName = '$APP_ID.client'|g" "$APP_CONFIG"
    sed -i "s|static const String deepLinkScheme = '[^']*'|static const String deepLinkScheme = '$DEEP_LINK_SCHEME'|g" "$APP_CONFIG"

    # Colores
    sed -i "s|static const int primaryValue = 0x[^;]*|static const int primaryValue = $PRIMARY_FLUTTER|g" "$APP_CONFIG"
    sed -i "s|static const int secondaryValue = 0x[^;]*|static const int secondaryValue = $SECONDARY_FLUTTER|g" "$APP_CONFIG"
    sed -i "s|static const int accentValue = 0x[^;]*|static const int accentValue = $ACCENT_FLUTTER|g" "$APP_CONFIG"

    log_success "app_config.dart actualizado"
else
    log_warning "app_config.dart no encontrado"
fi

# =============================================================================
# Modificar server_config.dart (URL por defecto si se proporciona)
# =============================================================================
if [[ -n "$SERVER_URL" && "$SERVER_URL" != "null" ]]; then
    log_info "Configurando URL del servidor por defecto..."

    SERVER_CONFIG="$SCRIPT_DIR/lib/core/config/server_config.dart"

    if [[ -f "$SERVER_CONFIG" ]]; then
        sed -i "s|static const String defaultServerUrl = '[^']*'|static const String defaultServerUrl = '$SERVER_URL'|g" "$SERVER_CONFIG"
        log_success "server_config.dart actualizado con URL: $SERVER_URL"
    fi
fi

# =============================================================================
# Modificar AndroidManifest.xml (deep links)
# =============================================================================
log_info "Modificando AndroidManifest.xml..."

# Admin manifest
ADMIN_MANIFEST="$SCRIPT_DIR/android/app/src/admin/AndroidManifest.xml"
if [[ -f "$ADMIN_MANIFEST" ]]; then
    sed -i "s|android:scheme=\"[^\"]*\" android:host=\"admin\"|android:scheme=\"$DEEP_LINK_SCHEME\" android:host=\"admin\"|g" "$ADMIN_MANIFEST"
    sed -i "s|android:name=\"[^\"]*\.client\"|android:name=\"$APP_ID.client\"|g" "$ADMIN_MANIFEST"
    log_success "Admin AndroidManifest.xml actualizado"
fi

# Client manifest
CLIENT_MANIFEST="$SCRIPT_DIR/android/app/src/client/AndroidManifest.xml"
if [[ -f "$CLIENT_MANIFEST" ]]; then
    sed -i "s|android:scheme=\"[^\"]*\" android:host=\"client\"|android:scheme=\"$DEEP_LINK_SCHEME\" android:host=\"client\"|g" "$CLIENT_MANIFEST"
    sed -i "s|android:name=\"[^\"]*\.admin\"|android:name=\"$APP_ID.admin\"|g" "$CLIENT_MANIFEST"
    log_success "Client AndroidManifest.xml actualizado"
fi

# =============================================================================
# Descargar logo si se proporciona URL
# =============================================================================
LOGO_URL=$(jq -r '.logo_url // ""' "$CONFIG")

if [[ -n "$LOGO_URL" && "$LOGO_URL" != "null" ]]; then
    log_info "Descargando logo desde $LOGO_URL..."

    LOGO_DIR="$SCRIPT_DIR/assets/images"
    mkdir -p "$LOGO_DIR"

    # Detectar extensión
    if [[ "$LOGO_URL" == *.svg ]]; then
        curl -s -o "$LOGO_DIR/logo.svg" "$LOGO_URL" && log_success "Logo SVG descargado"
    else
        curl -s -o "$LOGO_DIR/logo.png" "$LOGO_URL" && log_success "Logo PNG descargado"
    fi
fi

# =============================================================================
# Generar iconos de app (si existe el logo PNG)
# =============================================================================
LOGO_PNG="$SCRIPT_DIR/assets/images/logo.png"

if [[ -f "$LOGO_PNG" ]]; then
    log_info "Generando iconos de la app..."

    # Verificar si ImageMagick está instalado
    if command -v convert &> /dev/null; then
        MIPMAP_DIR="$SCRIPT_DIR/android/app/src/main/res"

        # Tamaños para cada densidad
        convert "$LOGO_PNG" -resize 48x48 "$MIPMAP_DIR/mipmap-mdpi/ic_launcher.png" 2>/dev/null || true
        convert "$LOGO_PNG" -resize 72x72 "$MIPMAP_DIR/mipmap-hdpi/ic_launcher.png" 2>/dev/null || true
        convert "$LOGO_PNG" -resize 96x96 "$MIPMAP_DIR/mipmap-xhdpi/ic_launcher.png" 2>/dev/null || true
        convert "$LOGO_PNG" -resize 144x144 "$MIPMAP_DIR/mipmap-xxhdpi/ic_launcher.png" 2>/dev/null || true
        convert "$LOGO_PNG" -resize 192x192 "$MIPMAP_DIR/mipmap-xxxhdpi/ic_launcher.png" 2>/dev/null || true

        log_success "Iconos generados"
    else
        log_warning "ImageMagick no instalado. Genera los iconos manualmente."
        log_info "Instala con: sudo apt install imagemagick"
    fi
fi

# =============================================================================
# Resumen final
# =============================================================================
echo ""
echo "=============================================="
echo -e "${GREEN}Personalización completada${NC}"
echo "=============================================="
echo ""
echo "Próximos pasos:"
echo ""
echo "1. Verificar cambios:"
echo "   git diff"
echo ""
echo "2. Instalar dependencias:"
echo "   flutter pub get"
echo ""
echo "3. Compilar apps:"
echo "   flutter build apk --flavor admin -t lib/main_admin.dart --release"
echo "   flutter build apk --flavor client -t lib/main_client.dart --release"
echo ""
echo "4. Los APKs estarán en:"
echo "   build/app/outputs/flutter-apk/app-admin-release.apk"
echo "   build/app/outputs/flutter-apk/app-client-release.apk"
echo ""
echo "5. Para Google Play (AAB):"
echo "   flutter build appbundle --flavor admin -t lib/main_admin.dart"
echo "   flutter build appbundle --flavor client -t lib/main_client.dart"
echo ""
