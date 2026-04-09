<?php
/**
 * Vista Gestión de Alumnos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cursos = $wpdb->prefix . 'flavor_cursos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';
$tabla_certificados = $wpdb->prefix . 'flavor_cursos_certificados';
$tabla_progreso = $wpdb->prefix . 'flavor_cursos_progreso';

// Obtener alumnos con estadísticas
$alumnos = $wpdb->get_results("
    SELECT u.ID,
           u.display_name,
           u.user_email,
           u.user_registered,
           COUNT(DISTINCT i.id) as total_inscripciones,
           COUNT(DISTINCT CASE WHEN i.estado = 'activa' THEN i.id END) as cursos_activos,
           COUNT(DISTINCT CASE WHEN i.estado = 'completada' THEN i.id END) as cursos_completados,
           AVG(i.progreso_porcentaje) as progreso_promedio,
           COUNT(DISTINCT cert.id) as certificados_obtenidos,
           SUM(i.precio_pagado) as total_pagado
    FROM {$wpdb->users} u
    INNER JOIN $tabla_inscripciones i ON u.ID = i.usuario_id
    LEFT JOIN $tabla_certificados cert ON i.id = cert.inscripcion_id
    GROUP BY u.ID
    ORDER BY total_inscripciones DESC
");

$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Alumnos', 'flavor-platform'); ?></h1>
    <hr class="wp-header-end">

    <!-- Estadísticas generales -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #3b82f6;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo count($alumnos); ?></div>
                <div class="flavor-stat-label"><?php echo esc_html__('Total Alumnos', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #10b981;">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value">
                    <?php echo number_format(array_sum(array_column($alumnos, 'cursos_activos'))); ?>
                </div>
                <div class="flavor-stat-label"><?php echo esc_html__('Inscripciones Activas', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #8b5cf6;">
                <span class="dashicons dashicons-yes"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value">
                    <?php echo number_format(array_sum(array_column($alumnos, 'cursos_completados'))); ?>
                </div>
                <div class="flavor-stat-label"><?php echo esc_html__('Cursos Completados', 'flavor-platform'); ?></div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #06b6d4;">
                <span class="dashicons dashicons-awards"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value">
                    <?php echo number_format(array_sum(array_column($alumnos, 'certificados_obtenidos'))); ?>
                </div>
                <div class="flavor-stat-label"><?php echo esc_html__('Certificados Emitidos', 'flavor-platform'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="flavor-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="cursos-alumnos">

            <div class="flavor-filters-row">
                <input type="search"
                       name="s"
                       value="<?php echo esc_attr($search); ?>"
                       placeholder="<?php echo esc_attr__('Buscar alumnos...', 'flavor-platform'); ?>"
                       class="flavor-filter-search">

                <select name="estado" class="flavor-filter-select">
                    <option value=""><?php echo esc_html__('Todos los estados', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('activa', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'activa'); ?>><?php echo esc_html__('Activos', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('completada', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'completada'); ?>><?php echo esc_html__('Completados', 'flavor-platform'); ?></option>
                    <option value="<?php echo esc_attr__('abandonada', 'flavor-platform'); ?>" <?php selected($filtro_estado, 'abandonada'); ?>><?php echo esc_html__('Abandonados', 'flavor-platform'); ?></option>
                </select>

                <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-platform'); ?></button>
            </div>
        </form>
    </div>

    <!-- Tabla de alumnos -->
    <div class="flavor-card">
        <div class="flavor-card-body">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Alumno', 'flavor-platform'); ?></th>
                        <th><?php echo esc_html__('Email', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Inscripciones', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Activos', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Completados', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Progreso', 'flavor-platform'); ?></th>
                        <th style="width: 100px;"><?php echo esc_html__('Certificados', 'flavor-platform'); ?></th>
                        <th style="width: 120px;"><?php echo esc_html__('Total Pagado', 'flavor-platform'); ?></th>
                        <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-platform'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($alumnos)): ?>
                        <?php foreach ($alumnos as $alumno): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($alumno->display_name); ?></strong>
                                    <br>
                                    <small class="flavor-text-muted">ID: <?php echo $alumno->ID; ?></small>
                                </td>
                                <td><?php echo esc_html($alumno->user_email); ?></td>
                                <td class="flavor-text-center">
                                    <strong><?php echo number_format($alumno->total_inscripciones); ?></strong>
                                </td>
                                <td class="flavor-text-center">
                                    <span class="flavor-badge flavor-badge-success">
                                        <?php echo number_format($alumno->cursos_activos); ?>
                                    </span>
                                </td>
                                <td class="flavor-text-center">
                                    <span class="flavor-badge flavor-badge-info">
                                        <?php echo number_format($alumno->cursos_completados); ?>
                                    </span>
                                </td>
                                <td class="flavor-text-center">
                                    <div class="flavor-progress-inline">
                                        <div class="flavor-progress-bar" style="width: <?php echo round($alumno->progreso_promedio); ?>%"></div>
                                    </div>
                                    <small><?php echo round($alumno->progreso_promedio); ?>%</small>
                                </td>
                                <td class="flavor-text-center">
                                    <?php echo number_format($alumno->certificados_obtenidos); ?>
                                </td>
                                <td class="flavor-text-right">
                                    <strong><?php echo number_format($alumno->total_pagado, 2); ?>€</strong>
                                </td>
                                <td>
                                    <button class="button button-small btn-ver-alumno" data-id="<?php echo $alumno->ID; ?>">
                                        <?php echo esc_html__('Ver Detalle', 'flavor-platform'); ?>
                                    </button>
                                    <button class="button button-small btn-progreso-alumno" data-id="<?php echo $alumno->ID; ?>">
                                        <?php echo esc_html__('Progreso', 'flavor-platform'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="flavor-no-data">
                                <?php echo esc_html__('No se encontraron alumnos', 'flavor-platform'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detalle Alumno -->
<div id="modal-alumno" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-content flavor-modal-large">
        <div class="flavor-modal-header">
            <h2><?php echo esc_html__('Detalle del Alumno', 'flavor-platform'); ?></h2>
            <span class="flavor-modal-close"><?php echo esc_html__('&times;', 'flavor-platform'); ?></span>
        </div>
        <div class="flavor-modal-body" id="alumno-detail-content">
            <div class="flavor-loading"><?php echo esc_html__('Cargando...', 'flavor-platform'); ?></div>
        </div>
    </div>
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
    transition: width 0.3s ease;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ver detalle alumno
    $('.btn-ver-alumno').on('click', function() {
        const alumnoId = $(this).data('id');

        $('#alumno-detail-content').html('<div class="flavor-loading">Cargando...</div>');
        $('#modal-alumno').fadeIn();

        $.post(ajaxurl, {
            action: 'flavor_get_alumno_detail',
            usuario_id: alumnoId,
            nonce: '<?php echo wp_create_nonce('flavor_cursos_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                const data = response.data;
                let html = `
                    <div class="flavor-alumno-detail">
                        <h3>${data.nombre}</h3>
                        <p><strong>Email:</strong> ${data.email}</p>

                        <h4>Inscripciones</h4>
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th>Curso</th>
                                    <th>Estado</th>
                                    <th>Progreso</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.inscripciones.map(i => `
                                    <tr>
                                        <td>${i.curso}</td>
                                        <td><span class="flavor-badge flavor-badge-${i.estado === 'activa' ? 'success' : 'info'}">${i.estado}</span></td>
                                        <td>${i.progreso}%</td>
                                        <td>${i.fecha}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>

                        ${data.certificados.length > 0 ? `
                            <h4>Certificados</h4>
                            <ul>
                                ${data.certificados.map(c => `<li>${c.curso} - ${c.fecha}</li>`).join('')}
                            </ul>
                        ` : ''}
                    </div>
                `;
                $('#alumno-detail-content').html(html);
            }
        });
    });

    // Ver progreso alumno
    $('.btn-progreso-alumno').on('click', function() {
        const alumnoId = $(this).data('id');
        window.location.href = '?page=cursos-alumnos&action=progreso&usuario_id=' + alumnoId;
    });

    // Cerrar modal
    $('.flavor-modal-close').on('click', function() {
        $('#modal-alumno').fadeOut();
    });
});
</script>
