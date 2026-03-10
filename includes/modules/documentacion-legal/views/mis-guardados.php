<?php
/**
 * Vista: Mis Documentos Guardados
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$usuario_id = get_current_user_id();
$tabla_documentos = $wpdb->prefix . 'flavor_documentacion_legal';
$tabla_favoritos = $wpdb->prefix . 'flavor_documentacion_favoritos';

$documentos_guardados = $wpdb->get_results($wpdb->prepare(
    "SELECT d.*, f.fecha_guardado
     FROM $tabla_documentos d
     INNER JOIN $tabla_favoritos f ON d.id = f.documento_id
     WHERE f.usuario_id = %d AND d.estado = 'publicado'
     ORDER BY f.fecha_guardado DESC",
    $usuario_id
));

$categorias = [
    'normativa' => __('Normativa', 'flavor-chat-ia'),
    'estatutos' => __('Estatutos', 'flavor-chat-ia'),
    'contratos' => __('Contratos', 'flavor-chat-ia'),
    'formularios' => __('Formularios', 'flavor-chat-ia'),
    'guias' => __('Guias', 'flavor-chat-ia'),
    'otros' => __('Otros', 'flavor-chat-ia'),
];
?>

<div class="doc-legal-guardados">
    <h2><?php _e('Mis documentos guardados', 'flavor-chat-ia'); ?></h2>

    <?php if (empty($documentos_guardados)): ?>
    <div class="doc-legal-empty">
        <span class="dashicons dashicons-star-empty"></span>
        <p><?php _e('No tienes documentos guardados.', 'flavor-chat-ia'); ?></p>
        <p><?php _e('Guarda documentos para acceder a ellos rapidamente.', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/documentacion-legal/')); ?>" class="doc-legal-btn doc-legal-btn-primary">
            <?php _e('Explorar documentos', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php else: ?>

    <div class="doc-legal-lista">
        <?php foreach ($documentos_guardados as $doc):
            $categoria_nombre = $categorias[$doc->categoria ?? 'otros'] ?? __('Otros', 'flavor-chat-ia');
        ?>
        <div class="doc-legal-item" data-id="<?php echo esc_attr($doc->id); ?>">
            <div class="doc-legal-item-icono">
                <span class="dashicons dashicons-media-document"></span>
            </div>

            <div class="doc-legal-item-info">
                <h3 class="doc-legal-item-titulo">
                    <a href="<?php echo esc_url(add_query_arg('doc_id', $doc->id, home_url('/documentacion-legal/detalle/'))); ?>">
                        <?php echo esc_html($doc->titulo); ?>
                    </a>
                </h3>

                <div class="doc-legal-item-meta">
                    <span class="doc-legal-badge"><?php echo esc_html($categoria_nombre); ?></span>
                    <span class="doc-legal-fecha-guardado">
                        <?php printf(__('Guardado: %s', 'flavor-chat-ia'), date_i18n('d/m/Y', strtotime($doc->fecha_guardado))); ?>
                    </span>
                </div>

                <?php if (!empty($doc->descripcion)): ?>
                <p class="doc-legal-item-desc"><?php echo esc_html(wp_trim_words($doc->descripcion, 20)); ?></p>
                <?php endif; ?>
            </div>

            <div class="doc-legal-item-acciones">
                <?php if (!empty($doc->archivo_url)): ?>
                <a href="<?php echo esc_url($doc->archivo_url); ?>" class="doc-legal-btn-icon" title="<?php esc_attr_e('Descargar', 'flavor-chat-ia'); ?>" download>
                    <span class="dashicons dashicons-download"></span>
                </a>
                <?php endif; ?>
                <button class="doc-legal-btn-icon doc-legal-quitar-guardado" data-id="<?php echo esc_attr($doc->id); ?>" title="<?php esc_attr_e('Quitar de guardados', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-star-filled"></span>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <p class="doc-legal-total">
        <?php printf(_n('%d documento guardado', '%d documentos guardados', count($documentos_guardados), 'flavor-chat-ia'), count($documentos_guardados)); ?>
    </p>

    <?php endif; ?>
</div>
