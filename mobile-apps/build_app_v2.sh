#!/bin/bash
#===============================================================================
# Flavor App Builder v2.0
#
# Script mejorado para construir APKs Flutter con:
# - Validación previa de configuración
# - Versionado automático
# - Logging detallado
# - Soporte para múltiples flavors
# - Verificación de dependencias
#===============================================================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_DIR="$SCRIPT_DIR/logs"
BUILD_DIR="$SCRIPT_DIR/build"
PUBSPEC="$SCRIPT_DIR/pubspec.yaml"

# Crear directorio de logs si no existe
mkdir -p "$LOG_DIR"

# Timestamp para logs
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
LOG_FILE="$LOG_DIR/build_$TIMESTAMP.log"

#===============================================================================
# Funciones de utilidad
#===============================================================================

log() {
    local level=$1
    shift
    local message="$@"
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")

    case $level in
        INFO)  echo -e "${BLUE}[INFO]${NC} $message" ;;
        OK)    echo -e "${GREEN}[OK]${NC} $message" ;;
        WARN)  echo -e "${YELLOW}[WARN]${NC} $message" ;;
        ERROR) echo -e "${RED}[ERROR]${NC} $message" ;;
        *)     echo "$message" ;;
    esac

    echo "[$timestamp] [$level] $message" >> "$LOG_FILE"
}

show_header() {
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}           ${GREEN}Flavor App Builder v2.0${NC}                        ${CYAN}║${NC}"
    echo -e "${CYAN}╠════════════════════════════════════════════════════════════╣${NC}"
    echo -e "${CYAN}║${NC}  Flavor: ${YELLOW}$FLAVOR${NC}                                            ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC}  Mode:   ${YELLOW}$MODE${NC}                                           ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC}  Log:    ${YELLOW}$LOG_FILE${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

show_usage() {
    echo ""
    echo -e "${CYAN}Uso:${NC} ./build_app_v2.sh <flavor> [opciones]"
    echo ""
    echo -e "${CYAN}Flavors:${NC}"
    echo "  client    - App para clientes/usuarios"
    echo "  admin     - App para administradores"
    echo ""
    echo -e "${CYAN}Opciones:${NC}"
    echo "  --release          Build de producción (default: debug)"
    echo "  --debug            Build de desarrollo"
    echo "  --bump-version     Incrementar versión automáticamente"
    echo "  --validate-only    Solo validar, no construir"
    echo "  --site-url=URL     URL del sitio para validar configuración"
    echo "  --skip-validation  Saltar validación de configuración"
    echo "  --clean            Limpiar antes de construir"
    echo "  --install          Instalar APK después de construir"
    echo "  --aab              Generar App Bundle (AAB) además de APK"
    echo "  --help             Mostrar esta ayuda"
    echo ""
    echo -e "${CYAN}Ejemplos:${NC}"
    echo "  ./build_app_v2.sh client --release"
    echo "  ./build_app_v2.sh admin --debug --bump-version"
    echo "  ./build_app_v2.sh client --release --site-url=https://mi-sitio.com"
    echo ""
}

#===============================================================================
# Funciones de validación
#===============================================================================

check_flutter() {
    log INFO "Verificando instalación de Flutter..."

    if ! command -v flutter &> /dev/null; then
        log ERROR "Flutter no está instalado o no está en el PATH"
        exit 1
    fi

    local flutter_version=$(flutter --version --machine 2>/dev/null | grep -o '"frameworkVersion":"[^"]*"' | cut -d'"' -f4)
    log OK "Flutter $flutter_version detectado"
}

check_dependencies() {
    log INFO "Verificando dependencias del proyecto..."

    cd "$SCRIPT_DIR"

    if [ ! -f "$PUBSPEC" ]; then
        log ERROR "No se encontró pubspec.yaml"
        exit 1
    fi

    # Verificar que las dependencias están actualizadas
    flutter pub get >> "$LOG_FILE" 2>&1

    if [ $? -eq 0 ]; then
        log OK "Dependencias verificadas"
    else
        log WARN "Problemas con algunas dependencias, revisar log"
    fi
}

