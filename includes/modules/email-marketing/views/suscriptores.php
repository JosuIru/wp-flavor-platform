<?php
/**
 * Vista: Suscriptores de Email Marketing
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Parámetros de filtrado
$pagina = isset($_GET['pag']) ? absint($_GET['pag']) : 1;
$por_pagina = 20;
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$lista_filtro = isset($_GET['lista']) ? absint($_GET['lista']) : 0;
$estado_filtro = isset($_GET['estado']) ? sanitize_key($_GET['estado']) : '';

$offset = ($pagina - 1) * $por_pagina;

// Construir query
$where = ['1=1'];
$params = [];

if ($buscar) {
    $where[] = "(s.email LIKE %s OR s.nombre LIKE %s)";
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = '%' . $wpdb->esc_like($buscar) . '%';
}

if ($estado_filtro) {
    $where[] = "s.estado = %s";
    $params[] = $estado_filtro;
}

$join = '';
if ($lista_filtro) {
    $join = "INNER JOIN {$wpdb->prefix}flavor_em_suscriptor_lista sl ON s.id = sl.suscriptor_id";
    $where[] = "sl.lista_id = %d AND sl.estado = 'activo'";
    $params[] = $lista_filtro;
}

$where_sql = implode(' AND ', $where);

// Contar total
$count_query = "SELECT COUNT(DISTINCT s.id) FROM {$wpdb->prefix}flavor_em_suscriptores s $join WHERE $where_sql";
$total = $params ? $wpdb->get_var($wpdb->prepare($count_query, ...$params)) : $wpdb->get_var($count_query);

// Obtener suscriptores
$query_params = array_merge($params, [$por_pagina, $offset]);
$query = "SELECT DISTINCT s.* FROM {$wpdb->prefix}flavor_em_suscriptores s $join WHERE $where_sql ORDER BY s.creado_en DESC LIMIT %d OFFSET %d";
$suscriptores = $wpdb->get_results($wpdb->prepare($query, ...$query_params));

// Obtener listas para filtro
$listas = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}flavor_em_listas WHERE activa = 1 ORDER BY nombre ASC");

$total_paginas = ceil($total / $por_pagina);
?>

<div class="wrap em-suscriptores">
    <h1>
        <?php _e('Suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        <button type="button" class="page-title-action em-btn-importar"><?php _e('Importar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        <button type="button" class="page-title-action em-btn-exportar"><?php _e('Exportar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </h1>

    <!-- Filtros -->
    <div class="em-filtros">
        <form method="get">
            <input type="hidden" name="page" value="flavor-em-suscriptores">

            <div class="em-filtro-grupo">
                <input type="search" name="s" placeholder="<?php esc_attr_e('Buscar por email o nombre...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                       value="<?php echo esc_attr($buscar); ?>">
            </div>

            <div class="em-filtro-grupo">
                <select name="lista">
                    <option value=""><?php _e('Todas las listas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($listas as $lista): ?>
                        <option value="<?php echo esc_attr($lista->id); ?>" <?php selected($lista_filtro, $lista->id); ?>>
                            <?php echo esc_html($lista->nombre); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="em-filtro-grupo">
                <select name="estado">
                    <option value=""><?php _e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="activo" <?php selected($estado_filtro, 'activo'); ?>><?php _e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="pendiente" <?php selected($estado_filtro, 'pendiente'); ?>><?php _e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="baja" <?php selected($estado_filtro, 'baja'); ?>><?php _e('Baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="rebotado" <?php selected($estado_filtro, 'rebotado'); ?>><?php _e('Rebotado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </div>

            <button type="submit" class="button"><?php _e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

            <?php if ($buscar || $lista_filtro || $estado_filtro): ?>
                <a href="<?php echo admin_url('admin.php?page=flavor-em-suscriptores'); ?>" class="button">
                    <?php _e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="em-stats-bar">
        <span>
            <strong><?php echo number_format($total); ?></strong>
            <?php _e('suscriptores encontrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </span>
    </div>

    <?php if (empty($suscriptores)): ?>
        <div class="em-empty-state">
            <span class="dashicons dashicons-groups"></span>
            <h2><?php _e('No hay suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p><?php _e('Importa tu lista de suscriptores o añádelos manualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <button type="button" class="button button-primary button-hero em-btn-importar">
                <?php _e('Importar suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped em-table-suscriptores">
            <thead>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" id="em-select-all">
                    </td>
                    <th class="column-email"><?php _e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th class="column-nombre"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th class="column-estado"><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th class="column-listas"><?php _e('Listas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th class="column-stats"><?php _e('Estadísticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th class="column-fecha"><?php _e('Suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suscriptores as $suscriptor): ?>
                    <?php
                    // Obtener listas del suscriptor
                    $sus_listas = $wpdb->get_results($wpdb->prepare(
                        "SELECT l.nombre FROM {$wpdb->prefix}flavor_em_listas l
                         INNER JOIN {$wpdb->prefix}flavor_em_suscriptor_lista sl ON l.id = sl.lista_id
                         WHERE sl.suscriptor_id = %d AND sl.estado = 'activo'",
                        $suscriptor->id
                    ));
                    ?>
                    <tr data-id="<?php echo esc_attr($suscriptor->id); ?>">
                        <th class="check-column">
                            <input type="checkbox" name="suscriptor_ids[]" value="<?php echo esc_attr($suscriptor->id); ?>">
                        </th>
                        <td class="column-email">
                            <strong>
                                <a href="#" class="em-ver-suscriptor" data-id="<?php echo esc_attr($suscriptor->id); ?>">
                                    <?php echo esc_html($suscriptor->email); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="#" class="em-editar-suscriptor" data-id="<?php echo esc_attr($suscriptor->id); ?>">
                                        <?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a> |
                                </span>
                                <span class="delete">
                                    <a href="#" class="em-eliminar-suscriptor" data-id="<?php echo esc_attr($suscriptor->id); ?>">
                                        <?php _e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="column-nombre">
                            <?php
                            $nombre_completo = trim($suscriptor->nombre . ' ' . $suscriptor->apellidos);
                            echo $nombre_completo ? esc_html($nombre_completo) : '-';
                            ?>
                        </td>
                        <td class="column-estado">
                            <span class="em-badge em-badge-<?php echo esc_attr($suscriptor->estado); ?>">
                                <?php echo esc_html(ucfirst($suscriptor->estado)); ?>
                            </span>
                        </td>
                        <td class="column-listas">
                            <?php if ($sus_listas): ?>
                                <?php foreach ($sus_listas as $l): ?>
                                    <span class="em-tag"><?php echo esc_html($l->nombre); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td class="column-stats">
                            <span title="<?php esc_attr_e('Emails enviados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-email"></span>
                                <?php echo $suscriptor->total_emails_enviados; ?>
                            </span>
                            <span title="<?php esc_attr_e('Aperturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php echo $suscriptor->total_abiertos; ?>
                            </span>
                            <span title="<?php esc_attr_e('Clicks', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                <span class="dashicons dashicons-admin-links"></span>
                                <?php echo $suscriptor->total_clicks; ?>
                            </span>
                        </td>
                        <td class="column-fecha">
                            <?php echo date_i18n('d M Y', strtotime($suscriptor->creado_en)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="em-paginacion">
                <?php if ($pagina > 1): ?>
                    <a href="<?php echo esc_url(add_query_arg('pag', $pagina - 1)); ?>" class="button">
                        &laquo; <?php _e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>

                <span class="em-paginacion-info">
                    <?php printf(__('Página %d de %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $pagina, $total_paginas); ?>
                </span>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="<?php echo esc_url(add_query_arg('pag', $pagina + 1)); ?>" class="button">
                        <?php _e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Modal de importación -->
<div class="em-modal" id="em-modal-importar" style="display:none;">
    <div class="em-modal-content em-modal-lg">
        <h3><?php _e('Importar suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <form id="em-form-importar" enctype="multipart/form-data">
            <div class="em-form-section">
                <label><?php _e('Archivo CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <input type="file" name="archivo" accept=".csv" required>
                <p class="description"><?php _e('Columnas requeridas: email. Opcionales: nombre, apellidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="em-form-section">
                <label><?php _e('Añadir a lista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <select name="lista_id" required>
                    <?php foreach ($listas as $lista): ?>
                        <option value="<?php echo esc_attr($lista->id); ?>"><?php echo esc_html($lista->nombre); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="em-form-section">
                <label>
                    <input type="checkbox" name="actualizar_existentes" value="1">
                    <?php _e('Actualizar suscriptores existentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>

            <div class="em-modal-actions">
                <button type="button" class="button em-modal-close"><?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="submit" class="button button-primary"><?php _e('Importar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de detalle suscriptor -->
<div class="em-modal" id="em-modal-suscriptor" style="display:none;">
    <div class="em-modal-content em-modal-lg">
        <div class="em-modal-loading"><?php _e('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        <div class="em-suscriptor-detalle"></div>
    </div>
</div>
