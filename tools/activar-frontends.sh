#!/bin/bash

# Script para añadir carga de frontend controllers en módulos AMARILLO
# Modifica las clases de módulo para que carguen su frontend controller

PLUGIN_PATH="/home/josu/Local Sites/sitio-prueba/app/public/wp-content/plugins/flavor-chat-ia"

# Módulos a procesar (21 AMARILLO)
MODULOS=(
    "advertising"
    "agregador-contenido"
    "bares"
    "chat-estados"
    "clientes"
    "contabilidad"
    "crowdfunding"
    "dex-solana"
    "economia-suficiencia"
    "email-marketing"
    "empresarial"
    "encuestas"
    "energia-comunitaria"
    "facturas"
    "huella-ecologica"
    "kulturaka"
    "red-social"
    "sello-conciencia"
    "themacle"
    "trading-ia"
    "woocommerce"
)

# Código del método a añadir
read -r -d '' METODO_FRONTEND << 'EOF'

    /**
     * Cargar frontend controller
     */
    private function cargar_frontend_controller() {
        $archivo_controller = dirname(__FILE__) . '/frontend/class-MODULE_SLUG-frontend-controller.php';
        if (file_exists($archivo_controller)) {
            require_once $archivo_controller;
            Flavor_MODULE_CLASS_Frontend_Controller::get_instance();
        }
    }
EOF

procesados=0
modificados=0
saltados=0

echo "========================================="
echo " ACTIVADOR DE FRONTEND CONTROLLERS"
echo "========================================="
echo ""
echo "Total módulos: ${#MODULOS[@]}"
echo ""

for slug in "${MODULOS[@]}"; do
    procesados=$((procesados + 1))

    # Convertir slug a nombre de clase
    class_name=$(echo "$slug" | sed 's/-/_/g' | awk -F_ '{for(i=1;i<=NF;i++){$i=toupper(substr($i,1,1)) substr($i,2)}}1' | sed 's/ /_/g')

    # Archivo de clase del módulo
    module_file="$PLUGIN_PATH/includes/modules/$slug/class-$slug-module.php"

    echo "[$procesados/${#MODULOS[@]}] Procesando: $slug"

    if [ ! -f "$module_file" ]; then
        echo "  ⚠ Archivo de módulo no existe: $module_file"
        saltados=$((saltados + 1))
        continue
    fi

    # Verificar si ya tiene el método
    if grep -q "cargar_frontend_controller" "$module_file"; then
        echo "  ⚠ Ya tiene cargar_frontend_controller(), saltando..."
        saltados=$((saltados + 1))
        continue
    fi

    # Preparar el método con los valores correctos
    metodo_personalizado=$(echo "$METODO_FRONTEND" | sed "s/MODULE_SLUG/$slug/g" | sed "s/MODULE_CLASS/$class_name/g")

    # Buscar la última llave de cierre de la clase
    # Insertar el método antes de la última línea }

    # Crear backup
    cp "$module_file" "$module_file.bak"

    # Insertar el método antes del último }
    # Usando sed para insertar antes de la última línea que contiene solo }
    awk -v metodo="$metodo_personalizado" '
        /^}$/ && !found {
            print metodo
            found=1
        }
        { print }
    ' "$module_file" > "$module_file.tmp"

    mv "$module_file.tmp" "$module_file"

    # Ahora añadir la llamada al método en el constructor
    # Buscar el constructor y añadir la llamada
    if grep -q "parent::__construct" "$module_file"; then
        # Añadir después de parent::__construct();
        sed -i '/parent::__construct();/a\        $this->cargar_frontend_controller();' "$module_file"
        echo "  ✅ Método añadido y llamado desde constructor"
        modificados=$((modificados + 1))
    else
        echo "  ⚠ No se encontró parent::__construct(), método añadido pero sin llamar"
        modificados=$((modificados + 1))
    fi
done

echo ""
echo "========================================="
echo " RESUMEN"
echo "========================================="
echo "Procesados: $procesados"
echo "Modificados: $modificados"
echo "Saltados: $saltados"
echo ""

if [ $modificados -gt 0 ]; then
    echo "✅ Proceso completado"
    echo ""
    echo "Backups creados: *.bak"
    echo ""
    echo "SIGUIENTE PASO:"
    echo "  wp plugin deactivate flavor-chat-ia"
    echo "  wp plugin activate flavor-chat-ia"
else
    echo "ℹ️ No se realizaron modificaciones"
fi
