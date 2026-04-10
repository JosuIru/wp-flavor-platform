<?php
/**
 * Vista Admin: Gestion de Grupos de Chat
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
$tabla_miembros = $wpdb->prefix . 'flavor_chat_grupos_miembros';
$tabla_mensajes = $wpdb->prefix . 'flavor_chat_grupos_mensajes';

// Filtros
$filtro_estado = sanitize_text_field($_GET['estado'] ?? '');
$busqueda = sanitize_text_field($_GET['s'] ?? '');
$paginacion = max(1, intval($_GET['paged'] ?? 1));
$por_pagina = 20;

// Construir query
$where = ['1=1'];
$params = [];

if ($filtro_estado) {
    $where[] = 'estado = %s';
    $params[] = $filtro_estado;
}

if ($busqueda) {
    $where[] = '(nombre LIKE %s OR descripcion LIKE %s)';
    $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
    $params[] = '%' . $wpdb->esc_like($busqueda) . '%';
}

$total = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_grupos WHERE " . implode(' AND ', $where),
    ...$params
));

$offset = ($paginacion - 1) * $por_pagina;
$grupos = $wpdb->get_results($wpdb->prepare(
    "SELECT g.*,
        (SELECT COUNT(*) FROM $tabla_miembros WHERE grupo_id = g.id) as miembros_count,
        (SELECT COUNT(*) FROM $tabla_mensajes WHERE grupo_id = g.id) as mensajes_count
     FROM $tabla_grupos g
     WHERE " . implode(' AND ', $where) . "
     ORDER BY g.fecha_creacion DESC
     LIMIT %d OFFSET %d",
    array_merge($params, [$por_pagina, $offset])
));

$total_paginas = ceil($total / $por_pagina);
?>

<div class="wrap flavor-chat-grupos-admin">
    <h1 class="wp-heading-inline"><?php _e('Grupos de Chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=chat-grupos-nuevo'); ?>" class="page-title-action"><?php _e('Crear grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
    <hr class="wp-header-end">

    <div class="tablenav top">
        <form method="get" class="alignleft actions">
            <input type="hidden" name="page" value="chat-grupos">

            <select name="estado">
                <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="activo" <?php selected($filtro_estado, 'activo'); ?>><?php _e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="inactivo" <?php selected($filtro_estado, 'inactivo'); ?>><?php _e('Inactivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="archivado" <?php selected($filtro_estado, 'archivado'); ?>><?php _e('Archivados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <input type="search" name="s" value="<?php echo esc_attr($busqueda); ?>" placeholder="<?php esc_attr_e('Buscar grupos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <input type="submit" class="button" value="<?php esc_attr_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        </form>

        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(_n('%s grupo', '%s grupos', $total, FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total)); ?>
            </span>
        </div>
    </div>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-primary"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th scope="col"><?php _e('Creado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($grupos)): ?>
            <tr>
                <td colspan="6"><?php _e('No se encontraron grupos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
            </tr>
            <?php else: ?>
            <?php foreach ($grupos as $grupo): ?>
            <tr>
                <td class="column-primary">
                    <strong>
                        <a href="<?php echo admin_url('admin.php?page=chat-grupos-editar&id=' . $grupo->id); ?>">
                            <?php echo esc_html($grupo->nombre); ?>
                        </a>
                    </strong>
                    <div class="row-actions">
                        <span class="edit">
                            <a href="<?php echo admin_url('admin.php?page=chat-grupos-editar&id=' . $grupo->id); ?>"><?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a> |
                        </span>
                        <span class="view">
                            <a href="<?php echo admin_url('admin.php?page=chat-grupos-miembros&id=' . $grupo->id); ?>"><?php _e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a> |
                        </span>
                        <span class="trash">
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=chat-grupos&action=archivar&id=' . $grupo->id), 'archivar_grupo_' . $grupo->id); ?>" class="submitdelete"><?php _e('Archivar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                        </span>
                    </div>
                </td>
                <td><?php echo esc_html(ucfirst($grupo->tipo ?? 'publico')); ?></td>
                <td><?php echo number_format_i18n($grupo->miembros_count); ?></td>
                <td><?php echo number_format_i18n($grupo->mensajes_count); ?></td>
                <td>
                    <span class="flavor-estado estado-<?php echo esc_attr($grupo->estado ?? 'activo'); ?>">
                        <?php echo esc_html(ucfirst($grupo->estado ?? 'activo')); ?>
                    </span>
                </td>
                <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($grupo->fecha_creacion))); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
.flavor-estado {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}
.flavor-estado.estado-activo { background: #d4edda; color: #155724; }
.flavor-estado.estado-inactivo { background: #fff3cd; color: #856404; }
.flavor-estado.estado-archivado { background: #e2e3e5; color: #383d41; }
</style>
