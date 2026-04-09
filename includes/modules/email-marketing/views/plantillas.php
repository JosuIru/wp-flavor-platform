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
    'basica' => __('Básicas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'newsletter' => __('Newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'promocional' => __('Promocionales', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'transaccional' => __('Transaccionales', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'personalizada' => __('Personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="wrap em-plantillas">
    <h1>
        <?php _e('Plantillas de email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        <button type="button" class="page-title-action em-btn-nueva-plantilla">
            <?php _e('Nueva plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </h1>

    <p class="description">
        <?php _e('Reutiliza plantillas para crear campañas más rápidamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </p>

    <!-- Filtro por categoría -->
    <div class="em-categorias-tabs">
        <a href="#" class="active" data-categoria="todas"><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
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
                        <span class="em-badge em-badge-info"><?php _e('Predefinida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                </div>

                <div class="em-plantilla-acciones">
                    <button type="button" class="button em-btn-usar-plantilla" data-id="<?php echo esc_attr($plantilla->id); ?>">
                        <?php _e('Usar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
                <span><?php _e('Crear plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>
        </div>
    </div>
</div>

<!-- Modal de edición de plantilla -->
<div class="em-modal" id="em-modal-plantilla" style="display:none;">
    <div class="em-modal-content em-modal-xl">
        <h3 id="em-modal-plantilla-titulo"><?php _e('Nueva plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <form id="em-form-plantilla">
            <input type="hidden" name="plantilla_id" id="em-plantilla-id" value="">

            <div class="em-plantilla-editor-layout">
                <div class="em-plantilla-editor-sidebar">
                    <div class="em-form-section">
                        <label for="em-plantilla-nombre"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="em-plantilla-nombre" name="nombre" required>
                    </div>

                    <div class="em-form-section">
                        <label for="em-plantilla-categoria"><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select id="em-plantilla-categoria" name="categoria">
                            <?php foreach ($categorias as $slug => $nombre): ?>
                                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="em-form-section">
                        <h4><?php _e('Variables disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <ul class="em-variables-lista">
                            <li><code><?php echo esc_html__('{{nombre}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code> - <?php _e('Nombre del suscriptor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><code><?php echo esc_html__('{{email}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code> - <?php _e('Email del suscriptor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><code><?php echo esc_html__('{{nombre_sitio}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code> - <?php _e('Nombre del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><code><?php echo esc_html__('{{contenido}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code> - <?php _e('Contenido de la campaña', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                            <li><code><?php echo esc_html__('{{url_baja}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code> - <?php _e('Enlace de baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        </ul>
                    </div>
                </div>

                <div class="em-plantilla-editor-main">
                    <label><?php _e('Código HTML de la plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="em-plantilla-html" name="contenido_html" rows="25"></textarea>
                </div>
            </div>

            <div class="em-modal-actions">
                <button type="button" class="button em-modal-close"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Guardar plantilla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de preview -->
<div class="em-modal" id="em-modal-preview" style="display:none;">
    <div class="em-modal-content em-modal-xl">
        <button type="button" class="em-modal-close em-modal-close-icon"><?php echo esc_html__('&times;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        <div class="em-preview-container">
            <iframe id="em-preview-iframe" frameborder="0"></iframe>
        </div>
    </div>
</div>
