<?php
/**
 * Vista: Detalle de Documento Legal
 *
 * Variables disponibles:
 * - $documento: objeto con datos del documento
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = [
    'normativa' => __('Normativa', 'flavor-chat-ia'),
    'estatutos' => __('Estatutos', 'flavor-chat-ia'),
    'contratos' => __('Contratos', 'flavor-chat-ia'),
    'formularios' => __('Formularios', 'flavor-chat-ia'),
    'guias' => __('Guias', 'flavor-chat-ia'),
    'otros' => __('Otros', 'flavor-chat-ia'),
];

$categoria_nombre = $categorias[$documento->categoria ?? 'otros'] ?? __('Otros', 'flavor-chat-ia');
$esta_guardado = false;
if (is_user_logged_in()) {
    global $wpdb;
    $tabla_favoritos = $wpdb->prefix . 'flavor_documentacion_favoritos';
    $esta_guardado = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_favoritos WHERE documento_id = %d AND usuario_id = %d",
        $documento->id,
        get_current_user_id()
    )) > 0;
}
?>

<div class="doc-legal-detalle">
    <nav class="doc-legal-breadcrumb">
        <a href="<?php echo esc_url(home_url('/documentacion-legal/')); ?>"><?php _e('Documentacion Legal', 'flavor-chat-ia'); ?></a>
        <span class="sep">/</span>
        <span class="current"><?php echo esc_html(wp_trim_words($documento->titulo, 5)); ?></span>
    </nav>

    <article class="doc-legal-articulo">
        <header class="doc-legal-header">
            <div class="doc-legal-meta">
                <span class="doc-legal-badge"><?php echo esc_html($categoria_nombre); ?></span>
                <?php if (!empty($documento->fecha_publicacion)): ?>
                <span class="doc-legal-fecha">
                    <?php echo esc_html(date_i18n('d/m/Y', strtotime($documento->fecha_publicacion))); ?>
                </span>
                <?php endif; ?>
                <span class="doc-legal-visitas">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php printf(__('%d visitas', 'flavor-chat-ia'), intval($documento->visitas ?? 0)); ?>
                </span>
            </div>

            <h1 class="doc-legal-titulo"><?php echo esc_html($documento->titulo); ?></h1>

            <?php if (!empty($documento->descripcion)): ?>
            <p class="doc-legal-descripcion"><?php echo esc_html($documento->descripcion); ?></p>
            <?php endif; ?>
        </header>

        <div class="doc-legal-acciones">
            <?php if (!empty($documento->archivo_url)): ?>
            <a href="<?php echo esc_url($documento->archivo_url); ?>" class="doc-legal-btn doc-legal-btn-primary" target="_blank" download>
                <span class="dashicons dashicons-download"></span>
                <?php _e('Descargar documento', 'flavor-chat-ia'); ?>
            </a>
            <?php endif; ?>

            <?php if (is_user_logged_in()): ?>
            <button class="doc-legal-btn doc-legal-btn-secondary doc-legal-guardar-btn" data-id="<?php echo esc_attr($documento->id); ?>" data-guardado="<?php echo $esta_guardado ? '1' : '0'; ?>">
                <span class="dashicons dashicons-<?php echo $esta_guardado ? 'star-filled' : 'star-empty'; ?>"></span>
                <?php echo $esta_guardado ? __('Guardado', 'flavor-chat-ia') : __('Guardar', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>

            <button class="doc-legal-btn doc-legal-btn-secondary doc-legal-compartir-btn">
                <span class="dashicons dashicons-share"></span>
                <?php _e('Compartir', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <?php if (!empty($documento->contenido)): ?>
        <div class="doc-legal-contenido">
            <?php echo wp_kses_post($documento->contenido); ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($documento->archivo_url)): ?>
        <div class="doc-legal-preview">
            <h3><?php _e('Vista previa', 'flavor-chat-ia'); ?></h3>
            <?php
            $extension = pathinfo($documento->archivo_url, PATHINFO_EXTENSION);
            if ($extension === 'pdf'):
            ?>
            <iframe src="<?php echo esc_url($documento->archivo_url); ?>" class="doc-legal-pdf-viewer"></iframe>
            <?php else: ?>
            <p class="doc-legal-no-preview">
                <?php _e('No se puede previsualizar este tipo de archivo. Por favor, descargalo para verlo.', 'flavor-chat-ia'); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($documento->tags)): ?>
        <div class="doc-legal-tags">
            <h3><?php _e('Etiquetas', 'flavor-chat-ia'); ?></h3>
            <div class="doc-legal-tags-lista">
                <?php
                $tags = is_array($documento->tags) ? $documento->tags : explode(',', $documento->tags);
                foreach ($tags as $tag):
                    $tag = trim($tag);
                    if ($tag):
                ?>
                <a href="<?php echo esc_url(add_query_arg('tag', urlencode($tag), home_url('/documentacion-legal/'))); ?>" class="doc-legal-tag">
                    <?php echo esc_html($tag); ?>
                </a>
                <?php
                    endif;
                endforeach;
                ?>
            </div>
        </div>
        <?php endif; ?>
    </article>

    <nav class="doc-legal-nav">
        <a href="<?php echo esc_url(home_url('/documentacion-legal/')); ?>" class="doc-legal-btn doc-legal-btn-link">
            <span class="dashicons dashicons-arrow-left-alt"></span>
            <?php _e('Volver al listado', 'flavor-chat-ia'); ?>
        </a>
    </nav>
</div>