validate_site_config() {
    local site_url=$1

    if [ -z "$site_url" ]; then
        log WARN "No se especificó URL del sitio, saltando validación remota"
        return 0
    fi

    log INFO "Validando configuración del sitio: $site_url"

    # Verificar endpoint de manifiesto
    local manifest_url="${site_url}/wp-json/flavor-app/v2/manifest"
    local response=$(curl -s -o /dev/null -w "%{http_code}" "$manifest_url" 2>/dev/null)

    if [ "$response" == "200" ]; then
        log OK "Endpoint de manifiesto accesible"

        # Obtener info del manifiesto
        local manifest=$(curl -s "$manifest_url" 2>/dev/null)
        local version=$(echo "$manifest" | grep -o '"version":"[^"]*"' | head -1 | cut -d'"' -f4)
        local modules_count=$(echo "$manifest" | grep -o '"total":[0-9]*' | head -1 | cut -d':' -f2)

        log INFO "  Versión de config: $version"
        log INFO "  Módulos activos: $modules_count"
    else
        log WARN "No se pudo acceder al manifiesto (HTTP $response)"
        log WARN "La app se construirá pero puede no estar sincronizada"
    fi

    # Verificar endpoint legacy también
    local discovery_url="${site_url}/wp-json/app-discovery/v1/info"
    local discovery_response=$(curl -s -o /dev/null -w "%{http_code}" "$discovery_url" 2>/dev/null)

    if [ "$discovery_response" == "200" ]; then
        log OK "Endpoint de discovery accesible"
    fi
}

#===============================================================================
# Funciones de versionado
#===============================================================================

get_current_version() {
    grep "^version:" "$PUBSPEC" | head -1 | awk '{print $2}'
}

bump_version() {
    local bump_type=${1:-patch}
    local current_version=$(get_current_version)

    # Separar versión y build number
    local version_part=$(echo "$current_version" | cut -d'+' -f1)
    local build_number=$(echo "$current_version" | cut -d'+' -f2)

    # Parsear major.minor.patch
    local major=$(echo "$version_part" | cut -d'.' -f1)
    local minor=$(echo "$version_part" | cut -d'.' -f2)
    local patch=$(echo "$version_part" | cut -d'.' -f3)

    # Incrementar
    case $bump_type in
        major)
            major=$((major + 1))
            minor=0
            patch=0
            ;;
        minor)
            minor=$((minor + 1))
            patch=0
            ;;
        patch|*)
            patch=$((patch + 1))
            ;;
    esac

    # Incrementar build number
    build_number=$((build_number + 1))

    local new_version="${major}.${minor}.${patch}+${build_number}"

    log INFO "Actualizando versión: $current_version -> $new_version"

    # Actualizar pubspec.yaml
    sed -i "s/^version: .*/version: $new_version/" "$PUBSPEC"

    echo "$new_version"
}

#===============================================================================
# Funciones de construcción
#===============================================================================

clean_build() {
    log INFO "Limpiando builds anteriores..."
    cd "$SCRIPT_DIR"
    flutter clean >> "$LOG_FILE" 2>&1
    log OK "Limpieza completada"
}

build_apk() {
    local flavor=$1
    local mode=$2
    local target=""

    case $flavor in
        client) target="lib/main_client.dart" ;;
        admin)  target="lib/main_admin.dart" ;;
        *)
            log ERROR "Flavor desconocido: $flavor"
            exit 1
            ;;
    esac

    log INFO "Construyendo APK ($flavor, $mode)..."
    log INFO "  Entry point: $target"

    cd "$SCRIPT_DIR"

    local build_cmd="flutter build apk --$mode --flavor $flavor -t $target"

    echo "Ejecutando: $build_cmd" >> "$LOG_FILE"

    # Ejecutar build con output en tiempo real y log
    $build_cmd 2>&1 | tee -a "$LOG_FILE"

    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        local apk_path="$BUILD_DIR/app/outputs/flutter-apk/app-$flavor-$mode.apk"

        if [ -f "$apk_path" ]; then
            local apk_size=$(du -h "$apk_path" | cut -f1)
            log OK "APK generado exitosamente"
            log INFO "  Ruta: $apk_path"
            log INFO "  Tamaño: $apk_size"

            # Copiar a directorio más accesible
            local output_name="flavor-app-$flavor-$mode-$(date +%Y%m%d).apk"
            cp "$apk_path" "$SCRIPT_DIR/$output_name"
            log INFO "  Copia en: $SCRIPT_DIR/$output_name"

            return 0
        else
            log ERROR "APK no encontrado en la ruta esperada"
            return 1
        fi
    else
        log ERROR "Error durante la construcción"
        return 1
    fi
}

build_aab() {
    local flavor=$1
    local target=""

    case $flavor in
        client) target="lib/main_client.dart" ;;
        admin)  target="lib/main_admin.dart" ;;
    esac

    log INFO "Construyendo App Bundle ($flavor)..."

    cd "$SCRIPT_DIR"

    flutter build appbundle --release --flavor $flavor -t $target 2>&1 | tee -a "$LOG_FILE"

    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        local aab_path="$BUILD_DIR/app/outputs/bundle/${flavor}Release/app-$flavor-release.aab"

        if [ -f "$aab_path" ]; then
            local aab_size=$(du -h "$aab_path" | cut -f1)
            log OK "App Bundle generado"
            log INFO "  Ruta: $aab_path"
            log INFO "  Tamaño: $aab_size"
        fi
    else
        log WARN "Error generando App Bundle"
    fi
}

