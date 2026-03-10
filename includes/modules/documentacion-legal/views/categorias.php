<?php
/**
 * Vista: Categorias de Documentos Legales
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';

$categorias = [
    'normativa' => [
        'nombre' => __('Normativa', 'flavor-chat-ia'),
        'descripcion' => __('Leyes, ordenanzas y normativas aplicables', 'flavor-chat-ia'),
        'icono' => 'dashicons-book',
    ],
    'estatutos' => [
        'nombre' => __('Estatutos', 'flavor-chat-ia'),
        'descripcion' => __('Estatutos de la asociacion y reglamentos internos', 'flavor-chat-ia'),
        'icono' => 'dashicons-media-document',
    ],
    'contratos' => [
        'nombre' => __('Contratos', 'flavor-chat-ia'),
        'descripcion' => __('Modelos de contratos y convenios', 'flavor-chat-ia'),
        'icono' => 'dashicons-clipboard',
    ],
    'formularios' => [
        'nombre' => __('Formularios', 'flavor-chat-ia'),
        'descripcion' => __('Formularios y plantillas para tramites', 'flavor-chat-ia'),
        'icono' => 'dashicons-forms',
    ],
    'guias' => [
        'nombre' => __('Guias', 'flavor-chat-ia'),
        'descripcion' => __('Guias practicas y manuales', 'flavor-chat-ia'),
        'icono' => 'dashicons-lightbulb',
    ],
    'otros' => [
        'nombre' => __('Otros', 'flavor-chat-ia'),
        'descripcion' => __('Documentos varios', 'flavor-chat-ia'),
        'icono' => 'dashicons-portfolio',
    ],
];

// Contar documentos por categoria
foreach ($categorias as $slug => &$cat) {
    $cat['count'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_documentos WHERE categoria = %s AND estado = 'publicado'",
        $slug
    )) ?: 0;
}
unset($cat);
?>

<div class="doc-legal-categorias">
    <h2><?php _e('Categorias', 'flavor-chat-ia'); ?></h2>
    <p class="doc-legal-intro"><?php _e('Explora los documentos organizados por categoria.', 'flavor-chat-ia'); ?></p>

    <div class="doc-legal-categorias-grid">
        <?php foreach ($categorias as $slug => $cat): ?>
        <a href="<?php echo esc_url(add_query_arg('categoria', $slug, home_url('/documentacion-legal/'))); ?>" class="doc-legal-categoria-card">
            <div class="doc-legal-categoria-icono">
                <span class="dashicons <?php echo esc_attr($cat['icono']); ?>"></span>
            </div>
            <h3 class="doc-legal-categoria-nombre"><?php echo esc_html($cat['nombre']); ?></h3>
            <p class="doc-legal-categoria-desc"><?php echo esc_html($cat['descripcion']); ?></p>
            <span class="doc-legal-categoria-count">
                <?php printf(_n('%d documento', '%d documentos', $cat['count'], 'flavor-chat-ia'), $cat['count']); ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
