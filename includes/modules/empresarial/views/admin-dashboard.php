<?php
/**
 * Vista: Dashboard Empresarial (Admin)
 *
 * @package FlavorChatIA
 * @var array $estadisticas Estadísticas del módulo
 */

if (!defined('ABSPATH')) {
    exit;
}

$datos_estadisticas = $estadisticas['estadisticas'] ?? [];
$datos_contactos = $datos_estadisticas['contactos'] ?? [];
$datos_proyectos = $datos_estadisticas['proyectos'] ?? [];
$datos_financiero = $datos_estadisticas['financiero'] ?? [];
?>

<div class="wrap flavor-empresarial-dashboard">
    <!-- Tarjetas de estadísticas principales -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card flavor-stat-contactos">
            <div class="stat-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($datos_contactos['nuevos'] ?? 0); ?></span>
                <span class="stat-label"><?php esc_html_e('Contactos Nuevos', 'flavor-chat-ia'); ?></span>
            </div>
            <?php if (($datos_contactos['nuevos'] ?? 0) > 0): ?>
                <span class="stat-badge stat-badge-warning"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></span>
            <?php endif; ?>
        </div>

        <div class="flavor-stat-card flavor-stat-proyectos">
            <div class="stat-icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($datos_proyectos['activos'] ?? 0); ?></span>
                <span class="stat-label"><?php esc_html_e('Proyectos Activos', 'flavor-chat-ia'); ?></span>
            </div>
            <?php if (($datos_proyectos['vencidos'] ?? 0) > 0): ?>
                <span class="stat-badge stat-badge-danger">
                    <?php printf(esc_html__('%d vencidos', 'flavor-chat-ia'), $datos_proyectos['vencidos']); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="flavor-stat-card flavor-stat-presupuesto">
            <div class="stat-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($datos_financiero['presupuesto_activo_fmt'] ?? '0,00 €'); ?></span>
                <span class="stat-label"><?php esc_html_e('Presupuesto Activo', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-completados">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html($datos_proyectos['completados'] ?? 0); ?></span>
                <span class="stat-label"><?php esc_html_e('Proyectos Completados', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Contenido en dos columnas -->
    <div class="flavor-dashboard-columns">
        <!-- Columna izquierda: Contactos recientes -->
        <div class="flavor-dashboard-column">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><?php esc_html_e('Últimos Contactos', 'flavor-chat-ia'); ?></h3>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-empresarial-empresas')); ?>" class="button">
                        <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <div class="flavor-card-body">
                    <?php
                    global $wpdb;
                    $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';
                    $ultimos_contactos = $wpdb->get_results(
                        "SELECT id, nombre, email, empresa, asunto, estado, created_at
                         FROM $tabla_contactos
                         ORDER BY created_at DESC
                         LIMIT 5",
                        ARRAY_A
                    );

                    if (!empty($ultimos_contactos)):
                    ?>
                        <ul class="flavor-contactos-list">
                            <?php foreach ($ultimos_contactos as $contacto): ?>
                                <li class="contacto-item estado-<?php echo esc_attr($contacto['estado']); ?>">
                                    <div class="contacto-avatar">
                                        <?php echo get_avatar($contacto['email'], 40); ?>
                                    </div>
                                    <div class="contacto-info">
                                        <strong><?php echo esc_html($contacto['nombre']); ?></strong>
                                        <?php if (!empty($contacto['empresa'])): ?>
                                            <span class="contacto-empresa"><?php echo esc_html($contacto['empresa']); ?></span>
                                        <?php endif; ?>
                                        <span class="contacto-asunto"><?php echo esc_html($contacto['asunto'] ?: __('Sin asunto', 'flavor-chat-ia')); ?></span>
                                    </div>
                                    <div class="contacto-meta">
                                        <span class="contacto-estado estado-<?php echo esc_attr($contacto['estado']); ?>">
                                            <?php echo esc_html(ucfirst($contacto['estado'])); ?>
                                        </span>
                                        <span class="contacto-fecha">
                                            <?php echo esc_html(human_time_diff(strtotime($contacto['created_at']), current_time('timestamp'))); ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="flavor-empty-message">
                            <?php esc_html_e('No hay contactos recientes.', 'flavor-chat-ia'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Distribución de orígenes -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><?php esc_html_e('Orígenes de Contacto', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <?php if (!empty($datos_contactos['origenes'])): ?>
                        <div class="flavor-origenes-chart">
                            <?php
                            $total_origenes = array_sum(array_column($datos_contactos['origenes'], 'cantidad'));
                            $colores_origen = [
                                'web' => '#4e73df',
                                'landing' => '#1cc88a',
                                'popup' => '#f6c23e',
                            ];
                            foreach ($datos_contactos['origenes'] as $origen):
                                $porcentaje = $total_origenes > 0 ? round(($origen['cantidad'] / $total_origenes) * 100) : 0;
                                $color = $colores_origen[$origen['origen']] ?? '#858796';
                            ?>
                                <div class="origen-bar">
                                    <div class="origen-label">
                                        <span class="origen-nombre"><?php echo esc_html(ucfirst($origen['origen'])); ?></span>
                                        <span class="origen-cantidad"><?php echo esc_html($origen['cantidad']); ?></span>
                                    </div>
                                    <div class="origen-progress">
                                        <div class="origen-fill" style="width: <?php echo esc_attr($porcentaje); ?>%; background-color: <?php echo esc_attr($color); ?>;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="flavor-empty-message">
                            <?php esc_html_e('Sin datos de orígenes.', 'flavor-chat-ia'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Columna derecha: Proyectos -->
        <div class="flavor-dashboard-column">
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><?php esc_html_e('Proyectos Activos', 'flavor-chat-ia'); ?></h3>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-empresarial-contratos')); ?>" class="button">
                        <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <div class="flavor-card-body">
                    <?php
                    $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';
                    $proyectos_activos = $wpdb->get_results(
                        "SELECT id, titulo, cliente_nombre, estado, presupuesto, progreso, fecha_entrega
                         FROM $tabla_proyectos
                         WHERE estado IN ('aprobado', 'en_curso')
                         ORDER BY fecha_entrega ASC
                         LIMIT 5",
                        ARRAY_A
                    );

                    if (!empty($proyectos_activos)):
                    ?>
                        <ul class="flavor-proyectos-list">
                            <?php foreach ($proyectos_activos as $proyecto):
                                $dias_restantes = null;
                                $clase_urgencia = '';
                                if (!empty($proyecto['fecha_entrega'])) {
                                    $fecha_entrega = strtotime($proyecto['fecha_entrega']);
                                    $hoy = strtotime(current_time('Y-m-d'));
                                    $dias_restantes = ($fecha_entrega - $hoy) / 86400;
                                    if ($dias_restantes < 0) {
                                        $clase_urgencia = 'urgencia-vencido';
                                    } elseif ($dias_restantes <= 7) {
                                        $clase_urgencia = 'urgencia-pronto';
                                    }
                                }
                            ?>
                                <li class="proyecto-item <?php echo esc_attr($clase_urgencia); ?>">
                                    <div class="proyecto-info">
                                        <strong><?php echo esc_html($proyecto['titulo']); ?></strong>
                                        <span class="proyecto-cliente"><?php echo esc_html($proyecto['cliente_nombre']); ?></span>
                                    </div>
                                    <div class="proyecto-progreso">
                                        <div class="progreso-bar">
                                            <div class="progreso-fill" style="width: <?php echo esc_attr($proyecto['progreso']); ?>%;"></div>
                                        </div>
                                        <span class="progreso-texto"><?php echo esc_html($proyecto['progreso']); ?>%</span>
                                    </div>
                                    <div class="proyecto-meta">
                                        <?php if (!empty($proyecto['fecha_entrega'])): ?>
                                            <span class="proyecto-fecha <?php echo esc_attr($clase_urgencia); ?>">
                                                <?php echo esc_html(date_i18n('j M', strtotime($proyecto['fecha_entrega']))); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="flavor-empty-message">
                            <?php esc_html_e('No hay proyectos activos.', 'flavor-chat-ia'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resumen financiero -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><?php esc_html_e('Resumen Financiero', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <div class="flavor-financiero-resumen">
                        <div class="financiero-item">
                            <span class="financiero-label"><?php esc_html_e('Presupuesto Total', 'flavor-chat-ia'); ?></span>
                            <span class="financiero-valor"><?php echo esc_html($datos_financiero['presupuesto_total_fmt'] ?? '0,00 €'); ?></span>
                        </div>
                        <div class="financiero-item financiero-activo">
                            <span class="financiero-label"><?php esc_html_e('En Curso', 'flavor-chat-ia'); ?></span>
                            <span class="financiero-valor"><?php echo esc_html($datos_financiero['presupuesto_activo_fmt'] ?? '0,00 €'); ?></span>
                        </div>
                        <div class="financiero-item financiero-completado">
                            <span class="financiero-label"><?php esc_html_e('Completado', 'flavor-chat-ia'); ?></span>
                            <span class="financiero-valor"><?php echo esc_html($datos_financiero['presupuesto_completado_fmt'] ?? '0,00 €'); ?></span>
                        </div>
                    </div>

                    <?php if (($datos_proyectos['progreso_promedio'] ?? 0) > 0): ?>
                        <div class="flavor-progreso-promedio">
                            <span class="progreso-label"><?php esc_html_e('Progreso Promedio', 'flavor-chat-ia'); ?></span>
                            <div class="progreso-bar grande">
                                <div class="progreso-fill" style="width: <?php echo esc_attr($datos_proyectos['progreso_promedio']); ?>%;"></div>
                            </div>
                            <span class="progreso-texto"><?php echo esc_html($datos_proyectos['progreso_promedio']); ?>%</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actividad reciente -->
            <div class="flavor-card">
                <div class="flavor-card-header">
                    <h3><?php esc_html_e('Estadísticas del Periodo', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="flavor-card-body">
                    <div class="flavor-periodo-stats">
                        <div class="periodo-stat">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <div>
                                <strong><?php echo esc_html($datos_contactos['ultima_semana'] ?? 0); ?></strong>
                                <span><?php esc_html_e('Contactos esta semana', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                        <div class="periodo-stat">
                            <span class="dashicons dashicons-calendar"></span>
                            <div>
                                <strong><?php echo esc_html($datos_contactos['ultimo_mes'] ?? 0); ?></strong>
                                <span><?php esc_html_e('Contactos este mes', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                        <div class="periodo-stat">
                            <span class="dashicons dashicons-clipboard"></span>
                            <div>
                                <strong><?php echo esc_html($datos_proyectos['propuestas'] ?? 0); ?></strong>
                                <span><?php esc_html_e('Propuestas pendientes', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-empresarial-dashboard {
    margin-top: 20px;
}

.flavor-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    position: relative;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f1;
}

.stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.flavor-stat-contactos .stat-icon { background: #e8f4fd; color: #2271b1; }
.flavor-stat-proyectos .stat-icon { background: #fef3e7; color: #d63638; }
.flavor-stat-presupuesto .stat-icon { background: #e7f5e7; color: #00a32a; }
.flavor-stat-completados .stat-icon { background: #f0e7fe; color: #7b61ff; }

.stat-content {
    flex: 1;
}

.stat-number {
    display: block;
    font-size: 28px;
    font-weight: 600;
    color: #1d2327;
}

.stat-label {
    display: block;
    color: #646970;
    font-size: 13px;
}

.stat-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
}

.stat-badge-warning { background: #fff3cd; color: #856404; }
.stat-badge-danger { background: #f8d7da; color: #721c24; }

.flavor-dashboard-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 1200px) {
    .flavor-dashboard-columns {
        grid-template-columns: 1fr;
    }
}

.flavor-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.flavor-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-card-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.flavor-card-body {
    padding: 20px;
}

.flavor-contactos-list,
.flavor-proyectos-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.contacto-item,
.proyecto-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f1;
}

.contacto-item:last-child,
.proyecto-item:last-child {
    border-bottom: none;
}

.contacto-avatar img {
    border-radius: 50%;
}

.contacto-info,
.proyecto-info {
    flex: 1;
}

.contacto-info strong,
.proyecto-info strong {
    display: block;
    color: #1d2327;
}

.contacto-empresa,
.contacto-asunto,
.proyecto-cliente {
    display: block;
    font-size: 12px;
    color: #646970;
}

.contacto-estado {
    font-size: 11px;
    padding: 2px 8px;
    border-radius: 10px;
    background: #f0f0f1;
}

.contacto-estado.estado-nuevo { background: #fff3cd; color: #856404; }
.contacto-estado.estado-leido { background: #cce5ff; color: #004085; }
.contacto-estado.estado-respondido { background: #d4edda; color: #155724; }

.contacto-fecha {
    display: block;
    font-size: 11px;
    color: #8c8f94;
    margin-top: 2px;
}

.proyecto-progreso {
    width: 100px;
    text-align: center;
}

.progreso-bar {
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 4px;
}

.progreso-bar.grande {
    height: 10px;
    flex: 1;
}

.progreso-fill {
    height: 100%;
    background: #2271b1;
    border-radius: 3px;
    transition: width 0.3s ease;
}

.progreso-texto {
    font-size: 11px;
    color: #646970;
}

.proyecto-fecha {
    font-size: 12px;
    color: #646970;
}

.proyecto-fecha.urgencia-vencido { color: #d63638; font-weight: 600; }
.proyecto-fecha.urgencia-pronto { color: #dba617; }

.flavor-empty-message {
    color: #646970;
    text-align: center;
    padding: 20px;
    margin: 0;
}

.flavor-origenes-chart {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.origen-bar {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.origen-label {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.origen-progress {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.origen-fill {
    height: 100%;
    border-radius: 4px;
}

.flavor-financiero-resumen {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.financiero-item {
    text-align: center;
    padding: 15px;
    background: #f7f7f7;
    border-radius: 6px;
}

.financiero-label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-bottom: 5px;
}

.financiero-valor {
    display: block;
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
}

.financiero-activo { background: #e8f4fd; }
.financiero-activo .financiero-valor { color: #2271b1; }
.financiero-completado { background: #e7f5e7; }
.financiero-completado .financiero-valor { color: #00a32a; }

.flavor-progreso-promedio {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-progreso-promedio .progreso-label {
    font-size: 13px;
    color: #646970;
    white-space: nowrap;
}

.flavor-periodo-stats {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.periodo-stat {
    display: flex;
    align-items: center;
    gap: 12px;
}

.periodo-stat .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    color: #2271b1;
}

.periodo-stat strong {
    display: block;
    font-size: 16px;
}

.periodo-stat span {
    display: block;
    font-size: 12px;
    color: #646970;
}
</style>
