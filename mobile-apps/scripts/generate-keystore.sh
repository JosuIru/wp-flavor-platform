#!/bin/bash
#===============================================================================
# Generador de Keystore para Flavor Apps
#
# Este script genera un keystore para firmar APKs de producción.
# El keystore es necesario para publicar en Google Play Store.
#
# USO:
#   ./generate-keystore.sh
#   ./generate-keystore.sh --non-interactive
#
# IMPORTANTE:
#   - Guarda el keystore y las contraseñas en un lugar seguro
#   - Si pierdes el keystore, no podrás actualizar la app en Play Store
#   - Nunca commits el keystore al repositorio
#===============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
KEYSTORE_DIR="$PROJECT_DIR/keystores"
KEYSTORE_FILE="$KEYSTORE_DIR/flavor-release.jks"
KEY_ALIAS="flavor-release-key"
VALIDITY_DAYS=10000  # ~27 años

# Verificar si es modo no interactivo
NON_INTERACTIVE=false
if [[ "$1" == "--non-interactive" ]]; then
    NON_INTERACTIVE=true
fi

show_header() {
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}        ${GREEN}Generador de Keystore - Flavor Apps${NC}               ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

check_keytool() {
    if ! command -v keytool &> /dev/null; then
        echo -e "${RED}Error: keytool no encontrado${NC}"
        echo ""
        echo "keytool es parte del JDK. Instálalo con:"
        echo "  Ubuntu/Debian: sudo apt install default-jdk"
        echo "  macOS: brew install openjdk"
        echo "  Windows: Instala JDK desde https://adoptium.net/"
        exit 1
    fi
    echo -e "${GREEN}✓${NC} keytool encontrado"
}

check_existing_keystore() {
    if [ -f "$KEYSTORE_FILE" ]; then
        echo ""
        echo -e "${YELLOW}⚠ Ya existe un keystore en:${NC}"
        echo "  $KEYSTORE_FILE"
        echo ""

        if [ "$NON_INTERACTIVE" = true ]; then
            echo -e "${RED}Abortando (modo no interactivo)${NC}"
            exit 1
        fi

        read -p "¿Deseas sobrescribirlo? (s/N): " confirm
        if [[ ! "$confirm" =~ ^[Ss]$ ]]; then
            echo "Operación cancelada."
            exit 0
        fi

        # Backup del existente
        backup_file="$KEYSTORE_DIR/flavor-release.jks.backup.$(date +%Y%m%d_%H%M%S)"
        mv "$KEYSTORE_FILE" "$backup_file"
        echo -e "${BLUE}Backup creado: $backup_file${NC}"
    fi
}

