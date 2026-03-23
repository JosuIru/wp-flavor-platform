<?php
/**
 * Vista: Solicitudes de Información - Módulo Transparencia
 *
 * Gestión de solicitudes de acceso a información pública.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_solicitudes = $wpdb->prefix . 'flavor_transparencia_solicitudes';
$tabla_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_solicitudes)) === $tabla_solicitudes;

// Paginación
$por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $por_pagina;

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Construir query
$where_clauses = ['1=1'];
$where_values = [];

if ($filtro_estado) {
    $where_clauses[] = 'estado = %s';
    $where_values[] = $filtro_estado;
}

if ($filtro_busqueda) {
    $where_clauses[] = '(titulo LIKE %s OR descripcion LIKE %s OR numero_registro LIKE %s)';
    $busqueda_like = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
    $where_values[] = $busqueda_like;
    $where_values[] = $busqueda_like;
    $where_values[] = $busqueda_like;
}

$where_sql = implode(' AND ', $where_clauses);

// Obtener datos
$solicitudes = [];
$total_items = 0;
$estadisticas = [];

if ($tabla_existe) {
    // Total para paginación
    $total_query = "SELECT COUNT(*) FROM $tabla_solicitudes WHERE $where_sql";
    if (!empty($where_values)) {
        $total_items = (int) $wpdb->get_var($wpdb->prepare($total_query, $where_values));
    } else {
        $total_items = (int) $wpdb->get_var($total_query);
    }

    // Solicitudes paginadas
    $data_query = "SELECT s.*, u.display_name as solicitante_nombre, u.user_email as solicitante_email
                   FROM $tabla_solicitudes s
                   LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
                   WHERE $where_sql
                   ORDER BY s.fecha_solicitud DESC
                   LIMIT %d OFFSET %d";

    $query_values = array_merge($where_values, [$por_pagina, $offset]);
    $solicitudes = $wpdb->get_results($wpdb->prepare($data_query, $query_values));

    // Estadísticas por estado
    $estadisticas = $wpdb->get_results(
        "SELECT estado, COUNT(*) as total FROM $tabla_solicitudes GROUP BY estado",
        OBJECT_K
    );
}

$total_paginas = ceil($total_items / $por_pagina);

// Estados disponibles
$estados = ['recibida', 'en_tramite', 'resuelta', 'denegada', 'archivada'];
$estado_labels = [
    'recibida' => __('Recibida', 'flavor-chat-ia'),
    'en_tramite' => __('En Trámite', 'flavor-chat-ia'),
    'resuelta' => __('Resuelta', 'flavor-chat-ia'),
    'denegada' => __('Denegada', 'flavor-chat-ia'),
    'archivada' => __('Archivada', 'flavor-chat-ia'),
];
$estado_badges = [
    'recibida' => 'dm-badge--warning',
    'en_tramite' => 'dm-badge--info',
    'resuelta' => 'dm-badge--success',
    'denegada' => 'dm-badge--error',
    'archivada' => 'dm-badge--secondary',
];
?>

<div class="wrap flavor-transparencia-solicitudes">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-clipboard"></span>
        <?php esc_html_e('Solicitudes de Información', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if (!$tabla_existe): ?>
        <div class="dm-alert dm-alert--warning">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php esc_html_e('Tablas no encontradas', 'flavor-chat-ia'); ?></strong>
                <p><?php esc_html_e('Las tablas del módulo Transparencia no están creadas.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    <?php else: ?>

    <!-- Resumen por estados -->
    <div class="dm-stats-grid dm-stats-grid--5" style="margin-bottom: 20px;">
        <?php foreach ($estados as $estado): ?>
            <?php
            $total_estado = isset($estadisticas[$estado]) ? (int) $estadisticas[$estado]->total : 0;
            $activo = ($filtro_estado === $estado) ? 'dm-stat-card--active' : '';
            ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-solicitudes&estado=' . $estado)); ?>"
               class="dm-stat-card dm-stat-card--clickable <?php echo esc_attr($activo); ?>" style="text-decoration: none;">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_estado); ?></div>
                <div class="dm-stat-card__label"><?php echo esc_html($estado_labels[$estado]); ?></div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="dm-card" style="margin-bottom: 20px;">
        <form method="get" class="dm-filters">
            <input type="hidden" name="page" value="transparencia-solicitudes">

            <div class="dm-filters__row">
                <div class="dm-filters__field">
                    <label><?php esc_html_e('Buscar', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>" placeholder="<?php esc_attr_e('Nº registro, título...', 'flavor-chat-ia'); ?>">
                </div>

                <div class="dm-filters__field">
                    <label><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                    <select name="estado">
                        <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
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
                        <?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-solicitudes')); ?>" class="button">
                        <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de solicitudes -->
    <div class="dm-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 10%;"><?php esc_html_e('Nº Registro', 'flavor-chat-ia'); ?></th>
                    <th style="width: 30%;"><?php esc_html_e('Solicitud', 'flavor-chat-ia'); ?></th>
                    <th style="width: 15%;"><?php esc_html_e('Solicitante', 'flavor-chat-ia'); ?></th>
                    <th style="width: 10%;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th style="width: 12%;"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th style="width: 12%;"><?php esc_html_e('Plazo', 'flavor-chat-ia'); ?></th>
                    <th style="width: 11%;"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($solicitudes)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="dm-empty" style="padding: 40px;">
                                <span class="dashicons dashicons-clipboard"></span>
                                <p><?php esc_html_e('No hay solicitudes registradas.', 'flavor-chat-ia'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($solicitudes as $solicitud): ?>
                        <?php
                        // Calcular días restantes del plazo
                        $dias_plazo = 30; // Configuración por defecto
                        $fecha_limite = strtotime($solicitud->fecha_solicitud . ' + ' . $dias_plazo . ' days');
                        $dias_restantes = ceil(($fecha_limite - time()) / (60 * 60 * 24));
                        $plazo_vencido = $dias_restantes < 0 && !in_array($solicitud->estado, ['resuelta', 'denegada', 'archivada']);
                        ?>
                        <tr class="<?php echo $plazo_vencido ? 'row-plazo-vencido' : ''; ?>">
                            <td>
                                <strong>#<?php echo esc_html($solicitud->numero_registro ?: $solicitud->id); ?></strong>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-solicitud&id=' . $solicitud->id)); ?>">
                                    <strong><?php echo esc_html(wp_trim_words($solicitud->titulo ?? __('Sin título', 'flavor-chat-ia'), 10)); ?></strong>
                                </a>
                                <?php if ($solicitud->descripcion): ?>
                                    <p class="description" style="margin: 4px 0 0;"><?php echo esc_html(wp_trim_words($solicitud->descripcion, 12)); ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($solicitud->solicitante_nombre): ?>
                                    <?php echo esc_html($solicitud->solicitante_nombre); ?>
                                    <br><small class="description"><?php echo esc_html($solicitud->solicitante_email); ?></small>
                                <?php else: ?>
                                    <em><?php esc_html_e('Anónimo', 'flavor-chat-ia'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($estado_badges[$solicitud->estado] ?? 'dm-badge--secondary'); ?>">
                                    <?php echo esc_html($estado_labels[$solicitud->estado] ?? ucfirst($solicitud->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html(date_i18n('d/m/Y', strtotime($solicitud->fecha_solicitud))); ?>
                                <br><small class="description"><?php echo esc_html(human_time_diff(strtotime($solicitud->fecha_solicitud), current_time('timestamp'))); ?></small>
                            </td>
                            <td>
                                <?php if (in_array($solicitud->estado, ['resuelta', 'denegada', 'archivada'])): ?>
                                    <span class="dm-badge dm-badge--success"><?php esc_html_e('Cerrada', 'flavor-chat-ia'); ?></span>
                                <?php elseif ($plazo_vencido): ?>
                                    <span class="dm-badge dm-badge--error">
                                        <?php printf(esc_html__('Vencido hace %d días', 'flavor-chat-ia'), abs($dias_restantes)); ?>
                                    </span>
                                <?php elseif ($dias_restantes <= 5): ?>
                                    <span class="dm-badge dm-badge--warning">
                                        <?php printf(esc_html__('%d días', 'flavor-chat-ia'), $dias_restantes); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="description">
                                        <?php printf(esc_html__('%d días', 'flavor-chat-ia'), $dias_restantes); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="row-actions visible">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-solicitud&id=' . $solicitud->id)); ?>" title="<?php esc_attr_e('Ver detalle', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </a>
                                    <?php if (in_array($solicitud->estado, ['recibida', 'en_tramite'])): ?>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=transparencia-solicitud&id=' . $solicitud->id . '&action=responder')); ?>" title="<?php esc_attr_e('Responder', 'flavor-chat-ia'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
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
                            esc_html(_n('%s solicitud', '%s solicitudes', $total_items, 'flavor-chat-ia')),
                            number_format_i18n($total_items)
                        ); ?>
                    </span>
                    <span class="pagination-links">
                        <?php
                        echo paginate_links([
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_paginas,
                            'current' => $pagina_actual,
                        ]);
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<style>
.flavor-transparencia-solicitudes .dm-filters {
    padding: 15px 20px;
}
.flavor-transparencia-solicitudes .dm-filters__row {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    align-items: flex-end;
}
.flavor-transparencia-solicitudes .dm-filters__field {
    display: flex;
    flex-direction: column;
    gap: 5px;
}
.flavor-transparencia-solicitudes .dm-filters__field label {
    font-weight: 600;
    font-size: 12px;
    color: #666;
}
.flavor-transparencia-solicitudes .dm-filters__actions {
    display: flex;
    gap: 8px;
}
.flavor-transparencia-solicitudes .dm-stat-card--clickable {
    cursor: pointer;
    transition: all 0.2s;
}
.flavor-transparencia-solicitudes .dm-stat-card--clickable:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.flavor-transparencia-solicitudes .dm-stat-card--active {
    border-color: var(--dm-primary, #3b82f6);
    background: var(--dm-primary-light, #dbeafe);
}
.flavor-transparencia-solicitudes .row-plazo-vencido {
    background-color: #fef2f2 !important;
}
.flavor-transparencia-solicitudes .row-actions .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    margin-right: 8px;
    color: #2271b1;
}
</style>
