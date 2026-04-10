<?php
/**
 * Uninstall Flavor Platform
 *
 * Limpia todos los datos del plugin cuando se desinstala desde WordPress.
 * Solo se ejecuta si el usuario ha habilitado la opción de limpieza.
 *
 * @package Flavor_Platform
 * @since 3.3.0
 * @since 3.5.1 FIX: SQL injection, transacciones, paginación
 */

// Si no se llama desde WordPress, salir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Verificar si el usuario quiere limpiar los datos
$settings = get_option('flavor_chat_ia_settings', []);
$limpiar_al_desinstalar = isset($settings['limpiar_al_desinstalar']) && $settings['limpiar_al_desinstalar'];

if (!$limpiar_al_desinstalar) {
    // El usuario no quiere limpiar datos, salir
    return;
}

global $wpdb;

// Iniciar transacción para garantizar consistencia
$wpdb->query('START TRANSACTION');

try {
    /**
     * 1. ELIMINAR TABLAS PERSONALIZADAS
     * FIX: Usar esc_like y prepare para evitar SQL injection
     */
    $table_pattern = $wpdb->esc_like($wpdb->prefix . 'flavor_') . '%';
    $tablas_flavor = $wpdb->get_results(
        $wpdb->prepare("SHOW TABLES LIKE %s", $table_pattern),
        ARRAY_N
    );

    foreach ($tablas_flavor as $tabla) {
        // Sanitizar nombre de tabla antes de usarlo
        $tabla_nombre = esc_sql($tabla[0]);
        $wpdb->query("DROP TABLE IF EXISTS `{$tabla_nombre}`");
    }

    /**
     * 2. ELIMINAR OPTIONS
     * FIX: Usar prepare con esc_like
     */
    $option_pattern_1 = $wpdb->esc_like('flavor_') . '%';
    $option_pattern_2 = $wpdb->esc_like('_flavor_') . '%';

    $opciones_flavor = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $option_pattern_1,
            $option_pattern_2
        ),
        ARRAY_N
    );

    foreach ($opciones_flavor as $opcion) {
        delete_option($opcion[0]);
        wp_cache_delete($opcion[0], 'options');
    }

    // Opciones específicas conocidas
    $opciones_especificas = [
        'flavor_chat_ia_settings',
        'flavor_chat_ia_design',
        'flavor_chat_ia_modules',
        'flavor_platform_settings',
        'flavor_db_version',
        'flavor_installed_version',
        'flavor_activation_date',
        'flavor_license_key',
        'flavor_api_key',
        'flavor_openai_key',
        'flavor_frontend_controllers_disabled',
        'flavor_legal_pages_installed',
        'flavor_placeholder_urls_fixed',
        'flavor_demo_grupo_consumo',
        'flavor_demo_data_log',
        'flavor_platform_missing_bootstrap_files',
        'flavor_chat_ia_missing_bootstrap_files',
    ];

    foreach ($opciones_especificas as $opcion) {
        delete_option($opcion);
        wp_cache_delete($opcion, 'options');
    }

    /**
     * 3. ELIMINAR USER META
     * FIX: Usar prepare con esc_like
     */
    $usermeta_pattern_1 = $wpdb->esc_like('flavor_') . '%';
    $usermeta_pattern_2 = $wpdb->esc_like('_flavor_') . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
            $usermeta_pattern_1,
            $usermeta_pattern_2
        )
    );

    /**
     * 4. ELIMINAR POST META
     * FIX: Usar prepare con esc_like
     */
    $postmeta_pattern_1 = $wpdb->esc_like('flavor_') . '%';
    $postmeta_pattern_2 = $wpdb->esc_like('_flavor_') . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
            $postmeta_pattern_1,
            $postmeta_pattern_2
        )
    );

    /**
     * 5. ELIMINAR CUSTOM POST TYPES Y SUS POSTS
     * FIX: Usar paginación para evitar OOM en sitios grandes
     */
    $post_types_flavor = [
        'marketplace_item',
        'banco_tiempo_service',
        'taller',
        'evento',
        'espacio',
        'curso',
        'aviso_municipal',
        'incidencia',
        'propuesta',
        'flavor_template',
        'flavor_layout',
        'flavor_app',
        'flavor_landing',
    ];

    $batch_size = 100;

    foreach ($post_types_flavor as $post_type) {
        $offset = 0;

        while (true) {
            $posts = get_posts([
                'post_type'      => $post_type,
                'posts_per_page' => $batch_size,
                'offset'         => $offset,
                'post_status'    => 'any',
                'fields'         => 'ids', // Solo IDs para mejor rendimiento
            ]);

            if (empty($posts)) {
                break;
            }

            foreach ($posts as $post_id) {
                wp_delete_post($post_id, true);
            }

            // Si obtuvimos menos del batch_size, no hay más posts
            if (count($posts) < $batch_size) {
                break;
            }

            $offset += $batch_size;

            // Limpiar cache para liberar memoria
            wp_cache_flush();
        }
    }

    /**
     * 6. ELIMINAR TÉRMINOS DE TAXONOMÍAS PERSONALIZADAS
     * FIX: Usar paginación
     */
    $taxonomias_flavor = [
        'marketplace_category',
        'evento_categoria',
        'taller_categoria',
        'incidencia_tipo',
        'flavor_module_tag',
    ];

    foreach ($taxonomias_flavor as $taxonomia) {
        $offset = 0;

        while (true) {
            $terms = get_terms([
                'taxonomy'   => $taxonomia,
                'hide_empty' => false,
                'number'     => $batch_size,
                'offset'     => $offset,
                'fields'     => 'ids',
            ]);

            if (is_wp_error($terms) || empty($terms)) {
                break;
            }

            foreach ($terms as $term_id) {
                wp_delete_term($term_id, $taxonomia);
            }

            if (count($terms) < $batch_size) {
                break;
            }

            $offset += $batch_size;
        }
    }

    /**
     * 7. ELIMINAR ROLES PERSONALIZADOS
     */
    $roles_flavor = [
        'flavor_admin',
        'flavor_gestor',
        'flavor_moderador',
        'flavor_miembro',
        'flavor_socio',
        'flavor_artista',
        'gestor_huertos',
        'gestor_espacios',
        'gestor_eventos',
        'gestor_marketplace',
    ];

    foreach ($roles_flavor as $rol) {
        remove_role($rol);
    }

    /**
     * 8. ELIMINAR CAPABILITIES DE ROLES EXISTENTES
     */
    $capabilities_flavor = [
        'manage_flavor_platform',
        'manage_flavor_modules',
        'manage_flavor_settings',
        'moderate_flavor_content',
        'view_flavor_dashboard',
        'edit_flavor_items',
        'delete_flavor_items',
        'flavor_ver_dashboard',
        'flavor_gestor_grupos',
    ];

    $roles_wordpress = ['administrator', 'editor', 'author', 'contributor', 'subscriber'];

    foreach ($roles_wordpress as $nombre_rol) {
        $rol = get_role($nombre_rol);
        if ($rol) {
            foreach ($capabilities_flavor as $cap) {
                $rol->remove_cap($cap);
            }
        }
    }

    /**
     * 9. ELIMINAR TRANSIENTS
     * FIX: Usar prepare con esc_like
     */
    $transient_pattern_1 = $wpdb->esc_like('_transient_flavor_') . '%';
    $transient_pattern_2 = $wpdb->esc_like('_transient_timeout_flavor_') . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $transient_pattern_1,
            $transient_pattern_2
        )
    );

    /**
     * 10. ELIMINAR CRON JOBS
     */
    $crons_flavor = [
        'flavor_daily_cleanup',
        'flavor_send_notifications',
        'flavor_sync_data',
        'flavor_check_expirations',
        'flavor_e2e_cleanup',
        'flavor_backup_messages',
        'flavor_network_sync',
    ];

    foreach ($crons_flavor as $cron) {
        wp_clear_scheduled_hook($cron);
    }

    /**
     * 11. LIMPIAR REWRITE RULES
     */
    flush_rewrite_rules();

    // Commit de la transacción
    $wpdb->query('COMMIT');

    /**
     * 12. LOG DE DESINSTALACIÓN (opcional, para debug)
     */
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Flavor Platform: Desinstalación completa - Todos los datos han sido eliminados.');
    }

} catch (Exception $e) {
    // Rollback en caso de error
    $wpdb->query('ROLLBACK');

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Flavor Platform: Error durante desinstalación - ' . $e->getMessage());
    }
}
