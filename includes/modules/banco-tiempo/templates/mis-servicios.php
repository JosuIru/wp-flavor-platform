<?php
/**
 * Template: Mis Servicios - Banco de Tiempo
 *
 * Variables disponibles:
 * - $servicios: array de servicios del usuario actual
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = [
    'cuidados' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'educacion' => __('Educacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'bricolaje' => __('Bricolaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'tecnologia' => __('Tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'transporte' => __('Transporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="bt-mis-servicios">
    <div class="bt-header-acciones">
        <h2><?php _e('Mis Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <a href="<?php echo esc_url(add_query_arg('accion', 'nuevo', home_url('/banco-tiempo/ofrecer/'))); ?>" class="bt-btn bt-btn-primary">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Ofrecer nuevo servicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>

    <?php if (empty($servicios)): ?>
    <div class="bt-empty-state">
        <span class="dashicons dashicons-admin-tools"></span>
        <p><?php _e('No tienes servicios publicados todavia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <p><?php _e('Comparte tus habilidades con la comunidad y gana horas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php else: ?>

    <div class="bt-services-grid bt-services-mine">
        <?php foreach ($servicios as $servicio):
            $categoria_key = strtolower($servicio->categoria ?? 'otros');
            $estado = $servicio->estado ?? 'activo';
            $es_activo = $estado === 'activo';
        ?>
        <div class="bt-service-card <?php echo !$es_activo ? 'bt-service-paused' : ''; ?>">
            <div class="bt-service-header">
                <span class="bt-service-category"><?php echo esc_html($categorias[$categoria_key] ?? __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                <span class="bt-service-status bt-status-<?php echo esc_attr($estado); ?>">
                    <?php echo $es_activo ? __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Pausado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
            </div>

            <h4 class="bt-service-title"><?php echo esc_html($servicio->titulo); ?></h4>
            <p class="bt-service-desc"><?php echo esc_html(wp_trim_words($servicio->descripcion, 18)); ?></p>

            <div class="bt-service-meta">
                <span class="bt-service-hours">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo esc_html(number_format((float)($servicio->horas_estimadas ?? 1), 1)); ?>h
                </span>
                <?php if (!empty($servicio->fecha_publicacion)): ?>
                <span class="bt-service-date">
                    <?php echo esc_html(date_i18n('d/m/Y', strtotime($servicio->fecha_publicacion))); ?>
                </span>
                <?php endif; ?>
            </div>

            <div class="bt-service-actions">
                <a href="<?php echo esc_url(add_query_arg(['accion' => 'editar', 'id' => $servicio->id], home_url('/banco-tiempo/ofrecer/'))); ?>" class="bt-btn-icon" title="<?php esc_attr_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-edit"></span>
                </a>
                <?php if ($es_activo): ?>
                <button class="bt-btn-icon bt-pausar-servicio" data-id="<?php echo esc_attr($servicio->id); ?>" title="<?php esc_attr_e('Pausar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-controls-pause"></span>
                </button>
                <?php else: ?>
                <button class="bt-btn-icon bt-activar-servicio" data-id="<?php echo esc_attr($servicio->id); ?>" title="<?php esc_attr_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-controls-play"></span>
                </button>
                <?php endif; ?>
                <a href="<?php echo esc_url(add_query_arg('servicio_id', $servicio->id, home_url('/banco-tiempo/servicio/'))); ?>" class="bt-btn-icon" title="<?php esc_attr_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-visibility"></span>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>
