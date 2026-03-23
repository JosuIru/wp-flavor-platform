#!/bin/bash
#===============================================================================
# Backup de Keystore para Flavor Apps
#
# Crea un backup cifrado del keystore para almacenamiento seguro.
#
# USO:
#   ./backup-keystore.sh
#   ./backup-keystore.sh --output /path/to/backup
#===============================================================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
KEYSTORE_DIR="$PROJECT_DIR/keystores"
KEYSTORE_FILE="$KEYSTORE_DIR/flavor-release.jks"
KEY_PROPERTIES="$PROJECT_DIR/android/key.properties"

# Parsear argumentos
OUTPUT_DIR="$HOME/flavor-keystore-backup"
while [[ $# -gt 0 ]]; do
    case $1 in
        --output)
            OUTPUT_DIR="$2"
            shift 2
            ;;
        *)
            shift
            ;;
    esac
done

echo ""
echo -e "${BLUE}Backup de Keystore - Flavor Apps${NC}"
echo "─────────────────────────────────"

# Verificar que existe el keystore
if [ ! -f "$KEYSTORE_FILE" ]; then
    echo -e "${RED}Error: No se encontró keystore en $KEYSTORE_FILE${NC}"
    echo "Ejecuta primero: ./generate-keystore.sh"
    exit 1
fi

# Crear directorio de backup
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$OUTPUT_DIR/backup_$TIMESTAMP"
mkdir -p "$BACKUP_DIR"

echo -e "${BLUE}Creando backup...${NC}"

# Copiar keystore
cp "$KEYSTORE_FILE" "$BACKUP_DIR/"
echo -e "${GREEN}✓${NC} Keystore copiado"

# Copiar key.properties si existe
if [ -f "$KEY_PROPERTIES" ]; then
    cp "$KEY_PROPERTIES" "$BACKUP_DIR/"
    echo -e "${GREEN}✓${NC} key.properties copiado"
fi

# Obtener información del keystore
KEYSTORE_INFO=$(keytool -list -v -keystore "$KEYSTORE_FILE" -storepass "$(grep storePassword "$KEY_PROPERTIES" 2>/dev/null | cut -d'=' -f2)" 2>/dev/null || echo "No se pudo obtener info")

# Crear archivo de información
cat > "$BACKUP_DIR/INFO.txt" << EOF
BACKUP DE KEYSTORE - FLAVOR APP
================================

Fecha de backup: $(date)
Keystore original: $KEYSTORE_FILE

INFORMACIÓN DEL KEYSTORE:
$KEYSTORE_INFO

INSTRUCCIONES DE RESTAURACIÓN:
1. Copia flavor-release.jks a mobile-apps/keystores/
2. Copia key.properties a mobile-apps/android/
3. Verifica con: keytool -list -keystore keystores/flavor-release.jks

IMPORTANTE:
- Guarda este backup en lugar seguro (offline preferiblemente)
- Si pierdes el keystore original, usa este backup
- Sin el keystore no podrás publicar actualizaciones en Play Store
EOF

echo -e "${GREEN}✓${NC} Archivo INFO.txt creado"

# Crear archivo ZIP cifrado (opcional)
if command -v zip &> /dev/null; then
    echo ""
    read -p "¿Crear archivo ZIP con contraseña? (s/N): " create_zip
    if [[ "$create_zip" =~ ^[Ss]$ ]]; then
        read -sp "Contraseña para el ZIP: " zip_password
        echo ""

        ZIP_FILE="$OUTPUT_DIR/flavor-keystore-backup_$TIMESTAMP.zip"
        cd "$BACKUP_DIR"
        zip -e -P "$zip_password" "$ZIP_FILE" * > /dev/null
        cd - > /dev/null

        echo -e "${GREEN}✓${NC} ZIP cifrado creado: $ZIP_FILE"

        # Preguntar si eliminar archivos sin cifrar
        read -p "¿Eliminar archivos sin cifrar? (s/N): " delete_plain
        if [[ "$delete_plain" =~ ^[Ss]$ ]]; then
            rm -rf "$BACKUP_DIR"
            echo -e "${GREEN}✓${NC} Archivos sin cifrar eliminados"
        fi
    fi
fi

echo ""
echo -e "${GREEN}Backup completado${NC}"
echo ""
echo -e "${BLUE}Ubicación:${NC} $BACKUP_DIR"
echo ""
echo -e "${YELLOW}IMPORTANTE: Guarda este backup en lugar seguro${NC}"
echo "  - USB externo"
echo "  - Almacenamiento offline"
echo "  - Gestor de contraseñas (1Password, Bitwarden)"
echo ""
