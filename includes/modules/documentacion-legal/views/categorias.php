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
        'nombre' => __('Normativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Leyes, ordenanzas y normativas aplicables', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-book',
    ],
    'estatutos' => [
        'nombre' => __('Estatutos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Estatutos de la asociacion y reglamentos internos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-media-document',
    ],
    'contratos' => [
        'nombre' => __('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Modelos de contratos y convenios', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-clipboard',
    ],
    'formularios' => [
        'nombre' => __('Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Formularios y plantillas para tramites', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-forms',
    ],
    'guias' => [
        'nombre' => __('Guias', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Guias practicas y manuales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-lightbulb',
    ],
    'otros' => [
        'nombre' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'descripcion' => __('Documentos varios', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
    <h2><?php _e('Categorias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
    <p class="doc-legal-intro"><?php _e('Explora los documentos organizados por categoria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

    <div class="doc-legal-categorias-grid">
        <?php foreach ($categorias as $slug => $cat): ?>
        <a href="<?php echo esc_url(add_query_arg('categoria', $slug, home_url('/documentacion-legal/'))); ?>" class="doc-legal-categoria-card">
            <div class="doc-legal-categoria-icono">
                <span class="dashicons <?php echo esc_attr($cat['icono']); ?>"></span>
            </div>
            <h3 class="doc-legal-categoria-nombre"><?php echo esc_html($cat['nombre']); ?></h3>
            <p class="doc-legal-categoria-desc"><?php echo esc_html($cat['descripcion']); ?></p>
            <span class="doc-legal-categoria-count">
                <?php printf(_n('%d documento', '%d documentos', $cat['count'], FLAVOR_PLATFORM_TEXT_DOMAIN), $cat['count']); ?>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
