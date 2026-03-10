<?php
/**
 * Dashboard de Seguimiento de Denuncias - Vista Admin
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Obtener estadísticas reales
$tabla_denuncias = $wpdb->prefix . 'flavor_seguimiento_denuncias';
$tabla_eventos = $wpdb->prefix . 'flavor_seguimiento_denuncias_eventos';
$tabla_participantes = $wpdb->prefix . 'flavor_seguimiento_denuncias_participantes';
$tabla_plantillas = $wpdb->prefix . 'flavor_seguimiento_denuncias_plantillas';

// Verificar si las tablas existen
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_denuncias}'") === $tabla_denuncias;

if ($tabla_existe) {
    $total_denuncias = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_denuncias}");
    $en_tramite = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_denuncias} WHERE estado IN ('presentada', 'en_tramite', 'requerimiento')");
    $silencio = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_denuncias} WHERE estado = 'silencio'");
    $resueltas_favorables = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_denuncias} WHERE estado = 'resuelta_favorable'");
    $resueltas_desfavorables = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_denuncias} WHERE estado = 'resuelta_desfavorable'");
    $recurridas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_denuncias} WHERE estado = 'recurrida'");

    // Eventos registrados
    $tabla_eventos_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_eventos}'") === $tabla_eventos;
    $total_eventos = $tabla_eventos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_eventos}") : 0;

    // Por tipo
    $por_tipo = $wpdb->get_results("
        SELECT tipo, COUNT(*) as total
        FROM {$tabla_denuncias}
        GROUP BY tipo
        ORDER BY total DESC
    ", ARRAY_A) ?: [];

    // Por estado
    $por_estado = $wpdb->get_results("
        SELECT estado, COUNT(*) as total
        FROM {$tabla_denuncias}
        GROUP BY estado
        ORDER BY total DESC
    ", ARRAY_A) ?: [];

    // Denuncias recientes
    $denuncias_recientes = $wpdb->get_results("
        SELECT id, titulo, tipo, estado, prioridad, fecha_presentacion, fecha_limite
        FROM {$tabla_denuncias}
        ORDER BY created_at DESC
        LIMIT 5
    ", ARRAY_A) ?: [];

    // Próximos plazos (denuncias con fecha límite cercana)
    $proximos_plazos = $wpdb->get_results("
        SELECT id, titulo, tipo, estado, fecha_limite,
               DATEDIFF(fecha_limite, CURDATE()) as dias_restantes
        FROM {$tabla_denuncias}
        WHERE fecha_limite IS NOT NULL
        AND fecha_limite >= CURDATE()
        AND estado NOT IN ('resuelta_favorable', 'resuelta_desfavorable', 'archivada')
        ORDER BY fecha_limite ASC
        LIMIT 5
    ", ARRAY_A) ?: [];

    // Denuncias urgentes (alta prioridad o en silencio)
    $urgentes = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$tabla_denuncias}
        WHERE (prioridad = 'alta' OR estado = 'silencio')
        AND estado NOT IN ('resuelta_favorable', 'resuelta_desfavorable', 'archivada')
    ");

    $hay_datos = $total_denuncias > 0;
} else {
    $hay_datos = false;
}

// Datos de demostración si no hay datos reales
if (!$hay_datos) {
    $total_denuncias = 24;
    $en_tramite = 8;
    $silencio = 3;
    $resueltas_favorables = 9;
    $resueltas_desfavorables = 2;
    $recurridas = 2;
    $total_eventos = 67;
    $urgentes = 4;

    $por_tipo = [
        ['tipo' => 'denuncia', 'total' => 10],
        ['tipo' => 'queja', 'total' => 6],
        ['tipo' => 'recurso', 'total' => 4],
        ['tipo' => 'solicitud', 'total' => 3],
        ['tipo' => 'peticion', 'total' => 1],
    ];

    $por_estado = [
        ['estado' => 'resuelta_favorable', 'total' => 9],
        ['estado' => 'en_tramite', 'total' => 5],
        ['estado' => 'silencio', 'total' => 3],
        ['estado' => 'presentada', 'total' => 2],
        ['estado' => 'recurrida', 'total' => 2],
        ['estado' => 'resuelta_desfavorable', 'total' => 2],
        ['estado' => 'requerimiento', 'total' => 1],
    ];

    $denuncias_recientes = [
        ['id' => 1, 'titulo' => 'Denuncia por vertidos ilegales', 'tipo' => 'denuncia', 'estado' => 'en_tramite', 'prioridad' => 'alta', 'fecha_presentacion' => date('Y-m-d', strtotime('-3 days'))],
        ['id' => 2, 'titulo' => 'Queja por ruidos nocturnos', 'tipo' => 'queja', 'estado' => 'silencio', 'prioridad' => 'media', 'fecha_presentacion' => date('Y-m-d', strtotime('-1 week'))],
        ['id' => 3, 'titulo' => 'Recurso licencia de obras', 'tipo' => 'recurso', 'estado' => 'presentada', 'prioridad' => 'alta', 'fecha_presentacion' => date('Y-m-d', strtotime('-2 days'))],
        ['id' => 4, 'titulo' => 'Solicitud acceso información', 'tipo' => 'solicitud', 'estado' => 'resuelta_favorable', 'prioridad' => 'baja', 'fecha_presentacion' => date('Y-m-d', strtotime('-2 weeks'))],
        ['id' => 5, 'titulo' => 'Denuncia ocupación vía pública', 'tipo' => 'denuncia', 'estado' => 'en_tramite', 'prioridad' => 'media', 'fecha_presentacion' => date('Y-m-d', strtotime('-5 days'))],
    ];

    $proximos_plazos = [
        ['id' => 1, 'titulo' => 'Denuncia por vertidos ilegales', 'tipo' => 'denuncia', 'estado' => 'en_tramite', 'fecha_limite' => date('Y-m-d', strtotime('+5 days')), 'dias_restantes' => 5],
        ['id' => 3, 'titulo' => 'Recurso licencia de obras', 'tipo' => 'recurso', 'estado' => 'presentada', 'fecha_limite' => date('Y-m-d', strtotime('+10 days')), 'dias_restantes' => 10],
        ['id' => 5, 'titulo' => 'Denuncia ocupación vía pública', 'tipo' => 'denuncia', 'estado' => 'en_tramite', 'fecha_limite' => date('Y-m-d', strtotime('+15 days')), 'dias_restantes' => 15],
    ];
}

// Labels para tipos
$tipos_labels = [
    'denuncia' => 'Denuncia',
    'queja' => 'Queja',
    'recurso' => 'Recurso',
    'solicitud' => 'Solicitud',
    'peticion' => 'Petición',
];

// Labels para estados
$estados_labels = [
    'presentada' => 'Presentada',
    'en_tramite' => 'En trámite',
    'requerimiento' => 'Requerimiento',
    'silencio' => 'Silencio Admin.',
    'resuelta_favorable' => 'Favorable',
    'resuelta_desfavorable' => 'Desfavorable',
    'archivada' => 'Archivada',
    'recurrida' => 'Recurrida',
];

// Colores para estados
$estados_colores = [
    'presentada' => '#3b82f6',
    'en_tramite' => '#f59e0b',
    'requerimiento' => '#ef4444',
    'silencio' => '#6b7280',
    'resuelta_favorable' => '#10b981',
    'resuelta_desfavorable' => '#dc2626',
    'archivada' => '#9ca3af',
    'recurrida' => '#8b5cf6',
];

// Colores para prioridades
$prioridad_colores = [
    'alta' => '#dc2626',
    'media' => '#f59e0b',
    'baja' => '#10b981',
];
?>

<div class="wrap flavor-admin-dashboard">
    <?php if (!$hay_datos): ?>
    <div class="notice notice-info" style="margin-bottom: 20px;">
        <p><strong>Modo demostración:</strong> Se muestran datos de ejemplo. Los datos reales aparecerán cuando se registren denuncias.</p>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="flavor-dashboard-header">
        <div>
            <h1><span class="dashicons dashicons-megaphone"></span> Seguimiento de Denuncias</h1>
            <p class="description">Panel de gestión y seguimiento de denuncias, quejas, recursos y solicitudes ciudadanas</p>
        </div>
        <div class="flavor-dashboard-actions">
            <a href="<?php echo esc_url(home_url('/denuncias/nueva/')); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt2"></span> Nueva Denuncia
            </a>
        </div>
    </div>

    <!-- Alertas urgentes -->
    <?php if ($urgentes > 0): ?>
    <div class="notice notice-warning" style="margin: 20px 0; padding: 12px 15px;">
        <p>
            <span class="dashicons dashicons-warning" style="color: #d97706;"></span>
            <strong>Atención:</strong> Hay <strong><?php echo $urgentes; ?></strong> denuncia(s) que requieren atención urgente (alta prioridad o en silencio administrativo).
            <a href="<?php echo esc_url(home_url('/denuncias/?filtro=urgentes')); ?>">Ver urgentes →</a>
        </p>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
    <div class="flavor-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 30px;">
        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-clipboard" style="font-size: 28px; color: #3b82f6;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo number_format($total_denuncias); ?></div>
                    <div style="font-size: 13px; color: #64748b;">Total Registros</div>
                </div>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-clock" style="font-size: 28px; color: #f59e0b;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo number_format($en_tramite); ?></div>
                    <div style="font-size: 13px; color: #64748b;">En Trámite</div>
                </div>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #6b7280; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-warning" style="font-size: 28px; color: #6b7280;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo number_format($silencio); ?></div>
                    <div style="font-size: 13px; color: #64748b;">Silencio Admin.</div>
                </div>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-yes-alt" style="font-size: 28px; color: #10b981;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo number_format($resueltas_favorables); ?></div>
                    <div style="font-size: 13px; color: #64748b;">Favorables</div>
                </div>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-dismiss" style="font-size: 28px; color: #dc2626;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo number_format($resueltas_desfavorables); ?></div>
                    <div style="font-size: 13px; color: #64748b;">Desfavorables</div>
                </div>
            </div>
        </div>

        <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-backup" style="font-size: 28px; color: #8b5cf6;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: 700; color: #1e293b;"><?php echo number_format($total_eventos); ?></div>
                    <div style="font-size: 13px; color: #64748b;">Eventos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="flavor-quick-access" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; font-size: 16px; color: #1e293b;">
            <span class="dashicons dashicons-admin-links"></span> Accesos Rápidos
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
            <a href="<?php echo esc_url(home_url('/denuncias/')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 6px; text-decoration: none; color: #334155; transition: all 0.2s;">
                <span class="dashicons dashicons-list-view" style="color: #3b82f6;"></span>
                <span>Todas las denuncias</span>
            </a>
            <a href="<?php echo esc_url(home_url('/denuncias/nueva/')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 6px; text-decoration: none; color: #334155; transition: all 0.2s;">
                <span class="dashicons dashicons-plus-alt" style="color: #10b981;"></span>
                <span>Nueva denuncia</span>
            </a>
            <a href="<?php echo esc_url(home_url('/denuncias/?estado=en_tramite')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 6px; text-decoration: none; color: #334155; transition: all 0.2s;">
                <span class="dashicons dashicons-clock" style="color: #f59e0b;"></span>
                <span>En trámite</span>
            </a>
            <a href="<?php echo esc_url(home_url('/denuncias/?estado=silencio')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 6px; text-decoration: none; color: #334155; transition: all 0.2s;">
                <span class="dashicons dashicons-warning" style="color: #6b7280;"></span>
                <span>Silencio administrativo</span>
            </a>
            <a href="<?php echo esc_url(home_url('/denuncias/plantillas/')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 6px; text-decoration: none; color: #334155; transition: all 0.2s;">
                <span class="dashicons dashicons-media-document" style="color: #8b5cf6;"></span>
                <span>Plantillas</span>
            </a>
            <a href="<?php echo esc_url(home_url('/denuncias/estadisticas/')); ?>" class="flavor-quick-link" style="display: flex; align-items: center; gap: 10px; padding: 12px 16px; background: #f8fafc; border-radius: 6px; text-decoration: none; color: #334155; transition: all 0.2s;">
                <span class="dashicons dashicons-chart-bar" style="color: #ec4899;"></span>
                <span>Estadísticas</span>
            </a>
        </div>
    </div>

    <!-- Gráficos -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <!-- Gráfico por tipo -->
        <div class="flavor-chart-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; font-size: 15px; color: #1e293b;">
                <span class="dashicons dashicons-category"></span> Distribución por Tipo
            </h3>
            <div style="position: relative; height: 280px;">
                <canvas id="chart-por-tipo"></canvas>
            </div>
        </div>

        <!-- Gráfico por estado -->
        <div class="flavor-chart-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; font-size: 15px; color: #1e293b;">
                <span class="dashicons dashicons-analytics"></span> Distribución por Estado
            </h3>
            <div style="position: relative; height: 280px;">
                <canvas id="chart-por-estado"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px;">
        <!-- Denuncias recientes -->
        <div class="flavor-table-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; font-size: 15px; color: #1e293b;">
                <span class="dashicons dashicons-clock"></span> Denuncias Recientes
            </h3>
            <table class="wp-list-table widefat striped" style="border: none;">
                <thead>
                    <tr>
                        <th style="padding: 10px 8px;">Título</th>
                        <th style="padding: 10px 8px; width: 90px;">Tipo</th>
                        <th style="padding: 10px 8px; width: 100px;">Estado</th>
                        <th style="padding: 10px 8px; width: 60px;">Prior.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($denuncias_recientes)): ?>
                        <?php foreach ($denuncias_recientes as $denuncia): ?>
                        <tr>
                            <td style="padding: 10px 8px;">
                                <a href="<?php echo esc_url(home_url('/denuncias/' . $denuncia['id'] . '/')); ?>" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                    <?php echo esc_html(wp_trim_words($denuncia['titulo'], 6)); ?>
                                </a>
                                <div style="font-size: 11px; color: #64748b;">
                                    <?php echo esc_html(date_i18n('j M Y', strtotime($denuncia['fecha_presentacion']))); ?>
                                </div>
                            </td>
                            <td style="padding: 10px 8px;">
                                <span style="font-size: 12px; background: #f1f5f9; padding: 3px 8px; border-radius: 4px;">
                                    <?php echo esc_html($tipos_labels[$denuncia['tipo']] ?? $denuncia['tipo']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px 8px;">
                                <?php
                                $estado = $denuncia['estado'];
                                $color = $estados_colores[$estado] ?? '#6b7280';
                                ?>
                                <span style="font-size: 11px; background: <?php echo $color; ?>20; color: <?php echo $color; ?>; padding: 3px 8px; border-radius: 4px; font-weight: 500;">
                                    <?php echo esc_html($estados_labels[$estado] ?? $estado); ?>
                                </span>
                            </td>
                            <td style="padding: 10px 8px; text-align: center;">
                                <?php
                                $prioridad = $denuncia['prioridad'] ?? 'media';
                                $prioridad_color = $prioridad_colores[$prioridad] ?? '#f59e0b';
                                ?>
                                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: <?php echo $prioridad_color; ?>;" title="<?php echo esc_attr(ucfirst($prioridad)); ?>"></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: #64748b;">
                                No hay denuncias registradas
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: center;">
                <a href="<?php echo esc_url(home_url('/denuncias/')); ?>" class="button button-secondary">
                    Ver todas las denuncias
                </a>
            </div>
        </div>

        <!-- Próximos plazos -->
        <div class="flavor-table-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin-top: 0; font-size: 15px; color: #1e293b;">
                <span class="dashicons dashicons-calendar-alt"></span> Próximos Plazos
            </h3>
            <table class="wp-list-table widefat striped" style="border: none;">
                <thead>
                    <tr>
                        <th style="padding: 10px 8px;">Denuncia</th>
                        <th style="padding: 10px 8px; width: 100px;">Fecha Límite</th>
                        <th style="padding: 10px 8px; width: 80px;">Días</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proximos_plazos)): ?>
                        <?php foreach ($proximos_plazos as $plazo): ?>
                        <?php
                        $dias = (int) $plazo['dias_restantes'];
                        $urgencia_color = $dias <= 3 ? '#dc2626' : ($dias <= 7 ? '#f59e0b' : '#10b981');
                        ?>
                        <tr>
                            <td style="padding: 10px 8px;">
                                <a href="<?php echo esc_url(home_url('/denuncias/' . $plazo['id'] . '/')); ?>" style="color: #2563eb; text-decoration: none; font-weight: 500;">
                                    <?php echo esc_html(wp_trim_words($plazo['titulo'], 5)); ?>
                                </a>
                                <div style="font-size: 11px; color: #64748b;">
                                    <?php echo esc_html($tipos_labels[$plazo['tipo']] ?? $plazo['tipo']); ?> • <?php echo esc_html($estados_labels[$plazo['estado']] ?? $plazo['estado']); ?>
                                </div>
                            </td>
                            <td style="padding: 10px 8px; font-size: 13px;">
                                <?php echo esc_html(date_i18n('j M Y', strtotime($plazo['fecha_limite']))); ?>
                            </td>
                            <td style="padding: 10px 8px; text-align: center;">
                                <span style="font-size: 12px; background: <?php echo $urgencia_color; ?>20; color: <?php echo $urgencia_color; ?>; padding: 4px 10px; border-radius: 4px; font-weight: 600;">
                                    <?php echo $dias; ?> días
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px; color: #64748b;">
                                No hay plazos próximos
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: center;">
                <a href="<?php echo esc_url(home_url('/denuncias/?orden=fecha_limite')); ?>" class="button button-secondary">
                    Ver calendario de plazos
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-admin-dashboard .flavor-quick-link:hover {
    background: #e2e8f0 !important;
    transform: translateY(-1px);
}
.flavor-admin-dashboard .flavor-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}
.flavor-admin-dashboard .flavor-dashboard-header h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 23px;
}
.flavor-admin-dashboard .flavor-dashboard-header h1 .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #f59e0b;
}
.flavor-admin-dashboard .flavor-dashboard-header .description {
    margin: 5px 0 0;
    color: #64748b;
}
.flavor-admin-dashboard .flavor-dashboard-actions .button-primary .dashicons {
    margin-right: 5px;
    vertical-align: middle;
    font-size: 16px;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para gráficos
    const tiposData = <?php echo json_encode(array_map(function($t) use ($tipos_labels) {
        return [
            'label' => $tipos_labels[$t['tipo']] ?? $t['tipo'],
            'value' => (int) $t['total']
        ];
    }, $por_tipo)); ?>;

    const estadosData = <?php echo json_encode(array_map(function($e) use ($estados_labels, $estados_colores) {
        return [
            'label' => $estados_labels[$e['estado']] ?? $e['estado'],
            'value' => (int) $e['total'],
            'color' => $estados_colores[$e['estado']] ?? '#6b7280'
        ];
    }, $por_estado)); ?>;

    // Colores para tipos
    const tiposColores = ['#3b82f6', '#f59e0b', '#10b981', '#8b5cf6', '#ec4899'];

    // Gráfico por tipo
    new Chart(document.getElementById('chart-por-tipo'), {
        type: 'doughnut',
        data: {
            labels: tiposData.map(t => t.label),
            datasets: [{
                data: tiposData.map(t => t.value),
                backgroundColor: tiposColores.slice(0, tiposData.length),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, usePointStyle: true }
                }
            }
        }
    });

    // Gráfico por estado
    new Chart(document.getElementById('chart-por-estado'), {
        type: 'bar',
        data: {
            labels: estadosData.map(e => e.label),
            datasets: [{
                data: estadosData.map(e => e.value),
                backgroundColor: estadosData.map(e => e.color),
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                },
                x: {
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
});
</script>
