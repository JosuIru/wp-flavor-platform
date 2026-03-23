#!/bin/bash
#
# Script de verificación P0 #6: TABLAS BD
# Verifica que la implementación de install.php está completa
#
# Uso: bash tools/verify-p0-6-tablas.sh
#

# Colores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}=== VERIFICACIÓN P0 #6: TABLAS BD ===${NC}"
echo ""

# Contador de errores
ERRORES=0

# 1. Verificar archivos install.php creados
echo -e "${YELLOW}1. Verificando archivos install.php...${NC}"
if [ -f "includes/modules/clientes/install.php" ]; then
    SIZE=$(wc -l < includes/modules/clientes/install.php)
    echo -e "  ${GREEN}✓${NC} Clientes install.php (${SIZE} líneas)"
else
    echo -e "  ${RED}✗${NC} Clientes install.php FALTA"
    ERRORES=$((ERRORES+1))
fi

if [ -f "includes/modules/facturas/install.php" ]; then
    SIZE=$(wc -l < includes/modules/facturas/install.php)
    echo -e "  ${GREEN}✓${NC} Facturas install.php (${SIZE} líneas)"
else
    echo -e "  ${RED}✗${NC} Facturas install.php FALTA"
    ERRORES=$((ERRORES+1))
fi

if [ -f "includes/modules/socios/install.php" ]; then
    SIZE=$(wc -l < includes/modules/socios/install.php)
    echo -e "  ${GREEN}✓${NC} Socios install.php (${SIZE} líneas)"
else
    echo -e "  ${YELLOW}⚠${NC} Socios install.php (debería existir previamente)"
fi

if [ -f "includes/modules/eventos/install.php" ]; then
    SIZE=$(wc -l < includes/modules/eventos/install.php)
    echo -e "  ${GREEN}✓${NC} Eventos install.php (${SIZE} líneas)"
else
    echo -e "  ${YELLOW}⚠${NC} Eventos install.php (debería existir previamente)"
fi

echo ""

# 2. Verificar métodos maybe_create_tables()
echo -e "${YELLOW}2. Verificando métodos maybe_create_tables()...${NC}"

if grep -q "public function maybe_create_tables" includes/modules/clientes/class-clientes-module.php; then
    echo -e "  ${GREEN}✓${NC} Clientes::maybe_create_tables()"
else
    echo -e "  ${RED}✗${NC} Clientes::maybe_create_tables() FALTA"
    ERRORES=$((ERRORES+1))
fi

if grep -q "public function maybe_create_tables" includes/modules/facturas/class-facturas-module.php; then
    echo -e "  ${GREEN}✓${NC} Facturas::maybe_create_tables()"
else
    echo -e "  ${RED}✗${NC} Facturas::maybe_create_tables() FALTA"
    ERRORES=$((ERRORES+1))
fi

if grep -q "public function maybe_create_tables" includes/modules/socios/class-socios-module.php; then
    echo -e "  ${GREEN}✓${NC} Socios::maybe_create_tables()"
else
    echo -e "  ${RED}✗${NC} Socios::maybe_create_tables() FALTA"
    ERRORES=$((ERRORES+1))
fi

if grep -q "public function maybe_create_tables" includes/modules/eventos/class-eventos-module.php; then
    echo -e "  ${GREEN}✓${NC} Eventos::maybe_create_tables()"
else
    echo -e "  ${RED}✗${NC} Eventos::maybe_create_tables() FALTA"
    ERRORES=$((ERRORES+1))
fi

echo ""

# 3. Verificar llamadas en init()
echo -e "${YELLOW}3. Verificando llamadas a maybe_create_tables()...${NC}"

# Clientes
if grep -A5 "public function init" includes/modules/clientes/class-clientes-module.php | grep -q "maybe_create_tables"; then
    echo -e "  ${GREEN}✓${NC} Clientes::init() llama método"
else
    echo -e "  ${RED}✗${NC} Clientes::init() NO llama método"
    ERRORES=$((ERRORES+1))
fi

# Facturas
if grep -A10 "public function init" includes/modules/facturas/class-facturas-module.php | grep -q "maybe_create_tables"; then
    echo -e "  ${GREEN}✓${NC} Facturas::init() llama método"
else
    echo -e "  ${RED}✗${NC} Facturas::init() NO llama método"
    ERRORES=$((ERRORES+1))
fi

# Socios
if grep -A10 "public function init" includes/modules/socios/class-socios-module.php | grep -q "maybe_create_tables"; then
    echo -e "  ${GREEN}✓${NC} Socios::init() llama método"
else
    echo -e "  ${YELLOW}⚠${NC} Socios::init() podría no llamar método"
fi

# Eventos - Buscar en todo el archivo porque init() puede estar más abajo
if grep "maybe_create_tables" includes/modules/eventos/class-eventos-module.php | grep -q "add_action"; then
    echo -e "  ${GREEN}✓${NC} Eventos registra llamada a método"
else
    echo -e "  ${YELLOW}⚠${NC} Eventos podría no llamar método"
fi

echo ""

# 4. Verificar Database Setup modificado
echo -e "${YELLOW}4. Verificando Database Setup...${NC}"

