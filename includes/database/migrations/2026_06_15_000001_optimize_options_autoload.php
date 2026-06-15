<?php
/**
 * Migration: Optimizar autoload de opciones pesadas
 *
 * Marca como autoload='no' un conjunto acotado de opciones que NO se necesitan
 * en cada request (logs, colas, historiales, timestamps de sync, flags de
 * migración). Estas opciones inflaban el array `alloptions` que WordPress
 * carga en TODAS las peticiones, penalizando el TTFB de todo el sitio.
 *
 * Solo se incluyen opciones de lectura bajo demanda (paneles admin, cron,
 * webhooks); NO se tocan opciones de configuración leídas en cada request.
 *
 * @package FlavorPlatform
 * @subpackage Database\Migrations
 * @since 3.5.14
 */

if (!defined('ABSPATH')) {
    exit;
}

class Migration_2026_06_15_000001_Optimize_Options_Autoload extends Flavor_Migration_Base {

    protected $migration_name = 'optimize_options_autoload';
    protected $description = 'Marca opciones pesadas no-críticas como autoload=no para aligerar alloptions';

    /**
     * Opciones que deben dejar de autocargarse (leídas solo bajo demanda).
     *
     * @var string[]
     */
    private $opciones_diferidas = [
        'flavor_app_last_sync',
        'flavor_app_synced_devices',
        'flavor_app_webhook_history',
        'flavor_business_last_sync',
        'flavor_demo_data_log',
        'flavor_network_federation_logs',
        'flavor_network_last_sync',
        'flavor_push_history',
        'flavor_qr_codes_log',
        'flavor_suggestion_log',
        'flavor_vb_migration_pending',
        'flavor_webhook_logs',
        'flavor_webhook_queue',
    ];

    /**
     * Ejecuta la migration: pone autoload='no' a las opciones diferidas.
     *
     * @return bool
     */
    public function up() {
        return $this->fijar_autoload('no');
    }

    /**
     * Revierte: vuelve a autoload='yes'.
     *
     * @return bool
     */
    public function down() {
        return $this->fijar_autoload('yes');
    }

    /**
     * Aplica el valor de autoload a las opciones existentes (cross-version,
     * determinista). Idempotente: solo afecta a las filas que existen.
     *
     * @param string $valor 'no' o 'yes'.
     * @return bool
     */
    private function fijar_autoload($valor) {
        $tabla_opciones = $this->wpdb->options;
        $afectadas = 0;

        foreach ($this->opciones_diferidas as $nombre_opcion) {
            $resultado = $this->wpdb->update(
                $tabla_opciones,
                ['autoload' => $valor],
                ['option_name' => $nombre_opcion],
                ['%s'],
                ['%s']
            );
            if ($resultado === false) {
                $this->log("Error al fijar autoload={$valor} en {$nombre_opcion}", 'error');
                return false;
            }
            $afectadas += (int) $resultado;
        }

        // Invalidar el cache de alloptions para que el cambio surta efecto.
        wp_cache_delete('alloptions', 'options');

        $this->log("autoload={$valor} aplicado a {$afectadas} opciones diferidas", 'info');
        return true;
    }
}
