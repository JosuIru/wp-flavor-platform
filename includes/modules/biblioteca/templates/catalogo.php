<?php
/**
 * Template: Catálogo de Biblioteca
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';

// Obtener géneros disponibles
$generos = $wpdb->get_col("SELECT DISTINCT genero FROM $tabla_libros WHERE genero IS NOT NULL AND genero != '' ORDER BY genero");

$genero_actual = isset($atts['genero']) ? sanitize_text_field($atts['genero']) : '';
?>

<div class="biblioteca-wrapper">
    <div class="biblioteca-header">
        <h2 class="biblioteca-titulo"><?php _e('Biblioteca Comunitaria', 'flavor-chat-ia'); ?></h2>

        <form class="biblioteca-buscador">
            <input type="text" name="busqueda" placeholder="<?php esc_attr_e('Buscar por título, autor...', 'flavor-chat-ia'); ?>">
            <button type="submit">
                <span class="dashicons dashicons-search"></span>
            </button>
        </form>
    </div>

    <div class="biblioteca-filtros">
        <div class="biblioteca-filtro-grupo">
            <label><?php _e('Género:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-genero">
                <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                <?php foreach ($generos as $genero): ?>
                    <option value="<?php echo esc_attr($genero); ?>" <?php selected($genero_actual, $genero); ?>>
                        <?php echo esc_html($genero); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="biblioteca-filtro-grupo">
            <label><?php _e('Disponibilidad:', 'flavor-chat-ia'); ?></label>
            <select id="filtro-disponibilidad">
                <option value=""><?php _e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="disponible"><?php _e('Disponibles', 'flavor-chat-ia'); ?></option>
                <option value="prestado"><?php _e('Prestados', 'flavor-chat-ia'); ?></option>
            </select>
        </div>

        <?php if (is_user_logged_in()): ?>
            <a href="<?php echo add_query_arg('vista', 'agregar', get_permalink()); ?>" class="btn btn-primary btn-sm" style="margin-left: auto;">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php _e('Agregar libro', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="biblioteca-grid">
        <div class="biblioteca-loading">
            <div class="biblioteca-spinner"></div>
            <span><?php _e('Cargando libros...', 'flavor-chat-ia'); ?></span>
        </div>
    </div>
</div>