if grep -q "modules_con_install" includes/bootstrap/class-database-setup.php; then
    echo -e "  ${GREEN}✓${NC} Database Setup actualizado (usa array modules_con_install)"
else
    echo -e "  ${RED}✗${NC} Database Setup sin modificar"
    ERRORES=$((ERRORES+1))
fi

if grep -q "'clientes'" includes/bootstrap/class-database-setup.php; then
    echo -e "  ${GREEN}✓${NC} Clientes en lista de módulos"
else
    echo -e "  ${RED}✗${NC} Clientes NO en lista"
    ERRORES=$((ERRORES+1))
fi

if grep -q "'facturas'" includes/bootstrap/class-database-setup.php; then
    echo -e "  ${GREEN}✓${NC} Facturas en lista de módulos"
else
    echo -e "  ${RED}✗${NC} Facturas NO en lista"
    ERRORES=$((ERRORES+1))
fi

echo ""

# 5. Verificar funciones de creación
echo -e "${YELLOW}5. Verificando funciones de creación en install.php...${NC}"

if grep -q "function flavor_clientes_crear_tablas" includes/modules/clientes/install.php; then
    echo -e "  ${GREEN}✓${NC} flavor_clientes_crear_tablas() definida"
else
    echo -e "  ${RED}✗${NC} flavor_clientes_crear_tablas() NO definida"
    ERRORES=$((ERRORES+1))
fi

if grep -q "function flavor_facturas_crear_tablas" includes/modules/facturas/install.php; then
    echo -e "  ${GREEN}✓${NC} flavor_facturas_crear_tablas() definida"
else
    echo -e "  ${RED}✗${NC} flavor_facturas_crear_tablas() NO definida"
    ERRORES=$((ERRORES+1))
fi

echo ""

# 6. Verificar CREATE TABLE statements
echo -e "${YELLOW}6. Verificando CREATE TABLE statements...${NC}"

CLIENTES_TABLES=$(grep -c "CREATE TABLE" includes/modules/clientes/install.php)
echo -e "  ${GREEN}✓${NC} Clientes crea ${CLIENTES_TABLES} tabla(s)"

FACTURAS_TABLES=$(grep -c "CREATE TABLE" includes/modules/facturas/install.php)
echo -e "  ${GREEN}✓${NC} Facturas crea ${FACTURAS_TABLES} tabla(s)"

if [ "$FACTURAS_TABLES" -ge 4 ]; then
    echo -e "  ${GREEN}✓${NC} Facturas tiene tablas suficientes (facturas, líneas, pagos, series)"
else
    echo -e "  ${RED}✗${NC} Facturas debería tener al menos 4 tablas"
    ERRORES=$((ERRORES+1))
fi

echo ""

# 7. Verificar campos clave en Clientes
echo -e "${YELLOW}7. Verificando campos clave en Clientes...${NC}"

CAMPOS_CLAVE=("numero_cliente" "usuario_id" "tipo_cliente" "estado" "metadata")
CAMPOS_OK=0

for campo in "${CAMPOS_CLAVE[@]}"; do
    if grep -q "$campo" includes/modules/clientes/install.php; then
        CAMPOS_OK=$((CAMPOS_OK+1))
    fi
done

if [ "$CAMPOS_OK" -eq "${#CAMPOS_CLAVE[@]}" ]; then
    echo -e "  ${GREEN}✓${NC} Todos los campos clave presentes (${CAMPOS_OK}/${#CAMPOS_CLAVE[@]})"
else
    echo -e "  ${YELLOW}⚠${NC} Algunos campos clave faltan (${CAMPOS_OK}/${#CAMPOS_CLAVE[@]})"
fi

echo ""

# 8. Verificar datos por defecto en Facturas
echo -e "${YELLOW}8. Verificando datos por defecto en Facturas...${NC}"

if grep -q "flavor_facturas_insertar_datos_default" includes/modules/facturas/install.php; then
    echo -e "  ${GREEN}✓${NC} Función de inserción de datos default existe"
else
    echo -e "  ${YELLOW}⚠${NC} No se encontró función de datos default"
fi

if grep -q "serie.*=>.*'A'" includes/modules/facturas/install.php; then
    echo -e "  ${GREEN}✓${NC} Serie por defecto 'A' configurada"
fi

if grep -q "IVA" includes/modules/facturas/install.php; then
    echo -e "  ${GREEN}✓${NC} Impuestos IVA configurados"
fi

echo ""

# Resumen final
echo -e "${YELLOW}=== RESUMEN ===${NC}"

if [ "$ERRORES" -eq 0 ]; then
    echo -e "${GREEN}✓ VERIFICACIÓN EXITOSA${NC} - Todos los componentes implementados correctamente"
    echo ""
    echo "Próximos pasos:"
    echo "1. Reactivar plugin para ejecutar install hooks"
    echo "2. Verificar creación de tablas en BD"
    echo "3. Test de activación de módulos Clientes y Facturas"
    exit 0
else
    echo -e "${RED}✗ VERIFICACIÓN FALLIDA${NC} - Se encontraron ${ERRORES} errores"
    echo ""
    echo "Revisa los errores marcados arriba y corrige antes de proceder."
    exit 1
fi
