<?php
/**
 * Banco de Tiempo - Intercambios (Frontend Dashboard)
 *
 * Template para mostrar los intercambios del usuario
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
if (!$usuario_id) {
    echo '<div class="fl-login-required"><p>' . esc_html__('Debes iniciar sesión para ver tus intercambios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

global $wpdb;
$tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

// Estados y sus estilos
$estados = [
    'pendiente' => [
        'label' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon' => 'dashicons-hourglass',
        'color' => '#d97706',
        'bg' => '#fef3c7',
    ],
    'aceptado' => [
        'label' => __('Aceptado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon' => 'dashicons-yes-alt',
        'color' => '#2563eb',
        'bg' => '#dbeafe',
    ],
    'en_curso' => [
        'label' => __('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon' => 'dashicons-update',
        'color' => '#7c3aed',
        'bg' => '#ede9fe',
    ],
    'completado' => [
        'label' => __('Completado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon' => 'dashicons-yes',
        'color' => '#059669',
        'bg' => '#d1fae5',
    ],
    'cancelado' => [
        'label' => __('Cancelado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon' => 'dashicons-no-alt',
        'color' => '#dc2626',
        'bg' => '#fee2e2',
    ],
    'rechazado' => [
        'label' => __('Rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icon' => 'dashicons-dismiss',
        'color' => '#9ca3af',
        'bg' => '#f3f4f6',
    ],
];

// Estadísticas del usuario
$mis_stats = [
    'pendientes' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_transacciones
         WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
         AND estado = 'pendiente'",
        $usuario_id, $usuario_id
    )),
    'en_curso' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_transacciones
         WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
         AND estado IN ('aceptado', 'en_curso')",
        $usuario_id, $usuario_id
    )),
    'completados' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_transacciones
         WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d)
         AND estado = 'completado'",
        $usuario_id, $usuario_id
    )),
];

// Intercambios activos (pendientes y en curso)
$intercambios_activos = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*, s.titulo as servicio_titulo, s.categoria,
            u_sol.display_name as solicitante_nombre,
            u_rec.display_name as receptor_nombre,
            CASE
                WHEN t.usuario_solicitante_id = %d THEN 'solicite'
                ELSE 'me_solicitaron'
            END as rol
     FROM $tabla_transacciones t
     LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
     LEFT JOIN {$wpdb->users} u_sol ON t.usuario_solicitante_id = u_sol.ID
     LEFT JOIN {$wpdb->users} u_rec ON t.usuario_receptor_id = u_rec.ID
     WHERE (t.usuario_solicitante_id = %d OR t.usuario_receptor_id = %d)
           AND t.estado IN ('pendiente', 'aceptado', 'en_curso')
     ORDER BY t.fecha_solicitud DESC",
    $usuario_id, $usuario_id, $usuario_id
));

// Historial (completados y cancelados - últimos 10)
$historial = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*, s.titulo as servicio_titulo,
            u_sol.display_name as solicitante_nombre,
            u_rec.display_name as receptor_nombre,
            CASE
                WHEN t.usuario_solicitante_id = %d THEN 'solicite'
                ELSE 'me_solicitaron'
            END as rol
     FROM $tabla_transacciones t
     LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
     LEFT JOIN {$wpdb->users} u_sol ON t.usuario_solicitante_id = u_sol.ID
     LEFT JOIN {$wpdb->users} u_rec ON t.usuario_receptor_id = u_rec.ID
     WHERE (t.usuario_solicitante_id = %d OR t.usuario_receptor_id = %d)
           AND t.estado IN ('completado', 'cancelado', 'rechazado')
     ORDER BY COALESCE(t.fecha_completado, t.fecha_solicitud) DESC
     LIMIT 10",
    $usuario_id, $usuario_id, $usuario_id
));
?>

<div class="fl-banco-tiempo-intercambios">
    <!-- Resumen de estadísticas -->
    <div class="fl-exchange-stats">
        <div class="fl-stat-pill fl-stat-pending">
            <span class="fl-stat-icon"><span class="dashicons dashicons-hourglass"></span></span>
            <span class="fl-stat-value"><?php echo intval($mis_stats['pendientes']); ?></span>
            <span class="fl-stat-label"><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="fl-stat-pill fl-stat-active">
            <span class="fl-stat-icon"><span class="dashicons dashicons-update"></span></span>
            <span class="fl-stat-value"><?php echo intval($mis_stats['en_curso']); ?></span>
            <span class="fl-stat-label"><?php esc_html_e('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="fl-stat-pill fl-stat-completed">
            <span class="fl-stat-icon"><span class="dashicons dashicons-yes"></span></span>
            <span class="fl-stat-value"><?php echo intval($mis_stats['completados']); ?></span>
            <span class="fl-stat-label"><?php esc_html_e('Completados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Intercambios activos -->
    <div class="fl-section">
        <div class="fl-section-header">
            <h3 class="fl-section-title">
                <span class="dashicons dashicons-randomize"></span>
                <?php esc_html_e('Intercambios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>

        <?php if (!empty($intercambios_activos)) : ?>
        <div class="fl-exchanges-list">
            <?php foreach ($intercambios_activos as $intercambio) :
                $estado_info = $estados[$intercambio->estado] ?? $estados['pendiente'];
                $otra_persona = $intercambio->rol === 'solicite'
                    ? $intercambio->receptor_nombre
                    : $intercambio->solicitante_nombre;
                $otra_persona_id = $intercambio->rol === 'solicite'
                    ? $intercambio->usuario_receptor_id
                    : $intercambio->usuario_solicitante_id;
            ?>
            <div class="fl-exchange-card fl-exchange-<?php echo esc_attr($intercambio->estado); ?>">
                <div class="fl-exchange-left">
                    <div class="fl-exchange-avatar">
                        <?php echo get_avatar($otra_persona_id, 48); ?>
                    </div>
                    <div class="fl-exchange-info">
                        <h4 class="fl-exchange-title"><?php echo esc_html($intercambio->servicio_titulo ?: __('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></h4>
                        <p class="fl-exchange-meta">
                            <?php if ($intercambio->rol === 'solicite') : ?>
                                <span class="fl-exchange-direction fl-direction-out">
                                    <span class="dashicons dashicons-arrow-right-alt"></span>
                                    <?php esc_html_e('Solicité a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            <?php else : ?>
                                <span class="fl-exchange-direction fl-direction-in">
                                    <span class="dashicons dashicons-arrow-left-alt"></span>
                                    <?php esc_html_e('Me solicitó', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            <?php endif; ?>
                            <strong><?php echo esc_html($otra_persona); ?></strong>
                        </p>
                        <p class="fl-exchange-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(date_i18n('j M Y, H:i', strtotime($intercambio->fecha_solicitud))); ?>
                        </p>
                    </div>
                </div>
                <div class="fl-exchange-right">
                    <div class="fl-exchange-hours">
                        <span class="fl-hours-value"><?php echo number_format($intercambio->horas, 1); ?>h</span>
                    </div>
                    <span class="fl-exchange-status" style="background: <?php echo esc_attr($estado_info['bg']); ?>; color: <?php echo esc_attr($estado_info['color']); ?>;">
                        <span class="dashicons <?php echo esc_attr($estado_info['icon']); ?>"></span>
                        <?php echo esc_html($estado_info['label']); ?>
                    </span>
                    <?php if ($intercambio->estado === 'pendiente' && $intercambio->rol === 'me_solicitaron') : ?>
                    <div class="fl-exchange-actions">
                        <button type="button" class="fl-btn fl-btn-success fl-btn-sm bt-btn-aceptar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Aceptar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="button" class="fl-btn fl-btn-danger fl-btn-sm bt-btn-rechazar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                            <span class="dashicons dashicons-no"></span>
                            <?php esc_html_e('Rechazar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                    <?php elseif ($intercambio->estado === 'pendiente' && $intercambio->rol === 'solicite') : ?>
                    <div class="fl-exchange-actions">
                        <button type="button" class="fl-btn fl-btn-outline fl-btn-sm bt-btn-cancelar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                            <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                    <?php elseif ($intercambio->estado === 'aceptado' || $intercambio->estado === 'en_curso') : ?>
                    <div class="fl-exchange-actions">
                        <button type="button" class="fl-btn fl-btn-primary fl-btn-sm bt-btn-completar-intercambio" data-intercambio-id="<?php echo esc_attr($intercambio->id); ?>">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Completar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <div class="fl-empty-state">
            <span class="dashicons dashicons-randomize"></span>
            <p><?php esc_html_e('No tienes intercambios activos. ¡Explora los servicios disponibles!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('banco_tiempo', '')); ?>" class="fl-btn fl-btn-outline">
                <?php esc_html_e('Ver servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Historial -->
    <?php if (!empty($historial)) : ?>
    <div class="fl-section">
        <div class="fl-section-header">
            <h3 class="fl-section-title">
                <span class="dashicons dashicons-backup"></span>
                <?php esc_html_e('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
        </div>
        <div class="fl-history-list">
            <?php foreach ($historial as $item) :
                $estado_info = $estados[$item->estado] ?? $estados['completado'];
                $otra_persona = $item->rol === 'solicite' ? $item->receptor_nombre : $item->solicitante_nombre;
            ?>
            <div class="fl-history-item">
                <div class="fl-history-icon" style="background: <?php echo esc_attr($estado_info['bg']); ?>; color: <?php echo esc_attr($estado_info['color']); ?>;">
                    <span class="dashicons <?php echo esc_attr($estado_info['icon']); ?>"></span>
                </div>
                <div class="fl-history-content">
                    <span class="fl-history-title"><?php echo esc_html($item->servicio_titulo ?: __('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                    <span class="fl-history-meta">
                        <?php echo $item->rol === 'solicite' ? esc_html__('con', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php echo esc_html($otra_persona); ?>
                        •
                        <?php echo esc_html(date_i18n('j M Y', strtotime($item->fecha_completado ?: $item->fecha_solicitud))); ?>
                    </span>
                </div>
                <div class="fl-history-hours <?php echo $item->rol === 'me_solicitaron' ? 'fl-hours-positive' : 'fl-hours-negative'; ?>">
                    <?php echo $item->rol === 'me_solicitaron' ? '+' : '-'; ?><?php echo number_format($item->horas, 1); ?>h
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.fl-banco-tiempo-intercambios {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Estadísticas */
.fl-exchange-stats {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.fl-stat-pill {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 50px;
    background: white;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.fl-stat-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fl-stat-icon .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.fl-stat-pending .fl-stat-icon {
    background: #fef3c7;
    color: #d97706;
}

.fl-stat-active .fl-stat-icon {
    background: #ede9fe;
    color: #7c3aed;
}

.fl-stat-completed .fl-stat-icon {
    background: #d1fae5;
    color: #059669;
}

.fl-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
}

.fl-stat-label {
    font-size: 0.8125rem;
    color: #6b7280;
}

/* Secciones */
.fl-section {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.fl-section-header {
    margin-bottom: 1rem;
}

.fl-section-title {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.fl-section-title .dashicons {
    color: #6366f1;
}

/* Lista de intercambios */
.fl-exchanges-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.fl-exchange-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    gap: 1rem;
}

.fl-exchange-pendiente {
    border-left: 3px solid #d97706;
}

.fl-exchange-aceptado,
.fl-exchange-en_curso {
    border-left: 3px solid #7c3aed;
}

.fl-exchange-left {
    display: flex;
    align-items: center;
    gap: 0.875rem;
    flex: 1;
    min-width: 0;
}

.fl-exchange-avatar img {
    border-radius: 50%;
    width: 48px;
    height: 48px;
}

.fl-exchange-info {
    min-width: 0;
}

.fl-exchange-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fl-exchange-meta {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0 0 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.fl-exchange-direction {
    display: inline-flex;
    align-items: center;
    gap: 0.125rem;
}

.fl-exchange-direction .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.fl-direction-out {
    color: #dc2626;
}

.fl-direction-in {
    color: #059669;
}

.fl-exchange-date {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.fl-exchange-date .dashicons {
    font-size: 12px;
    width: 12px;
    height: 12px;
}

.fl-exchange-right {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.fl-exchange-hours {
    text-align: right;
}

.fl-hours-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #6366f1;
}

.fl-exchange-status {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.375rem 0.625rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.fl-exchange-status .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.fl-exchange-actions {
    display: flex;
    gap: 0.375rem;
}

/* Botones */
.fl-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
}

.fl-btn-sm {
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
}

.fl-btn-primary {
    background: #6366f1;
    color: white;
}

.fl-btn-primary:hover {
    background: #4f46e5;
}

.fl-btn-success {
    background: #059669;
    color: white;
}

.fl-btn-success:hover {
    background: #047857;
}

.fl-btn-danger {
    background: #fee2e2;
    color: #dc2626;
}

.fl-btn-danger:hover {
    background: #fecaca;
}

.fl-btn-outline {
    background: white;
    border: 1px solid #e5e7eb;
    color: #4b5563;
}

.fl-btn-outline:hover {
    border-color: #6366f1;
    color: #6366f1;
}

/* Historial */
.fl-history-list {
    display: flex;
    flex-direction: column;
}

.fl-history-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.fl-history-item:last-child {
    border-bottom: none;
}

.fl-history-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.fl-history-icon .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.fl-history-content {
    flex: 1;
    min-width: 0;
}

.fl-history-title {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fl-history-meta {
    font-size: 0.75rem;
    color: #9ca3af;
}

.fl-history-hours {
    font-size: 0.9375rem;
    font-weight: 600;
    flex-shrink: 0;
}

.fl-hours-positive {
    color: #059669;
}

.fl-hours-negative {
    color: #dc2626;
}

/* Estado vacío */
.fl-empty-state {
    text-align: center;
    padding: 2.5rem 1.5rem;
    background: #f9fafb;
    border-radius: 12px;
}

.fl-empty-state .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #9ca3af;
    margin-bottom: 0.75rem;
}

.fl-empty-state p {
    margin: 0 0 1rem;
    color: #6b7280;
}

/* Responsive */
@media (max-width: 768px) {
    .fl-exchange-stats {
        flex-direction: column;
    }

    .fl-stat-pill {
        justify-content: center;
    }

    .fl-exchange-card {
        flex-direction: column;
        align-items: stretch;
    }

    .fl-exchange-right {
        flex-wrap: wrap;
        justify-content: space-between;
        padding-top: 0.75rem;
        border-top: 1px solid #e5e7eb;
    }
}
</style>
