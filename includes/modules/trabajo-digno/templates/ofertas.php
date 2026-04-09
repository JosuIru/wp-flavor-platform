<?php
/**
 * Template: Ofertas de Trabajo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos = Flavor_Chat_Trabajo_Digno_Module::TIPOS_OFERTA;
$sectores = Flavor_Chat_Trabajo_Digno_Module::SECTORES;
$jornadas = Flavor_Chat_Trabajo_Digno_Module::JORNADAS;
$criterios = Flavor_Chat_Trabajo_Digno_Module::CRITERIOS_DIGNIDAD;

$user_id = get_current_user_id();

$ofertas = get_posts([
    'post_type' => 'td_oferta',
    'post_status' => 'publish',
    'posts_per_page' => 30,
    'orderby' => 'date',
    'order' => 'DESC',
]);

// Contar por tipo
$conteo_tipos = [];
foreach ($tipos as $tipo_id => $tipo_data) {
    $conteo_tipos[$tipo_id] = 0;
}
foreach ($ofertas as $oferta) {
    $tipo = get_post_meta($oferta->ID, '_td_tipo', true);
    if (isset($conteo_tipos[$tipo])) {
        $conteo_tipos[$tipo]++;
    }
}

// Instancia del módulo para calcular dignidad
$modulo = new Flavor_Chat_Trabajo_Digno_Module();
?>

<div class="td-container">
    <header class="td-header">
        <h2><?php esc_html_e('Bolsa de Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Ofertas de empleo con criterios éticos y condiciones justas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Stats -->
    <div class="td-stats-bar">
        <div class="td-stat-item">
            <div class="td-stat-item__valor"><?php echo esc_html(count($ofertas)); ?></div>
            <div class="td-stat-item__label"><?php esc_html_e('Ofertas activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <?php foreach ($tipos as $tipo_id => $tipo_data) : ?>
        <div class="td-stat-item">
            <div class="td-stat-item__valor" style="color: <?php echo esc_attr($tipo_data['color']); ?>">
                <?php echo esc_html($conteo_tipos[$tipo_id]); ?>
            </div>
            <div class="td-stat-item__label"><?php echo esc_html($tipo_data['nombre']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="td-filtros">
        <select class="td-filtro-sector">
            <option value="todos"><?php esc_html_e('Todos los sectores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php foreach ($sectores as $sector_id => $sector_data) : ?>
            <option value="<?php echo esc_attr($sector_id); ?>"><?php echo esc_html($sector_data['nombre']); ?></option>
            <?php endforeach; ?>
        </select>

        <select class="td-filtro-jornada">
            <option value="todos"><?php esc_html_e('Todas las jornadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php foreach ($jornadas as $jornada_id => $jornada_nombre) : ?>
            <option value="<?php echo esc_attr($jornada_id); ?>"><?php echo esc_html($jornada_nombre); ?></option>
            <?php endforeach; ?>
        </select>

        <?php if (is_user_logged_in()) : ?>
        <a href="<?php echo esc_url(home_url('/trabajo-digno/publicar/')); ?>" class="td-btn td-btn--primary" style="margin-left: auto;">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Publicar oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>
    </div>

    <!-- Tipos tabs -->
    <div class="td-tipos-tabs">
        <button class="td-tipo-tab activo" data-tipo="todos">
            <?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
        <?php foreach ($tipos as $tipo_id => $tipo_data) : ?>
        <button class="td-tipo-tab" data-tipo="<?php echo esc_attr($tipo_id); ?>">
            <span class="dashicons <?php echo esc_attr($tipo_data['icono']); ?>" style="color: <?php echo esc_attr($tipo_data['color']); ?>"></span>
            <?php echo esc_html($tipo_data['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <?php if ($ofertas) : ?>
    <div class="td-ofertas-grid">
        <?php foreach ($ofertas as $oferta) :
            $tipo = get_post_meta($oferta->ID, '_td_tipo', true);
            $tipo_data = $tipos[$tipo] ?? ['nombre' => '', 'color' => '#6b7280', 'icono' => 'dashicons-businessman'];

            $jornada = get_post_meta($oferta->ID, '_td_jornada', true);
            $ubicacion = get_post_meta($oferta->ID, '_td_ubicacion', true);
            $salario = get_post_meta($oferta->ID, '_td_salario', true);
            $criterios_oferta = get_post_meta($oferta->ID, '_td_criterios_dignidad', true) ?: [];
            $postulaciones = get_post_meta($oferta->ID, '_td_postulaciones', true) ?: [];

            $indice_dignidad = $modulo->calcular_indice_dignidad($oferta->ID);

            $terms = wp_get_post_terms($oferta->ID, 'td_sector');
            $sector_slug = !empty($terms) ? $terms[0]->slug : '';

            // Verificar si ya postuló
            $ya_postulo = false;
            foreach ($postulaciones as $p) {
                if ($p['user_id'] == $user_id) {
                    $ya_postulo = true;
                    break;
                }
            }

            $autor = get_user_by('ID', $oferta->post_author);
        ?>
        <article class="td-oferta-card"
                 data-tipo="<?php echo esc_attr($tipo); ?>"
                 data-sector="<?php echo esc_attr($sector_slug); ?>"
                 data-jornada="<?php echo esc_attr($jornada); ?>"
                 style="border-left-color: <?php echo esc_attr($tipo_data['color']); ?>">
            <div class="td-oferta-card__header">
                <span class="td-oferta-card__tipo" style="background: <?php echo esc_attr($tipo_data['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($tipo_data['icono']); ?>"></span>
                    <?php echo esc_html($tipo_data['nombre']); ?>
                </span>
                <div class="td-oferta-card__dignidad" title="<?php esc_attr_e('Índice de trabajo digno', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span><?php echo esc_html($indice_dignidad); ?>%</span>
                    <div class="td-dignidad-bar">
                        <div class="td-dignidad-bar__fill" style="width: <?php echo esc_attr($indice_dignidad); ?>%"></div>
                    </div>
                </div>
            </div>

            <h3 class="td-oferta-card__titulo"><?php echo esc_html($oferta->post_title); ?></h3>
            <div class="td-oferta-card__empresa">
                <?php echo esc_html($autor ? $autor->display_name : __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
            </div>

            <div class="td-oferta-card__meta">
                <?php if ($ubicacion) : ?>
                <span><span class="dashicons dashicons-location"></span> <?php echo esc_html($ubicacion); ?></span>
                <?php endif; ?>
                <?php if ($jornada && isset($jornadas[$jornada])) : ?>
                <span><span class="dashicons dashicons-clock"></span> <?php echo esc_html($jornadas[$jornada]); ?></span>
                <?php endif; ?>
                <?php if ($salario) : ?>
                <span><span class="dashicons dashicons-money-alt"></span> <?php echo esc_html($salario); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!empty($criterios_oferta)) : ?>
            <div class="td-oferta-card__criterios">
                <?php foreach ($criterios_oferta as $criterio_id) :
                    $criterio_data = $criterios[$criterio_id] ?? null;
                    if (!$criterio_data) continue;
                ?>
                <span class="td-criterio-badge" title="<?php echo esc_attr($criterio_data['descripcion']); ?>">
                    <span class="dashicons <?php echo esc_attr($criterio_data['icono']); ?>"></span>
                    <?php echo esc_html($criterio_data['nombre']); ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="td-oferta-card__footer">
                <span style="color: var(--td-text-light); font-size: 0.85rem;">
                    <?php echo esc_html(human_time_diff(strtotime($oferta->post_date), current_time('timestamp'))); ?>
                </span>

                <?php if (is_user_logged_in() && $oferta->post_author != $user_id) : ?>
                    <?php if ($ya_postulo) : ?>
                    <span class="td-btn td-btn--secondary td-btn--small"><?php esc_html_e('Postulado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php else : ?>
                    <button class="td-btn td-btn--primary td-btn--small td-btn-postular" data-oferta="<?php echo esc_attr($oferta->ID); ?>">
                        <?php esc_html_e('Postular', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="td-empty-state">
        <span class="dashicons dashicons-businessman"></span>
        <p><?php esc_html_e('No hay ofertas de trabajo publicadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php if (is_user_logged_in()) : ?>
        <a href="<?php echo esc_url(home_url('/trabajo-digno/publicar/')); ?>" class="td-btn td-btn--primary">
            <?php esc_html_e('Publicar la primera oferta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
