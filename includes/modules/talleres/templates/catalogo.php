<?php
/**
 * Template: Catalogo de Talleres
 *
 * Variables disponibles:
 * - $talleres: array de talleres publicados
 * - $atts: atributos del shortcode
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$columnas = intval($atts['columnas'] ?? 3);
$mostrar_filtros = filter_var($atts['mostrar_filtros'] ?? true, FILTER_VALIDATE_BOOLEAN);
$categorias = [
    'general' => __('General', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'arte' => __('Arte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'tecnologia' => __('Tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cocina' => __('Cocina', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'idiomas' => __('Idiomas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="talleres-catalogo">
    <?php if ($mostrar_filtros): ?>
    <div class="talleres-filtros">
        <div class="talleres-filtro-grupo">
            <label for="filtro-categoria"><?php _e('Categoria:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="filtro-categoria" class="talleres-filtro-select" data-filtro="categoria">
                <option value=""><?php _e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($categorias as $slug => $nombre): ?>
                <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($nombre); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="talleres-filtro-grupo">
            <label for="filtro-orden"><?php _e('Ordenar por:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="filtro-orden" class="talleres-filtro-select" data-filtro="orden">
                <option value="fecha"><?php _e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="titulo"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="plazas"><?php _e('Plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>

        <div class="talleres-filtro-grupo">
            <input type="text" id="filtro-buscar" class="talleres-filtro-buscar" placeholder="<?php esc_attr_e('Buscar talleres...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($talleres)): ?>
    <div class="talleres-empty">
        <span class="dashicons dashicons-welcome-learn-more"></span>
        <p><?php _e('No hay talleres disponibles en este momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php else: ?>

    <div class="talleres-grid talleres-cols-<?php echo esc_attr($columnas); ?>">
        <?php foreach ($talleres as $taller):
            $plazas_disponibles = max(0, ($taller->capacidad ?? 20) - ($taller->inscritos ?? 0));
        ?>
        <div class="talleres-card" data-categoria="<?php echo esc_attr($taller->categoria ?? ''); ?>">
            <?php if (!empty($taller->imagen)): ?>
            <div class="talleres-card-imagen">
                <img src="<?php echo esc_url($taller->imagen); ?>" alt="<?php echo esc_attr($taller->titulo); ?>">
            </div>
            <?php endif; ?>

            <div class="talleres-card-header">
                <h3><?php echo esc_html($taller->titulo); ?></h3>
                <div class="talleres-card-meta">
                    <?php if (!empty($taller->categoria)): ?>
                    <span class="talleres-badge"><?php echo esc_html(ucfirst($taller->categoria)); ?></span>
                    <?php endif; ?>
                    <span class="talleres-badge <?php echo $plazas_disponibles > 0 ? 'talleres-badge-disponible' : 'talleres-badge-completo'; ?>">
                        <?php echo $plazas_disponibles > 0 ? sprintf(__('%d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN), $plazas_disponibles) : __('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
            </div>

            <div class="talleres-card-body">
                <?php if (!empty($taller->fecha_inicio)): ?>
                <p class="talleres-fecha">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo esc_html(date_i18n('d M Y, H:i', strtotime($taller->fecha_inicio))); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($taller->ubicacion)): ?>
                <p class="talleres-ubicacion">
                    <span class="dashicons dashicons-location"></span>
                    <?php echo esc_html($taller->ubicacion); ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($taller->descripcion)): ?>
                <p class="talleres-extracto">
                    <?php echo esc_html(wp_trim_words(strip_tags($taller->descripcion), 20)); ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="talleres-card-footer">
                <a href="<?php echo esc_url(add_query_arg('taller_id', $taller->id, home_url('/taller/'))); ?>" class="talleres-btn talleres-btn-primary">
                    <?php _e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>
