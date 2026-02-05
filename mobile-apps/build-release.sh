#!/bin/bash
# =============================================================================
# Build Release Script - APKs y AABs para Google Play
# =============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Directorio del script
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Configuración
FLUTTER_BIN="${FLUTTER_BIN:-flutter}"
KEYSTORE_DIR="$SCRIPT_DIR/android/keystore"
KEY_PROPERTIES="$SCRIPT_DIR/android/key.properties"
OUTPUT_DIR="$SCRIPT_DIR/release"

# Funciones de utilidad
log_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
log_success() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warning() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

show_help() {
    echo ""
    echo "Uso: $0 [opciones]"
    echo ""
    echo "Genera APKs y AABs firmados para producción."
    echo ""
    echo "Opciones:"
    echo "  --setup           Configurar keystore (primera vez)"
    echo "  --apk             Generar solo APKs"
    echo "  --aab             Generar solo AABs (Play Store)"
    echo "  --all             Generar APKs y AABs (default)"
    echo "  --client          Solo app cliente"
    echo "  --admin           Solo app admin"
    echo "  --version X.Y.Z   Establecer versión (ej: 1.0.0)"
    echo "  --build N         Establecer build number"
    echo "  -h, --help        Mostrar ayuda"
    echo ""
    echo "Ejemplo:"
    echo "  $0 --setup                    # Primera vez: crear keystore"
    echo "  $0 --all --version 1.0.0      # Generar todo con versión 1.0.0"
    echo "  $0 --aab --client             # Solo AAB cliente para Play Store"
    echo ""
}

# Buscar Flutter
find_flutter() {
    if command -v "$FLUTTER_BIN" &> /dev/null; then
        return 0
    fi

    for path in "$HOME/flutter/bin/flutter" "/opt/flutter/bin/flutter" "$HOME/snap/flutter/common/flutter/bin/flutter"; do
        if [ -f "$path" ]; then
            FLUTTER_BIN="$path"
            return 0
        fi
    done

    log_error "Flutter no encontrado. Instálalo o usa FLUTTER_BIN=ruta"
    exit 1
}

# Configurar keystore
setup_keystore() {
    log_info "Configurando keystore para firma de release..."

    mkdir -p "$KEYSTORE_DIR"

    KEYSTORE_FILE="$KEYSTORE_DIR/upload-keystore.jks"

    if [ -f "$KEYSTORE_FILE" ]; then
        log_warning "El keystore ya existe: $KEYSTORE_FILE"
        read -p "¿Quieres crear uno nuevo? (s/N): " confirm
        if [[ ! "$confirm" =~ ^[Ss]$ ]]; then
            log_info "Usando keystore existente"
            return 0
        fi
        mv "$KEYSTORE_FILE" "${KEYSTORE_FILE}.backup.$(date +%s)"
    fi

    echo ""
    log_info "Creando nuevo keystore..."
    echo ""

    # Solicitar información
    read -p "Nombre de la organización (ej: Mi Empresa): " ORG_NAME
    read -p "Email de contacto: " EMAIL
    read -p "País (código 2 letras, ej: ES): " COUNTRY
    read -sp "Contraseña del keystore (mínimo 6 caracteres): " STORE_PASS
    echo ""

    if [ ${#STORE_PASS} -lt 6 ]; then
        log_error "La contraseña debe tener al menos 6 caracteres"
        exit 1
    fi

    # Generar keystore
    keytool -genkey -v \
        -keystore "$KEYSTORE_FILE" \
        -keyalg RSA \
        -keysize 2048 \
        -validity 10000 \
        -alias upload \
        -storepass "$STORE_PASS" \
        -keypass "$STORE_PASS" \
        -dname "CN=$ORG_NAME, O=$ORG_NAME, C=$COUNTRY"

    if [ $? -ne 0 ]; then
        log_error "Error al crear keystore"
        exit 1
    fi

    log_success "Keystore creado: $KEYSTORE_FILE"

    # Crear key.properties
    cat > "$KEY_PROPERTIES" << EOF
storePassword=$STORE_PASS
keyPassword=$STORE_PASS
keyAlias=upload
storeFile=../keystore/upload-keystore.jks
EOF

    log_success "key.properties creado"

    echo ""
    log_warning "IMPORTANTE: Guarda estos archivos en un lugar seguro:"
    echo "  - $KEYSTORE_FILE"
    echo "  - $KEY_PROPERTIES"
    echo ""
    log_warning "Si pierdes el keystore, NO podrás actualizar la app en Play Store"
    echo ""
}

# Verificar configuración de firma
check_signing() {
    if [ ! -f "$KEY_PROPERTIES" ]; then
        log_error "No hay configuración de firma. Ejecuta primero: $0 --setup"
        exit 1
    fi

    # Verificar que el keystore existe
    STORE_FILE=$(grep "storeFile" "$KEY_PROPERTIES" | cut -d'=' -f2)
    KEYSTORE_PATH="$SCRIPT_DIR/android/app/$STORE_FILE"

    if [ ! -f "$KEYSTORE_PATH" ]; then
        log_error "Keystore no encontrado: $KEYSTORE_PATH"
        exit 1
    fi

    log_success "Configuración de firma verificada"
}

# Actualizar versión
update_version() {
    local version="$1"
    local build="$2"

    PUBSPEC="$SCRIPT_DIR/pubspec.yaml"

    if [ -n "$version" ]; then
        log_info "Actualizando versión a: $version"

        # Obtener build number actual o usar el proporcionado
        if [ -z "$build" ]; then
            current_version=$(grep "^version:" "$PUBSPEC" | head -1)
            if [[ "$current_version" =~ \+([0-9]+) ]]; then
                build=$((${BASH_REMATCH[1]} + 1))
            else
                build=1
            fi
        fi

        sed -i "s/^version: .*/version: $version+$build/" "$PUBSPEC"
        log_success "Versión actualizada: $version+$build"
    fi
}

# Construir APK
build_apk() {
    local flavor="$1"  # client o admin
    local target="lib/main_${flavor}.dart"

    log_info "Construyendo APK $flavor..."

    "$FLUTTER_BIN" build apk --release \
        --target="$target" \
        --flavor="$flavor" \
        2>&1 | grep -v "^$" | tail -20

    # Buscar APK generado
    local apk_path="$SCRIPT_DIR/build/app/outputs/flutter-apk/app-${flavor}-release.apk"

    if [ -f "$apk_path" ]; then
        mkdir -p "$OUTPUT_DIR"
        local version=$(grep "^version:" "$SCRIPT_DIR/pubspec.yaml" | sed 's/version: //' | cut -d'+' -f1)
        local output_name="basabere-${flavor}-v${version}.apk"
        cp "$apk_path" "$OUTPUT_DIR/$output_name"
        log_success "APK generado: $OUTPUT_DIR/$output_name"
    else
        log_error "APK no encontrado en: $apk_path"
        return 1
    fi
}

# Construir AAB (Android App Bundle)
build_aab() {
    local flavor="$1"
    local target="lib/main_${flavor}.dart"

    log_info "Construyendo AAB $flavor para Play Store..."

    "$FLUTTER_BIN" build appbundle --release \
        --target="$target" \
        --flavor="$flavor" \
        2>&1 | grep -v "^$" | tail -20

    # Buscar AAB generado
    local aab_path="$SCRIPT_DIR/build/app/outputs/bundle/${flavor}Release/app-${flavor}-release.aab"

    if [ -f "$aab_path" ]; then
        mkdir -p "$OUTPUT_DIR"
        local version=$(grep "^version:" "$SCRIPT_DIR/pubspec.yaml" | sed 's/version: //' | cut -d'+' -f1)
        local output_name="basabere-${flavor}-v${version}.aab"
        cp "$aab_path" "$OUTPUT_DIR/$output_name"
        log_success "AAB generado: $OUTPUT_DIR/$output_name"
    else
        log_error "AAB no encontrado en: $aab_path"
        return 1
    fi
}

# Parsear argumentos
BUILD_APK=false
BUILD_AAB=false
BUILD_CLIENT=false
BUILD_ADMIN=false
DO_SETUP=false
VERSION=""
BUILD_NUM=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --setup)
            DO_SETUP=true
            shift
            ;;
        --apk)
            BUILD_APK=true
            shift
            ;;
        --aab)
            BUILD_AAB=true
            shift
            ;;
        --all)
            BUILD_APK=true
            BUILD_AAB=true
            shift
            ;;
        --client)
            BUILD_CLIENT=true
            shift
            ;;
        --admin)
            BUILD_ADMIN=true
            shift
            ;;
        --version)
            VERSION="$2"
            shift 2
            ;;
        --build)
            BUILD_NUM="$2"
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

