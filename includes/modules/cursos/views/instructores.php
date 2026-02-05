<?php
/**
 * Vista Gestión de Instructores
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_cursos = $wpdb->prefix . 'flavor_cursos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_cursos_inscripciones';

// Obtener instructores con estadísticas
$instructores = $wpdb->get_results("
    SELECT u.ID,
           u.display_name,
           u.user_email,
           u.user_registered,
           COUNT(DISTINCT c.id) as total_cursos,
           COUNT(DISTINCT CASE WHEN c.estado = 'en_curso' THEN c.id END) as cursos_activos,
           SUM(c.alumnos_inscritos) as total_alumnos,
           AVG(c.valoracion_media) as valoracion_promedio,
           SUM(i.precio_pagado) as ingresos_totales
    FROM {$wpdb->users} u
    LEFT JOIN $tabla_cursos c ON u.ID = c.instructor_id
    LEFT JOIN $tabla_inscripciones i ON c.id = i.curso_id
    WHERE c.id IS NOT NULL
    GROUP BY u.ID
    ORDER BY total_cursos DESC
");

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Gestión de Instructores</h1>
    <hr class="wp-header-end">

    <!-- Tarjetas resumen -->
    <div class="flavor-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));">
        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #3b82f6;">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value"><?php echo count($instructores); ?></div>
                <div class="flavor-stat-label">Total Instructores</div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #10b981;">
                <span class="dashicons dashicons-book"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value">
                    <?php echo number_format(array_sum(array_column($instructores, 'total_cursos'))); ?>
                </div>
                <div class="flavor-stat-label">Cursos Creados</div>
            </div>
        </div>

        <div class="flavor-stat-card">
            <div class="flavor-stat-icon" style="background: #8b5cf6;">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="flavor-stat-content">
                <div class="flavor-stat-value">
                    <?php echo number_format(array_sum(array_column($instructores, 'total_alumnos'))); ?>
                </div>
                <div class="flavor-stat-label">Alumnos Totales</div>
            </div>
        </div>
    </div>

    <!-- Tabla de instructores -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2>Listado de Instructores</h2>
        </div>
        <div class="flavor-card-body">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Instructor</th>
                        <th>Email</th>
                        <th style="width: 100px;">Cursos</th>
                        <th style="width: 100px;">Activos</th>
                        <th style="width: 100px;">Alumnos</th>
                        <th style="width: 100px;">Valoración</th>
                        <th style="width: 120px;">Ingresos</th>
                        <th style="width: 100px;">Desde</th>
                        <th style="width: 150px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($instructores)): ?>
                        <?php foreach ($instructores as $instructor): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($instructor->display_name); ?></strong>
                                    <br>
                                    <small class="flavor-text-muted">ID: <?php echo $instructor->ID; ?></small>
                                </td>
                                <td><?php echo esc_html($instructor->user_email); ?></td>
                                <td class="flavor-text-center">
                                    <strong><?php echo number_format($instructor->total_cursos); ?></strong>
                                </td>
                                <td class="flavor-text-center">
                                    <span class="flavor-badge flavor-badge-success">
                                        <?php echo number_format($instructor->cursos_activos); ?>
                                    </span>
                                </td>
                                <td class="flavor-text-center">
                                    <?php echo number_format($instructor->total_alumnos); ?>
                                </td>
                                <td class="flavor-text-center">
                                    <?php if ($instructor->valoracion_promedio): ?>
                                        <span class="flavor-rating">
                                            <?php echo number_format($instructor->valoracion_promedio, 1); ?> ★
                                        </span>
                                    <?php else: ?>
                                        <span class="flavor-text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="flavor-text-right">
                                    <strong><?php echo number_format($instructor->ingresos_totales, 2); ?>€</strong>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($instructor->user_registered)); ?>
                                </td>
                                <td>
                                    <button class="button button-small btn-ver-instructor" data-id="<?php echo $instructor->ID; ?>">
                                        Ver Perfil
                                    </button>
                                    <button class="button button-small btn-cursos-instructor" data-id="<?php echo $instructor->ID; ?>">
                                        Cursos
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="flavor-no-data">
                                No hay instructores registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Instructores por Valoración -->
    <div class="flavor-dashboard-row">
        <div class="flavor-dashboard-col-6">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2>Top 5 Instructores por Valoración</h2>
                </div>
                <div class="flavor-card-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Valoración</th>
                                <th>Cursos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $top_valoracion = $instructores;
                            usort($top_valoracion, function($a, $b) {
                                return $b->valoracion_promedio <=> $a->valoracion_promedio;
                            });
                            $top_valoracion = array_slice($top_valoracion, 0, 5);
                            ?>
                            <?php foreach ($top_valoracion as $instructor): ?>
                                <?php if ($instructor->valoracion_promedio > 0): ?>
                                    <tr>
                                        <td><?php echo esc_html($instructor->display_name); ?></td>
                                        <td>
                                            <span class="flavor-rating">
                                                <?php echo number_format($instructor->valoracion_promedio, 2); ?> ★
                                            </span>
                                        </td>
                                        <td><?php echo number_format($instructor->total_cursos); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="flavor-dashboard-col-6">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h2>Top 5 Instructores por Alumnos</h2>
                </div>
                <div class="flavor-card-body">
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th>Instructor</th>
                                <th>Alumnos</th>
                                <th>Cursos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $top_alumnos = $instructores;
                            usort($top_alumnos, function($a, $b) {
                                return $b->total_alumnos <=> $a->total_alumnos;
                            });
                            $top_alumnos = array_slice($top_alumnos, 0, 5);
                            ?>
                            <?php foreach ($top_alumnos as $instructor): ?>
                                <tr>
                                    <td><?php echo esc_html($instructor->display_name); ?></td>
                                    <td><strong><?php echo number_format($instructor->total_alumnos); ?></strong></td>
                                    <td><?php echo number_format($instructor->total_cursos); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Perfil Instructor -->
<div id="modal-instructor" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h2>Perfil del Instructor</h2>
            <span class="flavor-modal-close">&times;</span>
        </div>
        <div class="flavor-modal-body" id="instructor-profile-content">
            <div class="flavor-loading">Cargando...</div>
        </div>
    </div>
</div>

<style>
<?php include plugin_dir_path(__FILE__) . '../../assets/css/admin-common.css'; ?>

.flavor-dashboard-row {
    display: grid;
    grid-template-columns: repeat(12, 1fr);
    gap: 20px;
    margin: 20px 0;
}

.flavor-dashboard-col-6 {
    grid-column: span 6;
}

@media (max-width: 782px) {
    .flavor-dashboard-col-6 {
        grid-column: span 12;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ver perfil instructor
    $('.btn-ver-instructor').on('click', function() {
        const instructorId = $(this).data('id');

        $('#instructor-profile-content').html('<div class="flavor-loading">Cargando...</div>');
        $('#modal-instructor').fadeIn();

        $.post(ajaxurl, {
            action: 'flavor_get_instructor_profile',
            instructor_id: instructorId,
            nonce: '<?php echo wp_create_nonce('flavor_cursos_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                const data = response.data;
                let html = `
                    <div class="flavor-instructor-profile">
                        <h3>${data.nombre}</h3>
                        <p><strong>Email:</strong> ${data.email}</p>
                        <p><strong>Miembro desde:</strong> ${data.fecha_registro}</p>

                        <div class="flavor-stats-mini">
                            <div class="stat-item">
                                <div class="stat-value">${data.total_cursos}</div>
                                <div class="stat-label">Cursos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${data.total_alumnos}</div>
                                <div class="stat-label">Alumnos</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${data.valoracion} ★</div>
                                <div class="stat-label">Valoración</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${data.ingresos}€</div>
                                <div class="stat-label">Ingresos</div>
                            </div>
                        </div>

                        <h4>Cursos Impartidos</h4>
                        <ul class="flavor-course-list">
                            ${data.cursos.map(c => `
                                <li>
                                    <strong>${c.titulo}</strong>
                                    <span class="flavor-badge flavor-badge-${c.estado === 'en_curso' ? 'success' : 'info'}">
                                        ${c.estado}
                                    </span>
                                    <br>
                                    <small>${c.alumnos} alumnos · ${c.valoracion} ★</small>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                `;
                $('#instructor-profile-content').html(html);
            } else {
                $('#instructor-profile-content').html('<p class="flavor-error">Error al cargar el perfil</p>');
            }
        });
    });

    // Ver cursos del instructor
    $('.btn-cursos-instructor').on('click', function() {
        const instructorId = $(this).data('id');
        window.location.href = '?page=flavor-chat-cursos&tab=cursos&instructor_id=' + instructorId;
    });

    // Cerrar modal
    $('.flavor-modal-close').on('click', function() {
        $('#modal-instructor').fadeOut();
    });
});
</script>

<style>
.flavor-instructor-profile h3 {
    margin-top: 0;
    font-size: 20px;
}

.flavor-stats-mini {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f8fafc;
    border-radius: 8px;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #1e293b;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.flavor-course-list {
    list-style: none;
    padding: 0;
}

.flavor-course-list li {
    padding: 10px;
    margin: 5px 0;
    background: #f8fafc;
    border-radius: 4px;
}
</style>
