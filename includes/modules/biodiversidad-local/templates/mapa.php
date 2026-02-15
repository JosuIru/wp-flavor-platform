<?php
/**
 * Template: Mapa de Avistamientos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = Flavor_Chat_Biodiversidad_Local_Module::CATEGORIAS_ESPECIES;

// Obtener avistamientos publicados con coordenadas
global $wpdb;
$avistamientos_raw = $wpdb->get_results("
    SELECT p.ID, p.post_title, p.post_date,
           pm_esp.meta_value as especie_id,
           pm_lat.meta_value as latitud,
           pm_lng.meta_value as longitud,
           pm_cant.meta_value as cantidad
    FROM {$wpdb->posts} p
    LEFT JOIN {$wpdb->postmeta} pm_esp ON p.ID = pm_esp.post_id AND pm_esp.meta_key = '_bl_especie_id'
    LEFT JOIN {$wpdb->postmeta} pm_lat ON p.ID = pm_lat.post_id AND pm_lat.meta_key = '_bl_latitud'
    LEFT JOIN {$wpdb->postmeta} pm_lng ON p.ID = pm_lng.post_id AND pm_lng.meta_key = '_bl_longitud'
    LEFT JOIN {$wpdb->postmeta} pm_cant ON p.ID = pm_cant.post_id AND pm_cant.meta_key = '_bl_cantidad'
    WHERE p.post_type = 'bl_avistamiento'
      AND p.post_status = 'publish'
      AND pm_lat.meta_value IS NOT NULL
      AND pm_lng.meta_value IS NOT NULL
    ORDER BY p.post_date DESC
    LIMIT 200
");

// Preparar datos para JS
$avistamientos_js = [];
foreach ($avistamientos_raw as $av) {
    $especie = get_post($av->especie_id);
    $especie_nombre = $especie ? $especie->post_title : __('Desconocida', 'flavor-chat-ia');

    $terms = $especie ? wp_get_post_terms($especie->ID, 'bl_categoria') : [];
    $categoria = !empty($terms) ? $terms[0]->slug : 'flora';

    $avistamientos_js[] = [
        'id' => $av->ID,
        'especie' => $especie_nombre,
        'lat' => floatval($av->latitud),
        'lng' => floatval($av->longitud),
        'cantidad' => intval($av->cantidad) ?: 1,
        'fecha' => date_i18n('j M Y', strtotime($av->post_date)),
        'categoria' => $categoria,
        'url' => get_permalink($av->ID),
    ];
}

// Centro del mapa (promedio de coordenadas o default)
$centro_lat = 40.4168;
$centro_lng = -3.7038;
if (!empty($avistamientos_js)) {
    $sum_lat = $sum_lng = 0;
    foreach ($avistamientos_js as $av) {
        $sum_lat += $av['lat'];
        $sum_lng += $av['lng'];
    }
    $centro_lat = $sum_lat / count($avistamientos_js);
    $centro_lng = $sum_lng / count($avistamientos_js);
}
?>

<div class="bl-container">
    <header class="bl-header">
        <h2><?php esc_html_e('Mapa de Avistamientos', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Explora los avistamientos registrados por la comunidad', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Estadísticas -->
    <div class="bl-stats-bar">
        <div class="bl-stat-item">
            <div class="bl-stat-item__valor"><?php echo esc_html(count($avistamientos_js)); ?></div>
            <div class="bl-stat-item__label"><?php esc_html_e('Avistamientos', 'flavor-chat-ia'); ?></div>
        </div>
        <?php foreach ($categorias as $cat_id => $cat_data) :
            $count = count(array_filter($avistamientos_js, fn($a) => $a['categoria'] === $cat_id));
        ?>
        <div class="bl-stat-item">
            <div class="bl-stat-item__valor" style="color: <?php echo esc_attr($cat_data['color']); ?>">
                <?php echo esc_html($count); ?>
            </div>
            <div class="bl-stat-item__label"><?php echo esc_html($cat_data['nombre']); ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Mapa -->
    <div class="bl-mapa-container">
        <div id="bl-mapa"
             class="bl-mapa"
             data-lat="<?php echo esc_attr($centro_lat); ?>"
             data-lng="<?php echo esc_attr($centro_lng); ?>"
             data-zoom="12"
             data-avistamientos='<?php echo wp_json_encode($avistamientos_js); ?>'>
        </div>

        <!-- Leyenda -->
        <div class="bl-mapa-leyenda">
            <?php foreach ($categorias as $cat_id => $cat_data) : ?>
            <div class="bl-mapa-leyenda__item">
                <span class="bl-mapa-leyenda__color" style="background: <?php echo esc_attr($cat_data['color']); ?>"></span>
                <span><?php echo esc_html($cat_data['nombre']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Últimos avistamientos -->
    <h3 style="margin: 2rem 0 1rem;"><?php esc_html_e('Últimos Avistamientos', 'flavor-chat-ia'); ?></h3>

    <?php if (!empty($avistamientos_js)) : ?>
    <div class="bl-avistamientos-lista">
        <?php foreach (array_slice($avistamientos_js, 0, 10) as $av) :
            $especie_post = get_posts(['post_type' => 'bl_especie', 'title' => $av['especie'], 'posts_per_page' => 1]);
            $cat_data = $categorias[$av['categoria']] ?? ['color' => '#6b7280'];
        ?>
        <article class="bl-avistamiento-item">
            <div class="bl-avistamiento-item__imagen" style="border: 3px solid <?php echo esc_attr($cat_data['color']); ?>">
                <span class="dashicons dashicons-visibility" style="color: <?php echo esc_attr($cat_data['color']); ?>"></span>
            </div>
            <div class="bl-avistamiento-item__body">
                <div class="bl-avistamiento-item__especie"><?php echo esc_html($av['especie']); ?></div>
                <div class="bl-avistamiento-item__meta">
                    <span><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html($av['fecha']); ?></span>
                    <span><span class="dashicons dashicons-groups"></span> <?php echo esc_html($av['cantidad']); ?> <?php esc_html_e('ejemplar(es)', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <div class="bl-empty-state">
        <span class="dashicons dashicons-location-alt"></span>
        <p><?php esc_html_e('Aún no hay avistamientos registrados.', 'flavor-chat-ia'); ?></p>
        <?php if (is_user_logged_in()) : ?>
        <a href="<?php echo esc_url(home_url('/biodiversidad/registrar/')); ?>" class="bl-btn bl-btn--primary">
            <?php esc_html_e('Registrar el primero', 'flavor-chat-ia'); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
