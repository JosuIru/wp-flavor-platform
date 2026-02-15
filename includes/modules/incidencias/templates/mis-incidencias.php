<?php
/**
 * Template: Mis Incidencias
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="incidencias-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesión para ver tus incidencias', 'flavor-chat-ia') . '</h3>';
    echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
$usuario_id = get_current_user_id();

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_incidencias)) {
    echo '<div class="incidencias-empty"><p>' . esc_html__('El módulo de incidencias no está configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Obtener incidencias del usuario
$incidencias = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_incidencias WHERE usuario_id = %d AND estado != 'eliminada' ORDER BY created_at DESC",
    $usuario_id
));

// Estadísticas
$stats = [
    'total' => count($incidencias),
    'pendientes' => 0,
    'en_proceso' => 0,
    'resueltas' => 0,
];

foreach ($incidencias as $incidencia) {
    if (isset($stats[$incidencia->estado])) {
        $stats[$incidencia->estado]++;
    } elseif ($incidencia->estado === 'pendiente') {
        $stats['pendientes']++;
    } elseif ($incidencia->estado === 'en_proceso') {
        $stats['en_proceso']++;
    } elseif (in_array($incidencia->estado, ['resuelta', 'cerrada'])) {
        $stats['resueltas']++;
    }
}

$estados_labels = [
    'pendiente' => __('Pendiente', 'flavor-chat-ia'),
    'en_proceso' => __('En proceso', 'flavor-chat-ia'),
    'resuelta' => __('Resuelta', 'flavor-chat-ia'),
    'cerrada' => __('Cerrada', 'flavor-chat-ia'),
];

$estados_colors = [
    'pendiente' => '#f59e0b',
    'en_proceso' => '#3b82f6',
    'resuelta' => '#10b981',
    'cerrada' => '#6b7280',
];
?>

<div class="mis-incidencias-wrapper">
    <div class="incidencias-header">
        <h2><?php esc_html_e('Mis Incidencias', 'flavor-chat-ia'); ?></h2>
        <a href="<?php echo esc_url(add_query_arg('vista', 'reportar', get_permalink())); ?>" class="btn btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nueva incidencia', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Resumen -->
    <div class="incidencias-stats">
        <div class="stat-card">
            <span class="stat-value"><?php echo esc_html($stats['total']); ?></span>
            <span class="stat-label"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="stat-card warning">
            <span class="stat-value"><?php echo esc_html($stats['pendientes']); ?></span>
            <span class="stat-label"><?php esc_html_e('Pendientes', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="stat-card info">
            <span class="stat-value"><?php echo esc_html($stats['en_proceso']); ?></span>
            <span class="stat-label"><?php esc_html_e('En proceso', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="stat-card success">
            <span class="stat-value"><?php echo esc_html($stats['resueltas']); ?></span>
            <span class="stat-label"><?php esc_html_e('Resueltas', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Listado -->
    <?php if ($incidencias): ?>
        <div class="incidencias-lista">
            <?php foreach ($incidencias as $incidencia): ?>
                <div class="incidencia-item">
                    <div class="incidencia-estado-indicator" style="background: <?php echo esc_attr($estados_colors[$incidencia->estado] ?? '#6b7280'); ?>"></div>
                    <div class="incidencia-content">
                        <div class="incidencia-header">
                            <h4><?php echo esc_html($incidencia->titulo); ?></h4>
                            <span class="incidencia-estado" style="background: <?php echo esc_attr($estados_colors[$incidencia->estado] ?? '#6b7280'); ?>">
                                <?php echo esc_html($estados_labels[$incidencia->estado] ?? ucfirst($incidencia->estado)); ?>
                            </span>
                        </div>
                        <?php if (!empty($incidencia->ubicacion)): ?>
                            <div class="incidencia-ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($incidencia->ubicacion); ?>
                            </div>
                        <?php endif; ?>
                        <p class="incidencia-descripcion"><?php echo esc_html(wp_trim_words($incidencia->descripcion, 25)); ?></p>
                        <div class="incidencia-meta">
                            <span class="incidencia-fecha">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($incidencia->created_at))); ?>
                            </span>
                            <?php if (!empty($incidencia->tipo)): ?>
                                <span class="incidencia-tipo"><?php echo esc_html(ucfirst($incidencia->tipo)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="incidencia-actions">
                        <a href="<?php echo esc_url(add_query_arg('incidencia_id', $incidencia->id, get_permalink())); ?>" class="btn btn-sm btn-outline">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="incidencias-empty">
            <span class="dashicons dashicons-flag"></span>
            <h3><?php esc_html_e('No has reportado incidencias', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Cuando reportes una incidencia, aparecerá aquí para que puedas seguir su estado.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(add_query_arg('vista', 'reportar', get_permalink())); ?>" class="btn btn-primary">
                <?php esc_html_e('Reportar incidencia', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.mis-incidencias-wrapper { max-width: 900px; margin: 0 auto; }
.incidencias-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.incidencias-login-required .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; display: block; margin: 0 auto 1rem; }
.incidencias-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
.incidencias-header h2 { margin: 0; font-size: 1.5rem; color: #1f2937; }
.incidencias-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
.stat-card { background: white; border-radius: 10px; padding: 1.25rem; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.stat-card .stat-value { display: block; font-size: 1.75rem; font-weight: 700; color: #1f2937; }
.stat-card .stat-label { font-size: 0.8rem; color: #6b7280; }
.stat-card.warning { border-top: 3px solid #f59e0b; }
.stat-card.info { border-top: 3px solid #3b82f6; }
.stat-card.success { border-top: 3px solid #10b981; }
.incidencias-lista { display: flex; flex-direction: column; gap: 0.75rem; }
.incidencia-item { display: flex; align-items: stretch; background: white; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); overflow: hidden; }
.incidencia-estado-indicator { width: 5px; flex-shrink: 0; }
.incidencia-content { flex: 1; padding: 1rem 1.25rem; }
.incidencia-content h4 { margin: 0 0 0.35rem; font-size: 1rem; color: #1f2937; }
.incidencia-estado { display: inline-block; padding: 2px 8px; border-radius: 4px; color: white; font-size: 0.7rem; font-weight: 500; margin-left: 0.5rem; vertical-align: middle; }
.incidencia-ubicacion { font-size: 0.85rem; color: #6b7280; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.35rem; }
.incidencia-ubicacion .dashicons { font-size: 14px; width: 14px; height: 14px; }
.incidencia-descripcion { margin: 0 0 0.5rem; font-size: 0.875rem; color: #6b7280; }
.incidencia-meta { font-size: 0.8rem; color: #9ca3af; display: flex; gap: 1rem; }
.incidencia-tipo { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
.incidencia-actions { display: flex; align-items: center; padding: 1rem; }
.incidencias-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.incidencias-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.incidencias-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.incidencias-empty p { margin: 0 0 1.5rem; color: #6b7280; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #ef4444; color: white; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8rem; }
@media (max-width: 640px) {
    .incidencias-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>
