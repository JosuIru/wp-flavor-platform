<?php
/**
 * Vista Mis Propuestas - Módulo Participación Ciudadana (Frontend)
 *
 * Panel del usuario para gestionar sus propuestas ciudadanas
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$usuario_actual_id = get_current_user_id();
if (!$usuario_actual_id) {
    echo '<div class="flavor-empty-state">';
    echo '<div class="flavor-empty-icon"><span class="dashicons dashicons-lock"></span></div>';
    echo '<p>' . esc_html__('Debes iniciar sesión para ver tus propuestas.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;

$tabla_propuestas = $wpdb->prefix . 'flavor_propuestas';
$tabla_votos = $wpdb->prefix . 'flavor_votos';
$tabla_comentarios = $wpdb->prefix . 'flavor_comentarios_propuesta';

// Estadísticas del usuario
$estadisticas_usuario = [
    'total_propuestas' => 0,
    'en_votacion' => 0,
    'aprobadas' => 0,
    'implementadas' => 0,
    'votos_recibidos' => 0,
    'comentarios_recibidos' => 0,
];

if (Flavor_Chat_Helpers::tabla_existe($tabla_propuestas)) {
    $estadisticas_usuario['total_propuestas'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proponente_id = %d",
        $usuario_actual_id
    ));

    $estadisticas_usuario['en_votacion'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proponente_id = %d AND estado = 'activa'",
        $usuario_actual_id
    ));

    $estadisticas_usuario['aprobadas'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proponente_id = %d AND estado IN ('aprobada', 'aceptada')",
        $usuario_actual_id
    ));

    $estadisticas_usuario['implementadas'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proponente_id = %d AND estado = 'implementada'",
        $usuario_actual_id
    ));

    // Votos recibidos en todas las propuestas del usuario
    $estadisticas_usuario['votos_recibidos'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(p.votos_favor + p.votos_contra + p.votos_abstencion)
         FROM {$tabla_propuestas} p
         WHERE p.proponente_id = %d",
        $usuario_actual_id
    ));
}

if (Flavor_Chat_Helpers::tabla_existe($tabla_comentarios)) {
    $estadisticas_usuario['comentarios_recibidos'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(c.id)
         FROM {$tabla_comentarios} c
         INNER JOIN {$tabla_propuestas} p ON c.propuesta_id = p.id
         WHERE p.proponente_id = %d",
        $usuario_actual_id
    ));
}

// Obtener propuestas del usuario con paginación
$pagina_actual = max(1, intval($_GET['pag'] ?? 1));
$elementos_por_pagina = 10;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

$condicion_estado = '';
$parametros_query = [$usuario_actual_id];
if ($estado_filtro && $estado_filtro !== 'todos') {
    $condicion_estado = " AND estado = %s";
    $parametros_query[] = $estado_filtro;
}

$propuestas_usuario = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*,
            (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id AND v.voto = 'favor') as votos_favor_calc,
            (SELECT COUNT(*) FROM {$tabla_votos} v WHERE v.propuesta_id = p.id AND v.voto = 'contra') as votos_contra_calc,
            (SELECT COUNT(*) FROM {$tabla_comentarios} c WHERE c.propuesta_id = p.id AND c.estado = 'aprobado') as comentarios_count
     FROM {$tabla_propuestas} p
     WHERE p.proponente_id = %d {$condicion_estado}
     ORDER BY p.fecha_creacion DESC
     LIMIT {$elementos_por_pagina} OFFSET {$offset}",
    ...$parametros_query
));

$total_propuestas = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_propuestas} WHERE proponente_id = %d {$condicion_estado}",
    ...$parametros_query
));

$total_paginas = ceil($total_propuestas / $elementos_por_pagina);

$mapa_estados = [
    'borrador' => ['etiqueta' => __('Borrador', 'flavor-chat-ia'), 'color' => '#6b7280', 'icono' => 'edit'],
    'pendiente_validacion' => ['etiqueta' => __('Pendiente', 'flavor-chat-ia'), 'color' => '#f59e0b', 'icono' => 'clock'],
    'activa' => ['etiqueta' => __('Activa', 'flavor-chat-ia'), 'color' => '#3b82f6', 'icono' => 'visibility'],
    'en_estudio' => ['etiqueta' => __('En Estudio', 'flavor-chat-ia'), 'color' => '#8b5cf6', 'icono' => 'search'],
    'aprobada' => ['etiqueta' => __('Aprobada', 'flavor-chat-ia'), 'color' => '#10b981', 'icono' => 'yes-alt'],
    'rechazada' => ['etiqueta' => __('Rechazada', 'flavor-chat-ia'), 'color' => '#ef4444', 'icono' => 'dismiss'],
    'implementada' => ['etiqueta' => __('Implementada', 'flavor-chat-ia'), 'color' => '#059669', 'icono' => 'flag'],
    'archivada' => ['etiqueta' => __('Archivada', 'flavor-chat-ia'), 'color' => '#9ca3af', 'icono' => 'archive'],
];

$url_base = remove_query_arg(['pag', 'estado']);
?>

<div class="flavor-mis-propuestas-container">
    <!-- Header -->
    <div class="flavor-section-header">
        <div class="flavor-header-content">
            <h2>
                <span class="dashicons dashicons-lightbulb"></span>
                <?php esc_html_e('Mis Propuestas', 'flavor-chat-ia'); ?>
            </h2>
            <p class="flavor-subtitle"><?php esc_html_e('Gestiona las propuestas que has presentado a la comunidad', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="flavor-header-actions">
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('participacion', 'crear')); ?>" class="flavor-btn flavor-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nueva Propuesta', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- KPIs -->
    <div class="flavor-kpi-grid flavor-kpi-grid-4">
        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon" style="background: #dbeafe; color: #2563eb;">
                <span class="dashicons dashicons-lightbulb"></span>
            </div>
            <div class="flavor-kpi-content">
                <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_usuario['total_propuestas']); ?></span>
                <span class="flavor-kpi-label"><?php esc_html_e('Propuestas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon" style="background: #dcfce7; color: #16a34a;">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-kpi-content">
                <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_usuario['aprobadas']); ?></span>
                <span class="flavor-kpi-label"><?php esc_html_e('Aprobadas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon" style="background: #fef3c7; color: #d97706;">
                <span class="dashicons dashicons-chart-bar"></span>
            </div>
            <div class="flavor-kpi-content">
                <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_usuario['votos_recibidos']); ?></span>
                <span class="flavor-kpi-label"><?php esc_html_e('Votos Recibidos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon" style="background: #ede9fe; color: #7c3aed;">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="flavor-kpi-content">
                <span class="flavor-kpi-value"><?php echo number_format_i18n($estadisticas_usuario['comentarios_recibidos']); ?></span>
                <span class="flavor-kpi-label"><?php esc_html_e('Comentarios', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="flavor-filters-bar">
        <div class="flavor-filter-tabs">
            <a href="<?php echo esc_url(add_query_arg('estado', 'todos', $url_base)); ?>"
               class="flavor-filter-tab <?php echo (!$estado_filtro || $estado_filtro === 'todos') ? 'active' : ''; ?>">
                <?php esc_html_e('Todas', 'flavor-chat-ia'); ?>
                <span class="flavor-badge"><?php echo $estadisticas_usuario['total_propuestas']; ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('estado', 'activa', $url_base)); ?>"
               class="flavor-filter-tab <?php echo $estado_filtro === 'activa' ? 'active' : ''; ?>">
                <?php esc_html_e('Activas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('estado', 'aprobada', $url_base)); ?>"
               class="flavor-filter-tab <?php echo $estado_filtro === 'aprobada' ? 'active' : ''; ?>">
                <?php esc_html_e('Aprobadas', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('estado', 'borrador', $url_base)); ?>"
               class="flavor-filter-tab <?php echo $estado_filtro === 'borrador' ? 'active' : ''; ?>">
                <?php esc_html_e('Borradores', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Lista de Propuestas -->
    <?php if (empty($propuestas_usuario)): ?>
        <div class="flavor-empty-state">
            <div class="flavor-empty-icon">
                <span class="dashicons dashicons-lightbulb"></span>
            </div>
            <h3><?php esc_html_e('No tienes propuestas', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Comparte tus ideas para mejorar la comunidad creando tu primera propuesta.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('participacion', 'crear')); ?>" class="flavor-btn flavor-btn-primary">
                <?php esc_html_e('Crear mi primera propuesta', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="flavor-propuestas-list">
            <?php foreach ($propuestas_usuario as $propuesta):
                $estado_info = $mapa_estados[$propuesta->estado] ?? ['etiqueta' => ucfirst($propuesta->estado), 'color' => '#6b7280', 'icono' => 'marker'];
                $votos_favor = $propuesta->votos_favor_calc ?: $propuesta->votos_favor;
                $votos_contra = $propuesta->votos_contra_calc ?: $propuesta->votos_contra;
                $total_votos = $votos_favor + $votos_contra + ($propuesta->votos_abstencion ?? 0);
                $porcentaje_aprobacion = $total_votos > 0 ? round(($votos_favor / $total_votos) * 100) : 0;
            ?>
                <div class="flavor-propuesta-item">
                    <div class="flavor-propuesta-content">
                        <div class="flavor-propuesta-header">
                            <span class="flavor-propuesta-estado" style="background: <?php echo esc_attr($estado_info['color']); ?>20; color: <?php echo esc_attr($estado_info['color']); ?>;">
                                <span class="dashicons dashicons-<?php echo esc_attr($estado_info['icono']); ?>"></span>
                                <?php echo esc_html($estado_info['etiqueta']); ?>
                            </span>
                            <?php if ($propuesta->categoria): ?>
                                <span class="flavor-propuesta-categoria">
                                    <?php echo esc_html(ucfirst($propuesta->categoria)); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <h3 class="flavor-propuesta-titulo">
                            <a href="<?php echo esc_url(add_query_arg('propuesta_id', $propuesta->id, Flavor_Chat_Helpers::get_action_url('participacion', 'detalle'))); ?>">
                                <?php echo esc_html($propuesta->titulo); ?>
                            </a>
                        </h3>

                        <p class="flavor-propuesta-descripcion">
                            <?php echo esc_html(wp_trim_words($propuesta->descripcion, 20)); ?>
                        </p>

                        <div class="flavor-propuesta-meta">
                            <span class="flavor-meta-item">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html(date_i18n('d M Y', strtotime($propuesta->fecha_creacion))); ?>
                            </span>
                            <?php if ($total_votos > 0): ?>
                                <span class="flavor-meta-item">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                    <?php echo number_format_i18n($total_votos); ?> <?php esc_html_e('votos', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($propuesta->comentarios_count > 0): ?>
                                <span class="flavor-meta-item">
                                    <span class="dashicons dashicons-admin-comments"></span>
                                    <?php echo number_format_i18n($propuesta->comentarios_count); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flavor-propuesta-stats">
                        <?php if ($total_votos > 0): ?>
                            <div class="flavor-stat-circular" style="--porcentaje: <?php echo $porcentaje_aprobacion; ?>;">
                                <span class="flavor-stat-value"><?php echo $porcentaje_aprobacion; ?>%</span>
                                <span class="flavor-stat-label"><?php esc_html_e('Apoyo', 'flavor-chat-ia'); ?></span>
                            </div>
                            <div class="flavor-votos-detalle">
                                <span class="votos-favor">+<?php echo number_format_i18n($votos_favor); ?></span>
                                <span class="votos-contra">-<?php echo number_format_i18n($votos_contra); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="flavor-sin-votos">
                                <span class="dashicons dashicons-marker"></span>
                                <span><?php esc_html_e('Sin votos aún', 'flavor-chat-ia'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flavor-propuesta-actions">
                        <a href="<?php echo esc_url(add_query_arg('propuesta_id', $propuesta->id, Flavor_Chat_Helpers::get_action_url('participacion', 'detalle'))); ?>"
                           class="flavor-btn flavor-btn-sm flavor-btn-outline"
                           title="<?php esc_attr_e('Ver detalles', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="flavor-pagination">
                <?php if ($pagina_actual > 1): ?>
                    <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <span class="dashicons dashicons-arrow-left-alt2"></span>
                        <?php esc_html_e('Anterior', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>

                <span class="flavor-pagination-info">
                    <?php printf(
                        esc_html__('Página %d de %d', 'flavor-chat-ia'),
                        $pagina_actual,
                        $total_paginas
                    ); ?>
                </span>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <?php esc_html_e('Siguiente', 'flavor-chat-ia'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
.flavor-mis-propuestas-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.flavor-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.flavor-section-header h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 24px;
    color: #1f2937;
}

.flavor-subtitle {
    margin: 8px 0 0;
    color: #6b7280;
}

.flavor-kpi-grid-4 {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-kpi-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.flavor-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-kpi-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.flavor-kpi-content {
    display: flex;
    flex-direction: column;
}

.flavor-kpi-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
}

.flavor-kpi-label {
    font-size: 13px;
    color: #6b7280;
}

.flavor-filters-bar {
    margin-bottom: 24px;
}

.flavor-filter-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.flavor-filter-tab {
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    color: #6b7280;
    background: #f3f4f6;
    font-size: 14px;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-filter-tab:hover {
    background: #e5e7eb;
    color: #374151;
}

.flavor-filter-tab.active {
    background: #2563eb;
    color: #fff;
}

.flavor-filter-tab .flavor-badge {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
}

.flavor-propuestas-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.flavor-propuesta-item {
    display: grid;
    grid-template-columns: 1fr auto auto;
    gap: 24px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    align-items: center;
}

.flavor-propuesta-header {
    display: flex;
    gap: 10px;
    margin-bottom: 8px;
}

.flavor-propuesta-estado {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.flavor-propuesta-estado .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.flavor-propuesta-categoria {
    padding: 4px 10px;
    background: #f3f4f6;
    color: #6b7280;
    border-radius: 20px;
    font-size: 12px;
}

.flavor-propuesta-titulo {
    margin: 0 0 8px;
    font-size: 18px;
}

.flavor-propuesta-titulo a {
    color: #1f2937;
    text-decoration: none;
}

.flavor-propuesta-titulo a:hover {
    color: #2563eb;
}

.flavor-propuesta-descripcion {
    margin: 0 0 12px;
    color: #6b7280;
    font-size: 14px;
    line-height: 1.5;
}

.flavor-propuesta-meta {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.flavor-meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #9ca3af;
}

.flavor-meta-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.flavor-propuesta-stats {
    text-align: center;
    min-width: 100px;
}

.flavor-stat-circular {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.flavor-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #10b981;
}

.flavor-stat-label {
    font-size: 12px;
    color: #6b7280;
}

.flavor-votos-detalle {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 8px;
    font-size: 14px;
}

.votos-favor { color: #10b981; font-weight: 600; }
.votos-contra { color: #ef4444; font-weight: 600; }

.flavor-sin-votos {
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #9ca3af;
    font-size: 13px;
}

.flavor-propuesta-actions {
    display: flex;
    gap: 8px;
}

.flavor-btn-sm {
    padding: 8px 12px;
}

.flavor-btn-sm .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.flavor-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.flavor-empty-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: #fef3c7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-empty-icon .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #d97706;
}

.flavor-empty-state h3 {
    margin: 0 0 10px;
    color: #1f2937;
}

.flavor-empty-state p {
    margin: 0 0 20px;
    color: #6b7280;
}

.flavor-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    margin-top: 24px;
}

.flavor-pagination-info {
    color: #6b7280;
    font-size: 14px;
}

.flavor-alert {
    padding: 16px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-alert-warning {
    background: #fef3c7;
    color: #92400e;
}

@media (max-width: 768px) {
    .flavor-propuesta-item {
        grid-template-columns: 1fr;
    }

    .flavor-propuesta-stats,
    .flavor-propuesta-actions {
        justify-content: flex-start;
    }
}
</style>
