<?php
/**
 * Dashboard de Campañas - Vista Admin
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Obtener estadísticas reales
$tabla_campanias = $wpdb->prefix . 'flavor_campanias';
$tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';
$tabla_acciones = $wpdb->prefix . 'flavor_campanias_acciones';

// Verificar si las tablas existen
$tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_campanias)) === $tabla_campanias;

if ($tabla_existe) {
    $total_campanias = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanias}");
    $activas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanias} WHERE estado = 'activa'");
    $planificadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanias} WHERE estado = 'planificada'");
    $completadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_campanias} WHERE estado = 'completada'");
    $total_firmas = (int) $wpdb->get_var("SELECT COALESCE(SUM(firmas_actuales), 0) FROM {$tabla_campanias}");
    $objetivo_firmas = (int) $wpdb->get_var("SELECT COALESCE(SUM(objetivo_firmas), 0) FROM {$tabla_campanias}");

    // Participantes
    $tabla_participantes_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_participantes)) === $tabla_participantes;
    $total_participantes = $tabla_participantes_existe ? (int) $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$tabla_participantes}") : 0;

    // Acciones programadas
    $tabla_acciones_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_acciones)) === $tabla_acciones;
    $proximas_acciones = $tabla_acciones_existe ? (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$tabla_acciones}
        WHERE fecha_accion >= CURDATE()
        AND estado != 'cancelada'
    ") : 0;

    // Por tipo
    $por_tipo = $wpdb->get_results("
        SELECT tipo, COUNT(*) as total
        FROM {$tabla_campanias}
        GROUP BY tipo
        ORDER BY total DESC
    ", ARRAY_A) ?: [];

    // Por estado
    $por_estado = $wpdb->get_results("
        SELECT estado, COUNT(*) as total
        FROM {$tabla_campanias}
        GROUP BY estado
        ORDER BY total DESC
    ", ARRAY_A) ?: [];

    // Campañas activas con progreso
    $campanias_activas = $wpdb->get_results("
        SELECT id, titulo, tipo, estado, firmas_actuales, objetivo_firmas, fecha_inicio, fecha_fin
        FROM {$tabla_campanias}
        WHERE estado IN ('activa', 'planificada')
        ORDER BY
            CASE WHEN estado = 'activa' THEN 0 ELSE 1 END,
            created_at DESC
        LIMIT 5
    ", ARRAY_A) ?: [];

    // Próximas acciones
    $acciones_proximas = $tabla_acciones_existe ? $wpdb->get_results("
        SELECT a.id, a.titulo, a.tipo, a.fecha_accion, a.lugar, c.titulo as campania_titulo, c.id as campania_id
        FROM {$tabla_acciones} a
        LEFT JOIN {$tabla_campanias} c ON a.campania_id = c.id
        WHERE a.fecha_accion >= CURDATE()
        AND a.estado != 'cancelada'
        ORDER BY a.fecha_accion ASC
        LIMIT 5
    ", ARRAY_A) : [];

    // Campañas urgentes (próximas a terminar sin alcanzar objetivo)
    $urgentes = (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$tabla_campanias}
        WHERE estado = 'activa'
        AND fecha_fin IS NOT NULL
        AND fecha_fin <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND firmas_actuales < objetivo_firmas
    ");

    $usando_demo = $total_campanias === 0;
} else {
    $usando_demo = true;
}

// Datos de demostración si no hay datos reales
if ($usando_demo) {
    $total_campanias = 15;
    $activas = 4;
    $planificadas = 3;
    $completadas = 8;
    $total_firmas = 4250;
    $objetivo_firmas = 6000;
    $total_participantes = 312;
    $proximas_acciones = 6;
    $urgentes = 2;

    $por_tipo = [
        ['tipo' => 'recogida_firmas', 'total' => 6],
        ['tipo' => 'concentracion', 'total' => 3],
        ['tipo' => 'boicot', 'total' => 2],
        ['tipo' => 'protesta', 'total' => 2],
        ['tipo' => 'sensibilizacion', 'total' => 2],
    ];

    $por_estado = [
        ['estado' => 'completada', 'total' => 8],
        ['estado' => 'activa', 'total' => 4],
        ['estado' => 'planificada', 'total' => 3],
    ];

    $campanias_activas = [
        ['id' => 1, 'titulo' => 'Stop macro-vertedero regional', 'tipo' => 'recogida_firmas', 'estado' => 'activa', 'firmas_actuales' => 1850, 'objetivo_firmas' => 3000, 'fecha_inicio' => date('Y-m-d', strtotime('-15 days')), 'fecha_fin' => date('Y-m-d', strtotime('+15 days'))],
        ['id' => 2, 'titulo' => 'Transporte público gratuito', 'tipo' => 'recogida_firmas', 'estado' => 'activa', 'firmas_actuales' => 890, 'objetivo_firmas' => 2000, 'fecha_inicio' => date('Y-m-d', strtotime('-10 days')), 'fecha_fin' => date('Y-m-d', strtotime('+20 days'))],
        ['id' => 3, 'titulo' => 'Concentración por la vivienda', 'tipo' => 'concentracion', 'estado' => 'activa', 'firmas_actuales' => 0, 'objetivo_firmas' => 0, 'fecha_inicio' => date('Y-m-d', strtotime('+5 days')), 'fecha_fin' => date('Y-m-d', strtotime('+5 days'))],
        ['id' => 4, 'titulo' => 'Boicot supermercado X', 'tipo' => 'boicot', 'estado' => 'activa', 'firmas_actuales' => 0, 'objetivo_firmas' => 0, 'fecha_inicio' => date('Y-m-d', strtotime('-30 days')), 'fecha_fin' => null],
        ['id' => 5, 'titulo' => 'Campaña sensibilización climática', 'tipo' => 'sensibilizacion', 'estado' => 'planificada', 'firmas_actuales' => 0, 'objetivo_firmas' => 0, 'fecha_inicio' => date('Y-m-d', strtotime('+10 days')), 'fecha_fin' => date('Y-m-d', strtotime('+40 days'))],
    ];

    $acciones_proximas = [
        ['id' => 1, 'titulo' => 'Mesa informativa plaza mayor', 'tipo' => 'mesa_informativa', 'fecha_accion' => date('Y-m-d', strtotime('+2 days')), 'lugar' => 'Plaza Mayor', 'campania_titulo' => 'Stop macro-vertedero regional', 'campania_id' => 1],
        ['id' => 2, 'titulo' => 'Concentración Ayuntamiento', 'tipo' => 'concentracion', 'fecha_accion' => date('Y-m-d', strtotime('+5 days')), 'lugar' => 'Plaza del Ayuntamiento', 'campania_titulo' => 'Concentración por la vivienda', 'campania_id' => 3],
        ['id' => 3, 'titulo' => 'Reparto octavillas centro', 'tipo' => 'reparto', 'fecha_accion' => date('Y-m-d', strtotime('+7 days')), 'lugar' => 'Centro ciudad', 'campania_titulo' => 'Transporte público gratuito', 'campania_id' => 2],
    ];
}

// Labels para tipos
$tipos_labels = [
    'protesta' => 'Protesta',
    'recogida_firmas' => 'Recogida de firmas',
    'concentracion' => 'Concentración',
    'boicot' => 'Boicot',
    'sensibilizacion' => 'Sensibilización',
    'accion_directa' => 'Acción directa',
    'denuncia_publica' => 'Denuncia pública',
];

// Labels para estados
$estados_labels = [
    'planificada' => 'Planificada',
    'activa' => 'Activa',
    'pausada' => 'Pausada',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
];

// Badge classes para estados
$estado_badge_classes = [
    'planificada' => 'dm-badge--info',
    'activa' => 'dm-badge--success',
    'pausada' => 'dm-badge--warning',
    'completada' => 'dm-badge--secondary',
    'cancelada' => 'dm-badge--error',
];

// Badge classes para tipos
$tipo_badge_classes = [
    'protesta' => 'dm-badge--error',
    'recogida_firmas' => 'dm-badge--primary',
    'concentracion' => 'dm-badge--warning',
    'boicot' => 'dm-badge--purple',
    'sensibilizacion' => 'dm-badge--success',
    'accion_directa' => 'dm-badge--pink',
    'denuncia_publica' => 'dm-badge--info',
];

// Calcular porcentaje de firmas
$porcentaje_firmas = $objetivo_firmas > 0 ? round(($total_firmas / $objetivo_firmas) * 100, 1) : 0;
?>

<div class="dm-dashboard">
    <?php if ($usando_demo): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Modo demostración:', 'flavor-chat-ia'); ?></strong>
        <?php esc_html_e('Se muestran datos de ejemplo. Los datos reales aparecerán cuando se creen campañas.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-megaphone"></span>
            <div>
                <h1><?php esc_html_e('Campañas Ciudadanas', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Gestiona campañas de movilización, recogida de firmas y acciones colectivas', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(home_url('/campanias/crear/')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva Campaña', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Alertas urgentes -->
    <?php if ($urgentes > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-warning"></span>
        <strong><?php esc_html_e('Atención:', 'flavor-chat-ia'); ?></strong>
        <?php printf(
            esc_html__('Hay %s campaña(s) que terminan pronto sin alcanzar su objetivo.', 'flavor-chat-ia'),
            '<strong>' . $urgentes . '</strong>'
        ); ?>
        <a href="<?php echo esc_url(home_url('/campanias/?filtro=urgentes')); ?>"><?php esc_html_e('Ver urgentes →', 'flavor-chat-ia'); ?></a>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid dm-stats-grid--6">
        <div class="dm-stat-card dm-stat-card--primary">
            <span class="dashicons dashicons-flag dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_campanias); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Campañas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <span class="dashicons dashicons-controls-play dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($activas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Activas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <span class="dashicons dashicons-edit dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_firmas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Firmas Recogidas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_participantes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <span class="dashicons dashicons-calendar-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($proximas_acciones); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Acciones Próximas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card">
            <span class="dashicons dashicons-yes-alt dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($completadas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Completadas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="dm-quick-links">
        <h2 class="dm-quick-links__title">
            <span class="dashicons dashicons-admin-links"></span>
            <?php esc_html_e('Accesos Rápidos', 'flavor-chat-ia'); ?>
        </h2>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(home_url('/campanias/')); ?>" class="dm-quick-links__item">
                <span class="dashicons dashicons-list-view"></span>
                <span><?php esc_html_e('Todas las campañas', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/campanias/crear/')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-plus-alt"></span>
                <span><?php esc_html_e('Nueva campaña', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/campanias/?estado=activa')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-controls-play"></span>
                <span><?php esc_html_e('Campañas activas', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/campanias/acciones/')); ?>" class="dm-quick-links__item dm-quick-links__item--pink">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span><?php esc_html_e('Calendario de acciones', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/campanias/participantes/')); ?>" class="dm-quick-links__item dm-quick-links__item--warning">
                <span class="dashicons dashicons-groups"></span>
                <span><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/campanias/estadisticas/')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-chart-bar"></span>
                <span><?php esc_html_e('Estadísticas', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/campanias/')); ?>" class="dm-quick-links__item" target="_blank">
                <span class="dashicons dashicons-external"></span>
                <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <!-- Gráfico por tipo -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-category"></span>
                    <?php esc_html_e('Distribución por Tipo', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-por-tipo"></canvas>
            </div>
        </div>

        <!-- Gráfico progreso de firmas -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Progreso Global de Firmas', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-signature-progress">
                <div class="dm-signature-progress__ring">
                    <svg viewBox="0 0 36 36">
                        <path class="dm-signature-progress__bg"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path class="dm-signature-progress__fill <?php echo $porcentaje_firmas >= 100 ? 'dm-signature-progress__fill--success' : ($porcentaje_firmas >= 50 ? 'dm-signature-progress__fill--primary' : 'dm-signature-progress__fill--warning'); ?>"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                            stroke-dasharray="<?php echo min($porcentaje_firmas, 100); ?>, 100"
                        />
                    </svg>
                    <div class="dm-signature-progress__center">
                        <div class="dm-signature-progress__percent"><?php echo $porcentaje_firmas; ?>%</div>
                        <div class="dm-signature-progress__label"><?php esc_html_e('alcanzado', 'flavor-chat-ia'); ?></div>
                    </div>
                </div>
                <div class="dm-signature-progress__summary">
                    <span class="dm-signature-progress__current"><?php echo number_format_i18n($total_firmas); ?></span>
                    <span class="dm-signature-progress__separator"><?php esc_html_e('de', 'flavor-chat-ia'); ?></span>
                    <span class="dm-signature-progress__goal"><?php echo number_format_i18n($objetivo_firmas); ?></span>
                    <span class="dm-signature-progress__unit"><?php esc_html_e('firmas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <!-- Campañas activas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-flag"></span>
                    <?php esc_html_e('Campañas en Curso', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Campaña', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Progreso', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($campanias_activas)): ?>
                        <?php foreach ($campanias_activas as $campania): ?>
                        <?php
                        $progreso = 0;
                        if (!empty($campania['objetivo_firmas']) && $campania['objetivo_firmas'] > 0) {
                            $progreso = round(($campania['firmas_actuales'] / $campania['objetivo_firmas']) * 100);
                        }
                        $estado_badge = $estado_badge_classes[$campania['estado']] ?? 'dm-badge--secondary';
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(home_url('/campanias/' . $campania['id'] . '/')); ?>" class="dm-link">
                                    <?php echo esc_html(wp_trim_words($campania['titulo'], 5)); ?>
                                </a>
                                <div class="dm-table__subtitle">
                                    <span class="dm-badge dm-badge--sm <?php echo esc_attr($estado_badge); ?>">
                                        <?php echo esc_html($estados_labels[$campania['estado']] ?? $campania['estado']); ?>
                                    </span>
                                    <?php if (!empty($campania['fecha_fin'])): ?>
                                    <span class="dm-table__muted">
                                        • <?php printf(esc_html__('Hasta %s', 'flavor-chat-ia'), esc_html(date_i18n('j M', strtotime($campania['fecha_fin'])))); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="dm-badge dm-badge--sm dm-badge--secondary">
                                    <?php echo esc_html($tipos_labels[$campania['tipo']] ?? $campania['tipo']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($campania['objetivo_firmas'] > 0): ?>
                                <div class="dm-campaign-progress">
                                    <div class="dm-progress dm-progress--sm">
                                        <div class="dm-progress__fill <?php echo $progreso >= 100 ? 'dm-progress__fill--success' : ''; ?>" style="width: <?php echo min($progreso, 100); ?>%;"></div>
                                    </div>
                                    <span class="dm-campaign-progress__percent"><?php echo $progreso; ?>%</span>
                                </div>
                                <div class="dm-table__muted dm-text-xs">
                                    <?php echo number_format_i18n($campania['firmas_actuales']); ?> / <?php echo number_format_i18n($campania['objetivo_firmas']); ?>
                                </div>
                                <?php else: ?>
                                <span class="dm-text-muted dm-text-sm"><?php esc_html_e('Sin objetivo', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="dm-table__empty">
                                <span class="dashicons dashicons-flag"></span>
                                <?php esc_html_e('No hay campañas activas', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(home_url('/campanias/')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver todas las campañas', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>

        <!-- Próximas acciones -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Próximas Acciones', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Acción', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Lugar', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($acciones_proximas)): ?>
                        <?php foreach ($acciones_proximas as $accion): ?>
                        <?php
                        $dias_hasta = floor((strtotime($accion['fecha_accion']) - time()) / 86400);
                        $urgencia_badge = $dias_hasta <= 2 ? 'dm-badge--error' : ($dias_hasta <= 5 ? 'dm-badge--warning' : 'dm-badge--success');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($accion['titulo']); ?></strong>
                                <div class="dm-table__subtitle dm-text-muted">
                                    <?php echo esc_html($accion['campania_titulo']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="dm-badge dm-badge--sm <?php echo esc_attr($urgencia_badge); ?>">
                                    <?php echo esc_html(date_i18n('j M', strtotime($accion['fecha_accion']))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="dm-text-muted dm-text-sm">
                                    <?php echo esc_html(wp_trim_words($accion['lugar'] ?? __('Por definir', 'flavor-chat-ia'), 3)); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="dm-table__empty">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php esc_html_e('No hay acciones programadas', 'flavor-chat-ia'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(home_url('/campanias/acciones/')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver calendario completo', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#10b981';
    var warningColor = rootStyles.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    var errorColor = rootStyles.getPropertyValue('--dm-error').trim() || '#ef4444';
    var purpleColor = rootStyles.getPropertyValue('--dm-purple').trim() || '#8b5cf6';
    var pinkColor = rootStyles.getPropertyValue('--dm-pink').trim() || '#ec4899';
    var infoColor = rootStyles.getPropertyValue('--dm-info').trim() || '#06b6d4';

    var tipoColores = {
        'protesta': errorColor,
        'recogida_firmas': primaryColor,
        'concentracion': warningColor,
        'boicot': purpleColor,
        'sensibilizacion': successColor,
        'accion_directa': pinkColor,
        'denuncia_publica': infoColor
    };

    var tiposData = <?php echo wp_json_encode(array_map(function($t) use ($tipos_labels) {
        return [
            'tipo' => $t['tipo'],
            'label' => $tipos_labels[$t['tipo']] ?? $t['tipo'],
            'value' => (int) $t['total']
        ];
    }, $por_tipo)); ?>;

    var chartCanvas = document.getElementById('chart-por-tipo');
    if (chartCanvas) {
        new Chart(chartCanvas, {
            type: 'doughnut',
            data: {
                labels: tiposData.map(function(t) { return t.label; }),
                datasets: [{
                    data: tiposData.map(function(t) { return t.value; }),
                    backgroundColor: tiposData.map(function(t) { return tipoColores[t.tipo] || '#6b7280'; }),
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
    }
});
</script>
