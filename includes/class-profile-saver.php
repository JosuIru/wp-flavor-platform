<?php
/**
 * Clase helper para guardar app_profile de forma confiable
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Profile_Saver {

    /**
     * Guarda el app_profile en la configuración
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array Resultado con debug info
     */
    public static function guardar_perfil($plantilla_id) {
        global $wpdb;

        // Leer directamente de la BD (bypass cache)
        $option_value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'flavor_chat_ia_settings'
            )
        );

        $configuracion = maybe_unserialize($option_value);
        if (!is_array($configuracion)) {
            $configuracion = [];
        }

        $valor_previo = $configuracion['app_profile'] ?? 'NO_EXISTE';

        // Modificar app_profile
        $configuracion['app_profile'] = $plantilla_id;

        // Guardar directamente en BD (bypass WordPress hooks)
        $serializado = maybe_serialize($configuracion);

        $resultado = $wpdb->update(
            $wpdb->options,
            ['option_value' => $serializado],
            ['option_name' => 'flavor_chat_ia_settings'],
            ['%s'],
            ['%s']
        );

        // Limpiar todos los caches posibles
        wp_cache_delete('flavor_chat_ia_settings', 'options');
        wp_cache_delete('alloptions', 'options');
        wp_cache_flush();

        // Verificar leyendo directamente de BD
        $verificacion_value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s",
                'flavor_chat_ia_settings'
            )
        );
        $verificacion = maybe_unserialize($verificacion_value);
        $valor_guardado = $verificacion['app_profile'] ?? 'NO_EXISTE';

        return [
            'valor_previo' => $valor_previo,
            'valor_intentado' => $plantilla_id,
            'wpdb_result' => $resultado,
            'valor_guardado' => $valor_guardado,
            'exito' => ($valor_guardado === $plantilla_id),
        ];
    }
}
