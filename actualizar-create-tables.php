<?php
/**
 * Script para actualizar create_tables() en módulos para que usen get_table_schema()
 */

$modulos_a_actualizar = [
    'avisos-municipales',
    'tramites',
    'carpooling'
];

$nuevo_metodo = '    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $esquemas = $this->get_table_schema();

        if (empty($esquemas)) {
            return;
        }

        require_once ABSPATH . \'wp-admin/includes/upgrade.php\';

        foreach ($esquemas as $tabla => $sql) {
            dbDelta($sql);
        }
    }';

foreach ($modulos_a_actualizar as $modulo) {
    $archivo = __DIR__ . "/includes/modules/{$modulo}/class-{$modulo}-module.php";

    if (!file_exists($archivo)) {
        echo "✗ No encontrado: {$archivo}\n";
        continue;
    }

    $contenido = file_get_contents($archivo);

    // Buscar el método create_tables() con regex
    $patron = '/private function create_tables\(\) \{.*?^\    \}/ms';

    if (preg_match($patron, $contenido)) {
        $contenido_nuevo = preg_replace($patron, $nuevo_metodo, $contenido);

        if ($contenido !== $contenido_nuevo) {
            file_put_contents($archivo, $contenido_nuevo);
            echo "✓ Actualizado: {$modulo}\n";
        } else {
            echo "⚠ Sin cambios: {$modulo}\n";
        }
    } else {
        echo "✗ No se encontró create_tables() en: {$modulo}\n";
    }
}

// Añadir maybe_create_tables() y create_tables() a trading-ia
$archivo_trading = __DIR__ . "/includes/modules/trading-ia/class-trading-ia-module.php";
if (file_exists($archivo_trading)) {
    $contenido_trading = file_get_contents($archivo_trading);

    // Buscar el método init() y añadir el hook
    if (strpos($contenido_trading, "add_action('init', [\$this, 'maybe_create_tables']);") === false) {
        // Añadir después de public function init() {
        $contenido_trading = preg_replace(
            '/(public function init\(\) \{)/',
            "$1\n        add_action('init', [\$this, 'maybe_create_tables']);",
            $contenido_trading,
            1
        );
    }

    // Añadir los métodos al final antes del último }
    if (strpos($contenido_trading, 'public function maybe_create_tables()') === false) {
        $metodos_nuevos = '
    /**
     * Verifica y crea tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_trades = $wpdb->prefix . \'flavor_trading_ia_trades\';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_trades)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias
     */
    private function create_tables() {
        $esquemas = $this->get_table_schema();

        if (empty($esquemas)) {
            return;
        }

        require_once ABSPATH . \'wp-admin/includes/upgrade.php\';

        foreach ($esquemas as $tabla => $sql) {
            dbDelta($sql);
        }
    }
';

        // Insertar antes del último }
        $pos_ultimo_cierre = strrpos($contenido_trading, '}');
        $contenido_trading = substr_replace($contenido_trading, $metodos_nuevos . "\n}", $pos_ultimo_cierre, 1);
    }

    file_put_contents($archivo_trading, $contenido_trading);
    echo "✓ Añadidos métodos a trading-ia\n";
}

echo "\n✓ Proceso completado\n";
echo "Ahora ejecuta: recrear-tablas-faltantes.php\n";