install_apk() {
    local flavor=$1
    local mode=$2
    local apk_path="$BUILD_DIR/app/outputs/flutter-apk/app-$flavor-$mode.apk"

    if [ ! -f "$apk_path" ]; then
        log ERROR "APK no encontrado para instalar"
        return 1
    fi

    log INFO "Instalando APK en dispositivo..."

    # Verificar ADB
    if ! command -v adb &> /dev/null; then
        log ERROR "ADB no está instalado"
        return 1
    fi

    # Verificar dispositivo conectado
    local devices=$(adb devices | grep -v "List" | grep -v "^$" | wc -l)

    if [ "$devices" -eq 0 ]; then
        log ERROR "No hay dispositivos conectados"
        return 1
    fi

    adb install -r "$apk_path" 2>&1 | tee -a "$LOG_FILE"

    if [ ${PIPESTATUS[0]} -eq 0 ]; then
        log OK "APK instalado exitosamente"
    else
        log ERROR "Error instalando APK"
    fi
}

#===============================================================================
# Función principal
#===============================================================================

main() {
    # Valores por defecto
    FLAVOR=""
    MODE="debug"
    BUMP_VERSION=false
    VALIDATE_ONLY=false
    SITE_URL=""
    SKIP_VALIDATION=false
    DO_CLEAN=false
    DO_INSTALL=false
    BUILD_AAB=false

    # Parsear argumentos
    for arg in "$@"; do
        case $arg in
            client|admin)
                FLAVOR=$arg
                ;;
            --release)
                MODE="release"
                ;;
            --debug)
                MODE="debug"
                ;;
            --bump-version)
                BUMP_VERSION=true
                ;;
            --validate-only)
                VALIDATE_ONLY=true
                ;;
            --site-url=*)
                SITE_URL="${arg#*=}"
                ;;
            --skip-validation)
                SKIP_VALIDATION=true
                ;;
            --clean)
                DO_CLEAN=true
                ;;
            --install)
                DO_INSTALL=true
                ;;
            --aab)
                BUILD_AAB=true
                ;;
            --help|-h)
                show_usage
                exit 0
                ;;
            *)
                echo -e "${RED}Argumento desconocido: $arg${NC}"
                show_usage
                exit 1
                ;;
        esac
    done

    # Verificar flavor requerido
    if [ -z "$FLAVOR" ]; then
        echo -e "${RED}Error: Se requiere especificar un flavor (client|admin)${NC}"
        show_usage
        exit 1
    fi

    # Mostrar header
    show_header

    # Iniciar log
    log INFO "=== Inicio de build ==="
    log INFO "Flavor: $FLAVOR, Mode: $MODE"

    # Verificaciones
    check_flutter
    check_dependencies

    # Validar configuración del sitio (si se proporciona URL)
    if [ "$SKIP_VALIDATION" != true ]; then
        validate_site_config "$SITE_URL"
    fi

    # Solo validación
    if [ "$VALIDATE_ONLY" == true ]; then
        log OK "Validación completada"
        exit 0
    fi

    # Bump version si se solicita
    if [ "$BUMP_VERSION" == true ]; then
        NEW_VERSION=$(bump_version patch)
        log OK "Nueva versión: $NEW_VERSION"
    fi

    # Limpiar si se solicita
    if [ "$DO_CLEAN" == true ]; then
        clean_build
    fi

    # Construir APK
    if build_apk "$FLAVOR" "$MODE"; then
        # Construir AAB si se solicita
        if [ "$BUILD_AAB" == true ] && [ "$MODE" == "release" ]; then
            build_aab "$FLAVOR"
        fi

        # Instalar si se solicita
        if [ "$DO_INSTALL" == true ]; then
            install_apk "$FLAVOR" "$MODE"
        fi

        echo ""
        echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${GREEN}║${NC}                    ${GREEN}BUILD EXITOSO${NC}                          ${GREEN}║${NC}"
        echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
        echo ""

        log INFO "=== Build completado exitosamente ==="

    else
        echo ""
        echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${RED}║${NC}                    ${RED}BUILD FALLIDO${NC}                           ${RED}║${NC}"
        echo -e "${RED}║${NC}  Revisa el log: $LOG_FILE"
        echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
        echo ""

        log ERROR "=== Build fallido ==="
        exit 1
    fi
}

# Ejecutar
main "$@"