get_keystore_info() {
    echo ""
    echo -e "${BLUE}Información del Keystore${NC}"
    echo "─────────────────────────"

    if [ "$NON_INTERACTIVE" = true ]; then
        # Generar contraseñas aleatorias en modo no interactivo
        STORE_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 20)
        KEY_PASSWORD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 20)

        CN="Flavor App"
        OU="Mobile Development"
        O="Flavor Platform"
        L="Bilbao"
        ST="Bizkaia"
        C="ES"

        echo "Usando valores por defecto (modo no interactivo)"
    else
        # Solicitar información interactivamente
        echo ""
        echo -e "${YELLOW}Contraseñas (mínimo 6 caracteres):${NC}"

        while true; do
            read -sp "  Contraseña del keystore: " STORE_PASSWORD
            echo ""
            if [ ${#STORE_PASSWORD} -lt 6 ]; then
                echo -e "${RED}  La contraseña debe tener al menos 6 caracteres${NC}"
                continue
            fi

            read -sp "  Confirmar contraseña: " STORE_PASSWORD_CONFIRM
            echo ""
            if [ "$STORE_PASSWORD" != "$STORE_PASSWORD_CONFIRM" ]; then
                echo -e "${RED}  Las contraseñas no coinciden${NC}"
                continue
            fi
            break
        done

        while true; do
            read -sp "  Contraseña de la clave: " KEY_PASSWORD
            echo ""
            if [ ${#KEY_PASSWORD} -lt 6 ]; then
                echo -e "${RED}  La contraseña debe tener al menos 6 caracteres${NC}"
                continue
            fi

            read -sp "  Confirmar contraseña: " KEY_PASSWORD_CONFIRM
            echo ""
            if [ "$KEY_PASSWORD" != "$KEY_PASSWORD_CONFIRM" ]; then
                echo -e "${RED}  Las contraseñas no coinciden${NC}"
                continue
            fi
            break
        done

        echo ""
        echo -e "${YELLOW}Información del certificado:${NC}"
        read -p "  Nombre de la app [Flavor App]: " CN
        CN=${CN:-"Flavor App"}

        read -p "  Unidad organizativa [Mobile Development]: " OU
        OU=${OU:-"Mobile Development"}

        read -p "  Organización [Flavor Platform]: " O
        O=${O:-"Flavor Platform"}

        read -p "  Ciudad [Bilbao]: " L
        L=${L:-"Bilbao"}

        read -p "  Provincia/Estado [Bizkaia]: " ST
        ST=${ST:-"Bizkaia"}

        read -p "  Código de país (2 letras) [ES]: " C
        C=${C:-"ES"}
    fi

    DNAME="CN=$CN, OU=$OU, O=$O, L=$L, ST=$ST, C=$C"
}

generate_keystore() {
    echo ""
    echo -e "${BLUE}Generando keystore...${NC}"

    # Crear directorio
    mkdir -p "$KEYSTORE_DIR"

    # Generar keystore
    keytool -genkeypair \
        -v \
        -keystore "$KEYSTORE_FILE" \
        -alias "$KEY_ALIAS" \
        -keyalg RSA \
        -keysize 2048 \
        -validity $VALIDITY_DAYS \
        -storepass "$STORE_PASSWORD" \
        -keypass "$KEY_PASSWORD" \
        -dname "$DNAME" \
        2>&1

    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ Keystore generado exitosamente${NC}"
    else
        echo -e "${RED}✗ Error generando keystore${NC}"
        exit 1
    fi
}

create_key_properties() {
    KEY_PROPERTIES="$PROJECT_DIR/android/key.properties"

    cat > "$KEY_PROPERTIES" << EOF
# Generado automáticamente por generate-keystore.sh
# Fecha: $(date)
#
# IMPORTANTE: No commits este archivo al repositorio

storePassword=$STORE_PASSWORD
keyPassword=$KEY_PASSWORD
keyAlias=$KEY_ALIAS
storeFile=../../keystores/flavor-release.jks
EOF

    echo -e "${GREEN}✓ key.properties creado${NC}"
}

update_gitignore() {
    GITIGNORE="$PROJECT_DIR/.gitignore"

    # Añadir entradas si no existen
    entries=(
        "# Keystores y configuración de firma"
        "keystores/"
        "*.jks"
        "*.keystore"
        "android/key.properties"
        "key.properties"
    )

    for entry in "${entries[@]}"; do
        if ! grep -qF "$entry" "$GITIGNORE" 2>/dev/null; then
            echo "$entry" >> "$GITIGNORE"
        fi
    done

    echo -e "${GREEN}✓ .gitignore actualizado${NC}"
}

show_summary() {
    echo ""
    echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}                    ${GREEN}RESUMEN${NC}                                 ${CYAN}║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${BLUE}Keystore:${NC}      $KEYSTORE_FILE"
    echo -e "  ${BLUE}Alias:${NC}         $KEY_ALIAS"
    echo -e "  ${BLUE}Validez:${NC}       $VALIDITY_DAYS días (~27 años)"
    echo -e "  ${BLUE}key.properties:${NC} $PROJECT_DIR/android/key.properties"
    echo ""

    if [ "$NON_INTERACTIVE" = true ]; then
        echo -e "${YELLOW}╔════════════════════════════════════════════════════════════╗${NC}"
        echo -e "${YELLOW}║  CONTRASEÑAS GENERADAS (GUÁRDALAS EN LUGAR SEGURO):        ║${NC}"
        echo -e "${YELLOW}╠════════════════════════════════════════════════════════════╣${NC}"
        echo -e "${YELLOW}║${NC}  Store Password: ${GREEN}$STORE_PASSWORD${NC}"
        echo -e "${YELLOW}║${NC}  Key Password:   ${GREEN}$KEY_PASSWORD${NC}"
        echo -e "${YELLOW}╚════════════════════════════════════════════════════════════╝${NC}"
    fi

    echo ""
    echo -e "${GREEN}✓ Configuración completada${NC}"
    echo ""
    echo -e "${BLUE}Próximos pasos:${NC}"
    echo "  1. Guarda el keystore y contraseñas en lugar seguro"
    echo "  2. Haz backup del keystore (si lo pierdes, no podrás actualizar la app)"
    echo "  3. Ejecuta: ./build_app_v2.sh client --release"
    echo ""
}

# Main
show_header
check_keytool
check_existing_keystore
get_keystore_info
generate_keystore
create_key_properties
update_gitignore
show_summary
