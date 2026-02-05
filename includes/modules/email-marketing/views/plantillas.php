<?php
/**
 * Vista: Plantillas de email
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$plantillas = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}flavor_em_plantillas WHERE activa = 1 ORDER BY es_predefinida DESC, categoria, nombre ASC"
);

$categorias = [
    'basica' => __('Básicas', 'flavor-chat-ia'),
    'newsletter' => __('Newsletter', 'flavor-chat-ia'),
    'promocional' => __('Promocionales', 'flavor-chat-ia'),
    'transaccional' => __('Transaccionales', 'flavor-chat-ia'),
    'personalizada' => __('Personalizadas', 'flavor-chat-ia'),
];
?>

<div class="wrap em-plantillas">
    <h1>
        <?php _e('Plantillas de email', 'flavor-chat-ia'); ?>
        <button type="button" class="page-title-action em-btn-nueva-plantilla">
            <?php _e('Nueva plantilla', 'flavor-chat-ia'); ?>
        </button>
    </h1>

    <p class="description">
        <?php _e('Reutiliza plantillas para crear campañas más rápidamente.', 'flavor-chat-ia'); ?>
    </p>

    <!-- Filtro por categoría -->
    <div class="em-categorias-tabs">
        <a href="#" class="active" data-categoria="todas"><?php _e('Todas', 'flavor-chat-ia'); ?></a>
        <?php foreach ($categorias as $slug => $nombre): ?>
            <a href="#" data-categoria="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="em-plantillas-grid">
        <?php foreach ($plantillas as $plantilla): ?>
            <div class="em-plantilla-card" data-categoria="<?php echo esc_attr($plantilla->categoria); ?>" data-id="<?php echo esc_attr($plantilla->id); ?>">
                <div class="em-plantilla-preview">
                    <?php if ($plantilla->thumbnail): ?>
                        <img src="<?php echo esc_url($plantilla->thumbnail); ?>" alt="">
                    <?php else: ?>
                        <div class="em-plantilla-preview-placeholder">
                            <span class="dashicons dashicons-email-alt2"></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="em-plantilla-info">
                    <h3><?php echo esc_html($plantilla->nombre); ?></h3>
                    <span class="em-plantilla-categoria"><?php echo esc_html($categorias[$plantilla->categoria] ?? $plantilla->categoria); ?></span>

                    <?php if ($plantilla->es_predefinida): ?>
                        <span class="em-badge em-badge-info"><?php _e('Predefinida', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                </div>

                <div class="em-plantilla-acciones">
                    <button type="button" class="button em-btn-usar-plantilla" data-id="<?php echo esc_attr($plantilla->id); ?>">
                        <?php _e('Usar', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="button" class="button em-btn-preview-plantilla" data-id="<?php echo esc_attr($plantilla->id); ?>">
                        <span class="dashicons dashicons-visibility"></span>
                    </button>
                    <?php if (!$plantilla->es_predefinida): ?>
                        <button type="button" class="button em-btn-editar-plantilla" data-id="<?php echo esc_attr($plantilla->id); ?>">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="button em-btn-eliminar-plantilla" data-id="<?php echo esc_attr($plantilla->id); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Card para crear nueva plantilla -->
        <div class="em-plantilla-card em-plantilla-nueva">
            <button type="button" class="em-btn-nueva-plantilla">
                <span class="dashicons dashicons-plus-alt2"></span>
                <span><?php _e('Crear plantilla', 'flavor-chat-ia'); ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Modal de edición de plantilla -->
<div class="em-modal" id="em-modal-plantilla" style="display:none;">
    <div class="em-modal-content em-modal-xl">
        <h3 id="em-modal-plantilla-titulo"><?php _e('Nueva plantilla', 'flavor-chat-ia'); ?></h3>
        <form id="em-form-plantilla">
            <input type="hidden" name="plantilla_id" id="em-plantilla-id" value="">

            <div class="em-plantilla-editor-layout">
                <div class="em-plantilla-editor-sidebar">
                    <div class="em-form-section">
                        <label for="em-plantilla-nombre"><?php _e('Nombre', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="em-plantilla-nombre" name="nombre" required>
                    </div>

                    <div class="em-form-section">
                        <label for="em-plantilla-categoria"><?php _e('Categoría', 'flavor-chat-ia'); ?></label>
                        <select id="em-plantilla-categoria" name="categoria">
                            <?php foreach ($categorias as $slug => $nombre): ?>
                                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="em-form-section">
                        <h4><?php _e('Variables disponibles', 'flavor-chat-ia'); ?></h4>
                        <ul class="em-variables-lista">
                            <li><code>{{nombre}}</code> - <?php _e('Nombre del suscriptor', 'flavor-chat-ia'); ?></li>
                            <li><code>{{email}}</code> - <?php _e('Email del suscriptor', 'flavor-chat-ia'); ?></li>
                            <li><code>{{nombre_sitio}}</code> - <?php _e('Nombre del sitio', 'flavor-chat-ia'); ?></li>
                            <li><code>{{contenido}}</code> - <?php _e('Contenido de la campaña', 'flavor-chat-ia'); ?></li>
                            <li><code>{{url_baja}}</code> - <?php _e('Enlace de baja', 'flavor-chat-ia'); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="em-plantilla-editor-main">
                    <label><?php _e('Código HTML de la plantilla', 'flavor-chat-ia'); ?></label>
                    <textarea id="em-plantilla-html" name="contenido_html" rows="25"></textarea>
                </div>
            </div>

            <div class="em-modal-actions">
                <button type="button" class="button em-modal-close"><?php _e('Cancelar', 'flavor-chat-ia'); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Guardar plantilla', 'flavor-chat-ia'); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de preview -->
<div class="em-modal" id="em-modal-preview" style="display:none;">
    <div class="em-modal-content em-modal-xl">
        <button type="button" class="em-modal-close em-modal-close-icon">&times;</button>
        <div class="em-preview-container">
            <iframe id="em-preview-iframe" frameborder="0"></iframe>
        </div>
    </div>
</div>
