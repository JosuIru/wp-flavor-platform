<?php
/**
 * Vista Gestión de Matrículas/Inscripciones
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cursos = $wpdb->prefix . 'flavor_cursos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

// Parámetros de filtrado
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_curso = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;

$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 30;
$offset = ($paged - 1) * $per_page;

// Construir query
$where = ['1=1'];
$prepare_values = [];

if (!empty($search)) {
    $where[] = '(u.display_name LIKE %s OR c.titulo LIKE %s)';
    $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
    $prepare_values[] = '%' . $wpdb->esc_like($search) . '%';
}

if (!empty($filtro_estado)) {
    $where[] = 'i.estado = %s';
    $prepare_values[] = $filtro_estado;
}

if ($filtro_curso > 0) {
    $where[] = 'i.curso_id = %d';
    $prepare_values[] = $filtro_curso;
}

$where_sql = implode(' AND ', $where);

// Total registros
$total_items = $wpdb->get_var(
    empty($prepare_values)
        ? "SELECT COUNT(*) FROM $tabla_inscripciones i WHERE $where_sql"
        : $wpdb->prepare("SELECT COUNT(*) FROM $tabla_inscripciones i WHERE $where_sql", ...$prepare_values)
);

$total_pages = ceil($total_items / $per_page);

// Obtener inscripciones
$query = "SELECT i.*,
                 c.titulo as curso_titulo,
                 u.display_name as alumno_nombre,
                 u.user_email as alumno_email,
                 inst.display_name as instructor_nombre
          FROM $tabla_inscripciones i
          INNER JOIN $tabla_cursos c ON i.curso_id = c.id
          INNER JOIN {$wpdb->users} u ON i.usuario_id = u.ID
          LEFT JOIN {$wpdb->users} inst ON c.instructor_id = inst.ID
          WHERE $where_sql
          ORDER BY i.created_at DESC
          LIMIT $per_page OFFSET $offset";

$inscripciones = empty($prepare_values)
    ? $wpdb->get_results($query)
    : $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));

// Obtener cursos para filtro
$cursos_para_filtro = $wpdb->get_results(
    "SELECT id, titulo FROM $tabla_cursos ORDER BY titulo"
);

// Estadísticas rápidas
$stats_hoy = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_inscripciones WHERE DATE(created_at) = CURDATE()");
$stats_semana = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_inscripciones WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_inscripciones WHERE MONTH(created_at) = MONTH(CURDATE())");

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Matrículas', 'flavor-platform'); ?></h1>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #3b82f6;">
                <span class="dashicons dashicons-calendar"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($stats_hoy); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Hoy', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #10b981;">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($stats_semana); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Esta Semana', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #8b5cf6;">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo number_format($stats_mes); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Este Mes', 'flavor-platform'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="flavor-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-chat-cursos', 'flavor-platform'); ?>">
            <input type="hidden" name="tab" value="<?php echo esc_attr__('matriculas', 'flavor-platform'); ?>">

            <div class="flavor-filters-row">
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php echo esc_attr__('Buscar alumno o curso...', 'flavor-platform'); ?>"
                       class="flavor-filter-search">

                <select name="curso_id" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todos los cursos', 'flavor-platform'); ?></option>
                    <?php foreach ($cursos_para_filtro as $curso): ?>
                        <option value="<?php echo $curso->id; ?>" <?php selected($filtro_curso, $curso->id); ?>>
                            <?php echo esc_html($curso->titulo); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="estado" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todos los estados', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('activa', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'activa'); ?>><?php echo esc_html__('Activa', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('completada', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'completada'); ?>><?php echo esc_html__('Completada', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('abandonada', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'abandonada'); ?>><?php echo esc_html__('Abandonada', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('suspendida', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'suspendida'); ?>><?php echo esc_html__('Suspendida', 'flavor-platform'); ?></option>
                </select>

                <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-platform'); ?></button>
                <?php if ($search || $filtro_estado || $filtro_curso): ?>
                    <a href="?page=flavor-chat-cursos&tab=matriculas" class="button"><?php echo esc_html__('Limpiar', 'flavor-platform'); ?></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabla de inscripciones -->
    <div class="flavor-card">
        <div class="flavor-card-body">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Alumno', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Curso', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Instructor', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Progreso', 'flavor-platform'); ?></th>
                        <th style="width: 80px;"><?php echo esc_html__('Precio', 'flavor-platform'); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Fecha', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($inscripciones)): ?>
                        <?php foreach ($inscripciones as $inscripcion): ?>
                            <tr>
                                <td><?php echo $inscripcion->id; ?></td>
                                <td>
                                    <strong><?php echo esc_html($inscripcion->alumno_nombre); ?></strong>
                                    <br><small class="flavor-text-muted"><?php echo esc_html($inscripcion->alumno_email); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($inscripcion->curso_titulo); ?></strong>
                                </td>
                                <td><?php echo esc_html($inscripcion->instructor_nombre); ?></td>
                                <td>
                                    <div class="flavor-progress-inline">
                                        <div class="flavor-progress-bar" style="width: <?php echo $inscripcion->progreso_porcentaje; ?>%"></div>
                                    </div>
                                    <small class="flavor-text-center"><?php echo round($inscripcion->progreso_porcentaje); ?>%</small>
                                </td>
                                <td class="flavor-text-right">
                                    <?php if ($inscripcion->precio_pagado > 0): ?>
                                        <?php echo number_format($inscripcion->precio_pagado, 2); ?>€
                                    <?php else: ?>
                                        <span class="flavor-text-success"><?php echo esc_html__('Gratis', 'flavor-platform'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($inscripcion->created_at)); ?>
                                    <br>
                                    <small class="flavor-text-muted">
                                        <?php echo date('H:i', strtotime($inscripcion->created_at)); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="flavor-badge flavor-badge-<?php
                                        echo $inscripcion->estado === 'activa' ? 'success' :
                                            ($inscripcion->estado === 'completada' ? 'info' :
                                            ($inscripcion->estado === 'abandonada' ? 'warning' : 'danger'));
                                    ?>">
                                        <?php echo ucfirst($inscripcion->estado); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button button-small btn-ver-inscripcion" data-id="<?php echo $inscripcion->id; ?>">
                                        <?php echo esc_html__('Ver', 'flavor-platform'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="flavor-no-data">
                                <?php echo esc_html__('No se encontraron inscripciones', 'flavor-platform'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo number_format($total_items); ?> elementos</span>
                <?php
                $page_links = paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $paged
                ]);
                echo $page_links;
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>

.flavor-progress-inline {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 4px;
}

.flavor-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981, #059669);
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ver detalle inscripción
    $('.btn-ver-inscripcion').on('click', function() {
        const inscripcionId = $(this).data('id');
        window.location.href = '?page=flavor-chat-cursos&tab=matriculas&action=detalle&inscripcion_id=' + inscripcionId;
    });
});
</script>
