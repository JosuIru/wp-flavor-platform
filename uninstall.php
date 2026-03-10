<?php
/**
 * Uninstall Flavor Platform
 *
 * Limpia todos los datos del plugin cuando se desinstala desde WordPress.
 * Solo se ejecuta si el usuario ha habilitado la opción de limpieza.
 *
 * @package Flavor_Chat_IA
 * @since 3.3.0
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

/**
 * 1. ELIMINAR TABLAS PERSONALIZADAS
 */
$tablas_flavor = $wpdb->get_results(
    "SHOW TABLES LIKE '{$wpdb->prefix}flavor_%'",
    ARRAY_N
);

foreach ($tablas_flavor as $tabla) {
    $wpdb->query("DROP TABLE IF EXISTS {$tabla[0]}");
}

/**
 * 2. ELIMINAR OPTIONS
 */
$opciones_flavor = $wpdb->get_results(
    "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'flavor_%' OR option_name LIKE '_flavor_%'",
    ARRAY_N
);

foreach ($opciones_flavor as $opcion) {
    delete_option($opcion[0]);
}

// Opciones específicas conocidas
$opciones_especificas = [
    'flavor_chat_ia_settings',
    'flavor_chat_ia_design',
    'flavor_chat_ia_modules',
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
];

foreach ($opciones_especificas as $opcion) {
    delete_option($opcion);
}

/**
 * 3. ELIMINAR USER META
 */
$wpdb->query(
    "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'flavor_%' OR meta_key LIKE '_flavor_%'"
);

/**
 * 4. ELIMINAR POST META
 */
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'flavor_%' OR meta_key LIKE '_flavor_%'"
);

/**
 * 5. ELIMINAR CUSTOM POST TYPES Y SUS POSTS
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
];

foreach ($post_types_flavor as $post_type) {
    $posts = get_posts([
        'post_type' => $post_type,
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

/**
 * 6. ELIMINAR TÉRMINOS DE TAXONOMÍAS PERSONALIZADAS
 */
$taxonomias_flavor = [
    'marketplace_category',
    'evento_categoria',
    'taller_categoria',
    'incidencia_tipo',
    'flavor_module_tag',
];

foreach ($taxonomias_flavor as $taxonomia) {
    $terms = get_terms([
        'taxonomy' => $taxonomia,
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomia);
        }
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
 */
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_flavor_%' OR option_name LIKE '_transient_timeout_flavor_%'"
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
];

foreach ($crons_flavor as $cron) {
    wp_clear_scheduled_hook($cron);
}

/**
 * 11. LIMPIAR REWRITE RULES
 */
flush_rewrite_rules();

/**
 * 12. LOG DE DESINSTALACIÓN (opcional, para debug)
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Flavor Platform: Desinstalación completa - Todos los datos han sido eliminados.');
}
