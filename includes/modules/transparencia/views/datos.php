<?php
/**
 * Vista: Datos Públicos - Módulo Transparencia
 *
 * Gestión de documentos y datos publicados en el portal de transparencia.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_datos = $wpdb->prefix . 'flavor_transparencia_documentos_publicos';
$tabla_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_datos)) === $tabla_datos;

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Construir query
$where_clauses = ['1=1'];
$where_values = [];

if ($filtro_categoria) {
    $where_clauses[] = 'categoria = %s';
    $where_values[] = $filtro_categoria;
}

if ($filtro_estado) {
    $where_clauses[] = 'estado = %s';
    $where_values[] = $filtro_estado;
}

if ($filtro_busqueda) {
    $where_clauses[] = '(titulo LIKE %s OR descripcion LIKE %s)';
    $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
    $where_values[] = $busqueda_like;
    $where_values[] = $busqueda_like;
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener datos
$datos = [];
$total_items = 0;
$categorias = [];

if ($tabla_existe) {
    // Total para paginación
    $total_query = "SELECT COUNT(*) FROM $tabla_datos WHERE $where_sql";
    if (!empty($where_values)) {
        $total_items = (int) $wpdb->get_var($wpdb->prepare($total_query, $where_values));
    } else {
        $total_items = (int) $wpdb->get_var($total_query);
    }

    // Datos paginados
    $data_query = "SELECT d.*, u.display_name as autor_nombre
                   FROM $tabla_datos d
                   LEFT JOIN {$wpdb->users} u ON d.autor_id = u.ID
                   WHERE $where_sql
                   ORDER BY d.fecha_creacion DESC
                   LIMIT %d OFFSET %d";

    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $datos = $wpdb->get_results($wpdb->prepare($data_query, $query_values));

    // Categorías para filtro
    $categorias = $wpdb->get_col("SELECT DISTINCT categoria FROM $tabla_datos WHERE categoria IS NOT NULL ORDER BY categoria");
}

$total_paginas = ceil($total_items / $por_pagina);

// Estados disponibles
$estados = ['borrador', 'pendiente', 'publicado', 'archivado'];
$estado_labels = [
    'borrador' => __('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'publicado' => __('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'archivado' => __('Archivado', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
$estado_badges = [
    'borrador' => 'dm-badge--secondary',
    'pendiente' => 'dm-badge--warning',
    'publicado' => 'dm-badge--success',
    'archivado' => 'dm-badge--info',
];
?>

<div class="wrap flavor-transparencia-datos">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-media-spreadsheet"></span>
        <?php esc_html_e('Datos Públicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-publicar')); ?>" class="page-title-action">
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php esc_html_e('Publicar Nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (!$tabla_existe): ?>
        <div class="dm-alert dm-alert--warning">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php esc_html_e('Tablas no encontradas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Transparencia no están creadas. Activa el módulo para crearlas automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    <?php else: ?>

    <!-- Filtros -->
    <div class="dm-card" style="margin-bottom: 20px;">
        <form method="get" class="dm-filters">
            <input type="hidden" name="page" value="transparencia-datos">

            <div class="dm-filters__row">
                <div class="dm-filters__field">
                    <label><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php esc_attr_e('Título o descripción...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>

                <div class="dm-filters__field">
                    <label><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="categoria">
                        <option value=""><?php esc_html_e('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo esc_attr($cat); ?>" <?php selected($filtro_categoria, $cat); ?>>
                                <?php echo esc_html(ucfirst($cat)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="dm-filters__field">
                    <label><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="estado">
                        <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?php echo esc_attr($estado); ?>" <?php selected($filtro_estado, $estado); ?>>
                                <?php echo esc_html($estado_labels[$estado]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="dm-filters__actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-datos')); ?>" class="button">
                        <?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de datos -->
    <div class="dm-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 40%;"><?php esc_html_e('Documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 15%;"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 10%;"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 10%;"><?php esc_html_e('Visitas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 15%;"><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 10%;"><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($datos)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="dm-empty" style="padding: 40px;">
                                <span class="dashicons dashicons-media-spreadsheet"></span>
                                <p><?php esc_html_e('No hay datos publicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-publicar')); ?>" class="button button-primary">
                                    <?php esc_html_e('Publicar primer documento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($datos as $dato): ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-publicar&id=' . $dato->id)); ?>">
                                        <?php echo esc_html($dato->titulo); ?>
                                    </a>
                                </strong>
                                <?php if ($dato->descripcion): ?>
                                    <p class="description" style="margin: 4px 0 0;"><?php echo esc_html(wp_trim_words($dato->descripcion, 15)); ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="dm-badge dm-badge--info">
                                    <?php echo esc_html(ucfirst($dato->categoria ?: __('Sin categoría', FLAVOR_PLATFORM_TEXT_DOMAIN))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($estado_badges[$dato->estado] ?? 'dm-badge--secondary'); ?>">
                                    <?php echo esc_html($estado_labels[$dato->estado] ?? ucfirst($dato->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <span class="dashicons dashicons-visibility" style="opacity: 0.5;"></span>
                                <?php echo number_format_i18n($dato->visitas ?? 0); ?>
                                <?php if ($dato->descargas > 0): ?>
                                    <br>
                                    <span class="dashicons dashicons-download" style="opacity: 0.5;"></span>
                                    <?php echo number_format_i18n($dato->descargas); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dato->fecha_publicacion): ?>
                                    <?php echo esc_html(date_i18n('d/m/Y', strtotime($dato->fecha_publicacion))); ?>
                                <?php else: ?>
                                    <span class="description"><?php echo esc_html(date_i18n('d/m/Y', strtotime($dato->fecha_creacion))); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="row-actions visible">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-publicar&id=' . $dato->id)); ?>" title="<?php esc_attr_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <?php if ($dato->archivo_url): ?>
                                        <a href="<?php echo esc_url($dato->archivo_url); ?>" target="_blank" title="<?php esc_attr_e('Ver archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-external"></span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(
                            esc_html(_n('%s elemento', '%s elementos', $total_items, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                            number_format_i18n($total_items)
                        ); ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        $paginate_args = [
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_paginas,
                            'current' => $pagina_actual,
                        ];
                        echo paginate_links($paginate_args);
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<style>
.flavor-transparencia-datos .dm-filters {
    padding: 15px 20px;
}
.flavor-transparencia-datos .dm-filters__row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}
.flavor-transparencia-datos .dm-filters__field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.flavor-transparencia-datos .dm-filters__field label {
    font-weight: 600;
    font-size: 12px;
    color: #666;
}
.flavor-transparencia-datos .dm-filters__field input,
.flavor-transparencia-datos .dm-filters__field select {
    min-width: 180px;
}
.flavor-transparencia-datos .dm-filters__actions {
    display: flex;
    gap: 8px;
}
.flavor-transparencia-datos .dm-filters__actions .dashicons {
    margin-right: 4px;
}
.flavor-transparencia-datos .row-actions .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    margin-right: 8px;
    color: #2271b1;
}
.flavor-transparencia-datos .row-actions .dashicons:hover {
    color: #135e96;
}
</style>
