<?php
/**
 * Template: Catálogo de cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

$resultado = $module->action_catalogo_cursos([
    'categoria' => $atts['categoria'] ?? '',
    'nivel' => $atts['nivel'] ?? '',
    'modalidad' => $atts['modalidad'] ?? '',
]);

$cursos = $resultado['success'] ? $resultado['cursos'] : [];
$settings = $module->get_settings();
$categorias = $settings['categorias'] ?? [];
?>

<div class="cursos-catalogo">
    <div class="cursos-filtros">
        <div class="filtro-grupo">
            <label><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="filtro_categoria">
                <option value=""><?php _e('Todas las categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($categorias as $slug => $nombre): ?>
                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($atts['categoria'], $slug); ?>>
                        <?php echo esc_html($nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro-grupo">
            <label><?php _e('Nivel', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="filtro_nivel">
                <option value=""><?php _e('Todos los niveles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="principiante" <?php selected($atts['nivel'], 'principiante'); ?>><?php _e('Principiante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="intermedio" <?php selected($atts['nivel'], 'intermedio'); ?>><?php _e('Intermedio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="avanzado" <?php selected($atts['nivel'], 'avanzado'); ?>><?php _e('Avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>

        <div class="filtro-grupo">
            <label><?php _e('Modalidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select name="filtro_modalidad">
                <option value=""><?php _e('Todas las modalidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="online" <?php selected($atts['modalidad'], 'online'); ?>><?php _e('Online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="presencial" <?php selected($atts['modalidad'], 'presencial'); ?>><?php _e('Presencial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="mixto" <?php selected($atts['modalidad'], 'mixto'); ?>><?php _e('Mixto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>

        <div class="filtro-grupo">
            <label><?php _e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <input type="text" name="filtro_busqueda" placeholder="<?php esc_attr_e('Buscar cursos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </div>
    </div>

    <?php if (empty($cursos)): ?>
        <div class="cursos-vacio">
            <span class="dashicons dashicons-welcome-learn-more"></span>
            <p><?php _e('No hay cursos disponibles con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php else: ?>
        <div class="cursos-grid" style="--columnas: <?php echo intval($atts['columnas']); ?>">
            <?php foreach (array_slice($cursos, 0, intval($atts['limite'])) as $curso): ?>
                <div class="curso-card">
                    <div class="curso-card-imagen">
                        <?php if ($curso['imagen']): ?>
                            <img src="<?php echo esc_url($curso['imagen']); ?>" alt="<?php echo esc_attr($curso['titulo']); ?>">
                        <?php else: ?>
                            <img src="https://placehold.co/400x225/e5e7eb/6b7280?text=<?php echo urlencode($curso['titulo']); ?>" alt="">
                        <?php endif; ?>

                        <?php if ($curso['es_gratuito']): ?>
                            <span class="curso-badge gratuito"><?php _e('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>

                        <?php if ($curso['destacado']): ?>
                            <span class="curso-badge destacado"><?php _e('Destacado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="curso-card-contenido">
                        <div class="curso-card-categoria">
                            <?php echo esc_html($categorias[$curso['categoria']] ?? $curso['categoria'] ?? __('General', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </div>

                        <h3 class="curso-card-titulo">
                            <a href="<?php echo esc_url(add_query_arg('curso_id', $curso['id'], get_permalink())); ?>">
                                <?php echo esc_html($curso['titulo']); ?>
                            </a>
                        </h3>

                        <div class="curso-card-instructor">
                            <span><?php printf(__('Por %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($curso['instructor'])); ?></span>
                        </div>

                        <div class="curso-card-meta">
                            <span>
                                <span class="dashicons dashicons-clock"></span>
                                <?php printf(__('%dh', FLAVOR_PLATFORM_TEXT_DOMAIN), $curso['duracion_horas']); ?>
                            </span>
                            <span>
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php printf(__('%d alumnos', FLAVOR_PLATFORM_TEXT_DOMAIN), $curso['alumnos']); ?>
                            </span>
                            <span>
                                <span class="dashicons dashicons-chart-bar"></span>
                                <?php echo esc_html(ucfirst($curso['nivel'])); ?>
                            </span>
                        </div>
                    </div>

                    <div class="curso-card-footer">
                        <span class="curso-card-precio <?php echo $curso['es_gratuito'] ? 'gratuito' : ''; ?>">
                            <?php echo $curso['es_gratuito'] ? __('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN) : number_format($curso['precio'], 2) . ' €'; ?>
                        </span>
                        <div class="curso-card-valoracion">
                            <span class="dashicons dashicons-star-filled"></span>
                            <span><?php echo number_format($curso['valoracion'], 1); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
