#!/bin/bash

# Script para generar frontend controllers masivamente
# Uso: ./generar-frontends.sh

PLUGIN_PATH="/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia"
TEMPLATE_PATH="$PLUGIN_PATH/tools/templates/frontend-controller-template.php"

# Módulos a procesar (21 AMARILLO)
declare -A MODULOS=(
    ["advertising"]="Advertising"
    ["agregador-contenido"]="Agregador_Contenido"
    ["bares"]="Bares"
    ["chat-estados"]="Chat_Estados"
    ["clientes"]="Clientes"
    ["contabilidad"]="Contabilidad"
    ["crowdfunding"]="Crowdfunding"
    ["dex-solana"]="Dex_Solana"
    ["economia-suficiencia"]="Economia_Suficiencia"
    ["email-marketing"]="Email_Marketing"
    ["empresarial"]="Empresarial"
    ["encuestas"]="Encuestas"
    ["energia-comunitaria"]="Energia_Comunitaria"
    ["facturas"]="Facturas"
    ["huella-ecologica"]="Huella_Ecologica"
    ["kulturaka"]="Kulturaka"
    ["red-social"]="Red_Social"
    ["sello-conciencia"]="Sello_Conciencia"
    ["themacle"]="Themacle"
    ["trading-ia"]="Trading_IA"
    ["woocommerce"]="Woocommerce"
)

# Nombres legibles para cada módulo
declare -A NOMBRES=(
    ["advertising"]="Publicidad"
    ["agregador-contenido"]="Agregador de Contenido"
    ["bares"]="Bares"
    ["chat-estados"]="Chat Estados"
    ["clientes"]="Clientes"
    ["contabilidad"]="Contabilidad"
    ["crowdfunding"]="Crowdfunding"
    ["dex-solana"]="DEX Solana"
    ["economia-suficiencia"]="Economía de Suficiencia"
    ["email-marketing"]="Email Marketing"
    ["empresarial"]="Empresarial"
    ["encuestas"]="Encuestas"
    ["energia-comunitaria"]="Energía Comunitaria"
    ["facturas"]="Facturas"
    ["huella-ecologica"]="Huella Ecológica"
    ["kulturaka"]="Kulturaka"
    ["red-social"]="Red Social"
    ["sello-conciencia"]="Sello de Conciencia"
    ["themacle"]="Themacle"
    ["trading-ia"]="Trading IA"
    ["woocommerce"]="WooCommerce"
)

# Función para convertir slug a CamelCase para JS
to_camel_case() {
    local slug=$1
    # Eliminar guiones y capitalizar primera letra de cada palabra
    echo "$slug" | sed 's/-/ /g' | awk '{for(i=1;i<=NF;i++){$i=toupper(substr($i,1,1)) substr($i,2)}}1' | sed 's/ //g'
}

# Verificar que existe la plantilla
if [ ! -f "$TEMPLATE_PATH" ]; then
    echo "❌ ERROR: No se encuentra la plantilla en $TEMPLATE_PATH"
    exit 1
fi

# Contador
total=${#MODULOS[@]}
procesados=0
creados=0
saltados=0

echo "========================================="
echo " GENERADOR DE FRONTEND CONTROLLERS"
echo "========================================="
echo ""
echo "Total de módulos a procesar: $total"
echo "Plantilla: $TEMPLATE_PATH"
echo ""

for slug in "${!MODULOS[@]}"; do
    class_name="${MODULOS[$slug]}"
    nombre="${NOMBRES[$slug]}"
    camel_case=$(to_camel_case "$slug")

    procesados=$((procesados + 1))

    echo "[$procesados/$total] Procesando: $slug"

    # Crear directorio frontend si no existe
    frontend_dir="$PLUGIN_PATH/includes/modules/$slug/frontend"
    if [ ! -d "$frontend_dir" ]; then
        mkdir -p "$frontend_dir"
        echo "  ✓ Directorio frontend creado"
    fi

    # Nombre del archivo
    output_file="$frontend_dir/class-${slug}-frontend-controller.php"

    # Verificar si ya existe
    if [ -f "$output_file" ]; then
        echo "  ⚠ Ya existe, saltando..."
        saltados=$((saltados + 1))
        continue
    fi

    # Generar archivo desde plantilla
    sed -e "s/{{MODULE_NAME}}/$nombre/g" \
        -e "s/{{MODULE_CLASS}}/$class_name/g" \
        -e "s/{{MODULE_SLUG}}/$slug/g" \
        -e "s/{{MODULE_CAMEL}}/$camel_case/g" \
        "$TEMPLATE_PATH" > "$output_file"

    if [ $? -eq 0 ]; then
        creados=$((creados + 1))
        echo "  ✅ Frontend controller creado"
    else
        echo "  ❌ Error al crear archivo"
    fi
done

echo ""
echo "========================================="
echo " RESUMEN"
echo "========================================="
echo "Procesados: $procesados/$total"
echo "Creados: $creados"
echo "Ya existían: $saltados"
echo ""

if [ $creados -gt 0 ]; then
    echo "✅ Proceso completado exitosamente"
    echo ""
    echo "SIGUIENTE PASO:"
    echo "Añade las inicializaciones en:"
    echo "  includes/bootstrap/class-bootstrap-dependencies.php"
    echo ""
    echo "Luego ejecuta:"
    echo "  wp plugin deactivate flavor-chat-ia"
    echo "  wp plugin activate flavor-chat-ia"
else
    echo "ℹ️ No se crearon archivos nuevos (todos ya existían)"
fi