# Defaults
if [ "$BUILD_APK" = false ] && [ "$BUILD_AAB" = false ] && [ "$DO_SETUP" = false ]; then
    BUILD_APK=true
    BUILD_AAB=true
fi

if [ "$BUILD_CLIENT" = false ] && [ "$BUILD_ADMIN" = false ]; then
    BUILD_CLIENT=true
    BUILD_ADMIN=true
fi

# Banner
echo ""
echo "=============================================="
echo "   Chat IA - Release Build"
echo "=============================================="
echo ""

# Setup si se solicitó
if [ "$DO_SETUP" = true ]; then
    setup_keystore
    if [ "$BUILD_APK" = false ] && [ "$BUILD_AAB" = false ]; then
        exit 0
    fi
fi

# Verificar dependencias
find_flutter
log_success "Flutter: $FLUTTER_BIN"

check_signing

# Actualizar versión si se especificó
if [ -n "$VERSION" ]; then
    update_version "$VERSION" "$BUILD_NUM"
fi

# Mostrar configuración
echo ""
log_info "Configuración de build:"
echo "  APKs: $BUILD_APK"
echo "  AABs: $BUILD_AAB"
echo "  Cliente: $BUILD_CLIENT"
echo "  Admin: $BUILD_ADMIN"
echo ""

# Limpiar build anterior
log_info "Limpiando build anterior..."
"$FLUTTER_BIN" clean > /dev/null 2>&1 || true
"$FLUTTER_BIN" pub get > /dev/null 2>&1

# Construir
if [ "$BUILD_CLIENT" = true ]; then
    if [ "$BUILD_APK" = true ]; then
        build_apk "client"
    fi
    if [ "$BUILD_AAB" = true ]; then
        build_aab "client"
    fi
fi

if [ "$BUILD_ADMIN" = true ]; then
    if [ "$BUILD_APK" = true ]; then
        build_apk "admin"
    fi
    if [ "$BUILD_AAB" = true ]; then
        build_aab "admin"
    fi
fi

# Resumen
echo ""
echo "=============================================="
log_success "Build completado!"
echo ""
echo "Archivos generados en: $OUTPUT_DIR"
ls -lh "$OUTPUT_DIR"/ 2>/dev/null || true
echo ""
echo "=============================================="
echo ""

if [ "$BUILD_AAB" = true ]; then
    log_info "Para subir a Google Play:"
    echo "  1. Ve a Google Play Console"
    echo "  2. Selecciona tu app"
    echo "  3. Producción > Crear nueva versión"
    echo "  4. Sube el archivo .aab"
    echo ""
fi
