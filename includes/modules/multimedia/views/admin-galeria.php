<?php
/**
 * Vista Admin: Galeria Multimedia
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
$tabla_albumes = $wpdb->prefix . 'flavor_albumes';

// Filtros
$filtro_tipo = sanitize_text_field($_GET['tipo'] ?? '');
$filtro_estado = sanitize_text_field($_GET['estado'] ?? '');
$busqueda = sanitize_text_field($_GET['s'] ?? '');
$paginacion = max(1, intval($_GET['paged'] ?? 1));
$por_pagina = 20;

// Construir query
$where = ['1=1'];
$params = [];

if ($filtro_tipo) {
    $where[] = 'tipo = %s';
    $params[] = $filtro_tipo;
}

if ($filtro_estado) {
    $where[] = 'estado = %s';
    $params[] = $filtro_estado;
}

if ($busqueda) {
    $where[] = '(titulo LIKE %s OR descripcion LIKE %s)';
    $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
    $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
}

$total = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_multimedia WHERE " . implode(' AND ', $where),
    ...$params
));

$offset = ($paginacion - 1) * $por_pagina;
$archivos = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_multimedia WHERE " . implode(' AND ', $where) . " ORDER BY fecha_subida DESC LIMIT %d OFFSET %d",
    array_merge($params, [$por_pagina, $offset])
));

$total_paginas = ceil($total / $por_pagina);
?>

<div class="wrap flavor-admin-galeria">
    <h1 class="wp-heading-inline"><?php _e('Galeria Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=multimedia-subir'); ?>" class="page-title-action"><?php _e('Subir nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <form method="get" class="alignleft actions">
            <input type="hidden" name="page" value="multimedia-galeria">

            <select name="tipo">
                <option value=""><?php _e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="imagen" <?php selected($filtro_tipo, 'imagen'); ?>><?php _e('Imagenes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="video" <?php selected($filtro_tipo, 'video'); ?>><?php _e('Videos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="audio" <?php selected($filtro_tipo, 'audio'); ?>><?php _e('Audio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="documento" <?php selected($filtro_tipo, 'documento'); ?>><?php _e('Documentos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <select name="estado">
                <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="publicado" <?php selected($filtro_estado, 'publicado'); ?>><?php _e('Publicados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="pendiente" <?php selected($filtro_estado, 'pendiente'); ?>><?php _e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="rechazado" <?php selected($filtro_estado, 'rechazado'); ?>><?php _e('Rechazados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php esc_attr_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <input type="submit" class="button" value="<?php esc_attr_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s elemento', '%s elementos', $total, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total)); ?>
            </span>
            <?php if ($total_paginas > 1): ?>
            <span class="pagination-links">
                <?php if ($paginacion > 1): ?>
                <a class="prev-page button" href="<?php echo esc_url(add_query_arg('paged', $paginacion - 1)); ?>">
                    <span class="screen-reader-text"><?php _e('Pagina anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span aria-hidden="true">&lsaquo;</span>
                </a>
                <?php endif; ?>
                <span class="paging-input">
                    <?php echo $paginacion; ?> de <span class="total-pages"><?php echo $total_paginas; ?></span>
                </span>
                <?php if ($paginacion < $total_paginas): ?>
                <a class="next-page button" href="<?php echo esc_url(add_query_arg('paged', $paginacion + 1)); ?>">
                    <span class="screen-reader-text"><?php _e('Pagina siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span aria-hidden="true">&rsaquo;</span>
                </a>
                <?php endif; ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($archivos)): ?>
    <div class="flavor-empty-state">
        <span class="dashicons dashicons-format-gallery"></span>
        <p><?php _e('No se encontraron archivos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php else: ?>

    <div class="flavor-galeria-grid">
        <?php foreach ($archivos as $archivo):
            $usuario = get_userdata($archivo->usuario_id);
        ?>
        <div class="flavor-galeria-item" data-id="<?php echo esc_attr($archivo->id); ?>">
            <div class="flavor-galeria-preview">
                <?php if ($archivo->tipo === 'imagen' && !empty($archivo->url)): ?>
                <img src="<?php echo esc_url($archivo->url); ?>" alt="<?php echo esc_attr($archivo->titulo); ?>">
                <?php else: ?>
                <span class="dashicons dashicons-<?php
                    echo $archivo->tipo === 'video' ? 'video-alt3' :
                        ($archivo->tipo === 'audio' ? 'format-audio' : 'media-document');
                ?>"></span>
                <?php endif; ?>
            </div>

            <div class="flavor-galeria-info">
                <h4><?php echo esc_html(wp_trim_words($archivo->titulo, 5)); ?></h4>
                <span class="flavor-galeria-meta">
                    <?php echo esc_html($usuario ? $usuario->display_name : '-'); ?>
                </span>
                <span class="flavor-galeria-estado estado-<?php echo esc_attr($archivo->estado ?? 'pendiente'); ?>">
                    <?php echo esc_html(ucfirst($archivo->estado ?? 'pendiente')); ?>
                </span>
            </div>

            <div class="flavor-galeria-acciones">
                <a href="<?php echo esc_url($archivo->url ?? '#'); ?>" class="button button-small" target="_blank"><?php _e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="<?php echo admin_url('admin.php?page=multimedia-editar&id=' . $archivo->id); ?>" class="button button-small"><?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<style>
.flavor-galeria-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.flavor-galeria-item {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
}
.flavor-galeria-preview {
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
}
.flavor-galeria-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}
.flavor-galeria-preview .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #999;
}
.flavor-galeria-info {
    padding: 10px;
}
.flavor-galeria-info h4 {
    margin: 0 0 5px;
    font-size: 13px;
}
.flavor-galeria-meta {
    font-size: 11px;
    color: #666;
}
.flavor-galeria-estado {
    display: inline-block;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}
.flavor-galeria-estado.estado-publicado { background: #d4edda; color: #155724; }
.flavor-galeria-estado.estado-pendiente { background: #fff3cd; color: #856404; }
.flavor-galeria-estado.estado-rechazado { background: #f8d7da; color: #721c24; }
.flavor-galeria-acciones {
    padding: 10px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 5px;
}
.flavor-empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}
.flavor-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}
</style>
