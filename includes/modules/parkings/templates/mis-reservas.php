<?php
/**
 * Template: Mis Reservas de Parking
 *
 * Lista de reservas del usuario actual con filtros por estado.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="parkings-login-required">';
    echo '<p>' . esc_html__('Debes iniciar sesión para ver tus reservas.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;

$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_parkings = $wpdb->prefix . 'flavor_parkings';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_reservas)) {
    echo '<div class="parkings-empty"><p>' . esc_html__('El módulo de parkings no está configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

$usuario_actual_id = get_current_user_id();

// Filtro de estado
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';

// Construir consulta
$where_conditions = ["r.user_id = %d"];
$query_params = [$usuario_actual_id];

if ($estado_filtro) {
    $where_conditions[] = "r.estado = %s";
    $query_params[] = $estado_filtro;
}

$where_sql = implode(' AND ', $where_conditions);

// Obtener reservas del usuario
$reservas = $wpdb->get_results($wpdb->prepare("
    SELECT
        r.id,
        r.fecha_inicio,
        r.fecha_fin,
        r.estado,
        r.matricula,
        r.notas,
        r.created_at,
        pl.numero AS plaza_numero,
        pl.tipo AS plaza_tipo,
        pk.nombre AS parking_nombre,
        pk.direccion AS parking_direccion
    FROM $tabla_reservas r
    LEFT JOIN $tabla_plazas pl ON r.plaza_id = pl.id
    LEFT JOIN $tabla_parkings pk ON pl.parking_id = pk.id
    WHERE $where_sql
    ORDER BY r.fecha_inicio DESC
    LIMIT 50
", $query_params));

// Contar por estados
$estados_count = $wpdb->get_results($wpdb->prepare("
    SELECT estado, COUNT(*) as total
    FROM $tabla_reservas
    WHERE user_id = %d
    GROUP BY estado
", $usuario_actual_id), OBJECT_K);

$estados_labels = [
    'activa' => __('Activa', 'flavor-chat-ia'),
    'pendiente' => __('Pendiente', 'flavor-chat-ia'),
    'confirmada' => __('Confirmada', 'flavor-chat-ia'),
    'completada' => __('Completada', 'flavor-chat-ia'),
    'cancelada' => __('Cancelada', 'flavor-chat-ia'),
    'expirada' => __('Expirada', 'flavor-chat-ia'),
];

$estados_colores = [
    'activa' => '#10b981',
    'pendiente' => '#f59e0b',
    'confirmada' => '#3b82f6',
    'completada' => '#6b7280',
    'cancelada' => '#ef4444',
    'expirada' => '#9ca3af',
];

$parkings_base_url = home_url('/mi-portal/parkings/');
?>

<div class="parkings-mis-reservas">
    <header class="reservas-header">
        <div class="reservas-header__titulo">
            <h2><?php esc_html_e('Mis Reservas', 'flavor-chat-ia'); ?></h2>
            <p><?php esc_html_e('Gestiona tus reservas de parking', 'flavor-chat-ia'); ?></p>
        </div>
        <a href="<?php echo esc_url($parkings_base_url . 'disponibilidad/'); ?>" class="btn btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nueva reserva', 'flavor-chat-ia'); ?>
        </a>
    </header>

    <!-- Tabs de filtro -->
    <div class="reservas-tabs">
        <a href="<?php echo esc_url(remove_query_arg('estado')); ?>"
           class="reservas-tab <?php echo empty($estado_filtro) ? 'activo' : ''; ?>">
            <?php esc_html_e('Todas', 'flavor-chat-ia'); ?>
            <span class="tab-count"><?php echo esc_html(array_sum(array_column((array)$estados_count, 'total'))); ?></span>
        </a>
        <?php foreach ($estados_labels as $estado_key => $estado_label):
            $count = isset($estados_count[$estado_key]) ? $estados_count[$estado_key]->total : 0;
            if ($count === 0 && $estado_key !== 'activa') continue;
        ?>
            <a href="<?php echo esc_url(add_query_arg('estado', $estado_key)); ?>"
               class="reservas-tab <?php echo $estado_filtro === $estado_key ? 'activo' : ''; ?>">
                <?php echo esc_html($estado_label); ?>
                <span class="tab-count"><?php echo esc_html($count); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Lista de reservas -->
    <?php if ($reservas): ?>
        <div class="reservas-lista">
            <?php foreach ($reservas as $reserva):
                $fecha_inicio = strtotime($reserva->fecha_inicio);
                $fecha_fin = strtotime($reserva->fecha_fin);
                $ahora = time();

                // Determinar si la reserva está activa ahora
                $es_activa_ahora = ($reserva->estado === 'activa' || $reserva->estado === 'confirmada')
                                   && $ahora >= $fecha_inicio && $ahora <= $fecha_fin;

                // Calcular tiempo restante si está activa
                $tiempo_restante = '';
                if ($es_activa_ahora) {
                    $diferencia = $fecha_fin - $ahora;
                    $horas = floor($diferencia / 3600);
                    $minutos = floor(($diferencia % 3600) / 60);
                    $tiempo_restante = sprintf(__('%dh %dm restantes', 'flavor-chat-ia'), $horas, $minutos);
                }

                $estado_color = $estados_colores[$reserva->estado] ?? '#6b7280';
            ?>
                <article class="reserva-card <?php echo $es_activa_ahora ? 'reserva-card--activa' : ''; ?>">
                    <div class="reserva-card__estado">
                        <span class="estado-badge" style="background: <?php echo esc_attr($estado_color); ?>">
                            <?php echo esc_html($estados_labels[$reserva->estado] ?? ucfirst($reserva->estado)); ?>
                        </span>
                        <?php if ($tiempo_restante): ?>
                            <span class="tiempo-restante"><?php echo esc_html($tiempo_restante); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="reserva-card__contenido">
                        <div class="reserva-info">
                            <h3 class="reserva-parking"><?php echo esc_html($reserva->parking_nombre); ?></h3>
                            <p class="reserva-direccion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($reserva->parking_direccion); ?>
                            </p>
                            <div class="reserva-detalles">
                                <span class="detalle-item">
                                    <span class="dashicons dashicons-car"></span>
                                    <?php printf(esc_html__('Plaza %s', 'flavor-chat-ia'), $reserva->plaza_numero); ?>
                                </span>
                                <?php if ($reserva->matricula): ?>
                                    <span class="detalle-item">
                                        <span class="dashicons dashicons-admin-network"></span>
                                        <?php echo esc_html($reserva->matricula); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="reserva-fechas">
                            <div class="fecha-grupo">
                                <span class="fecha-label"><?php esc_html_e('Entrada', 'flavor-chat-ia'); ?></span>
                                <span class="fecha-valor"><?php echo date_i18n('d M, H:i', $fecha_inicio); ?></span>
                            </div>
                            <div class="fecha-separador">
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                            </div>
                            <div class="fecha-grupo">
                                <span class="fecha-label"><?php esc_html_e('Salida', 'flavor-chat-ia'); ?></span>
                                <span class="fecha-valor"><?php echo date_i18n('d M, H:i', $fecha_fin); ?></span>
                            </div>
                        </div>
                    </div>

                    <?php if ($reserva->estado === 'activa' || $reserva->estado === 'pendiente' || $reserva->estado === 'confirmada'): ?>
                        <footer class="reserva-card__acciones">
                            <?php if ($reserva->estado === 'pendiente'): ?>
                                <button class="btn btn-outline btn-sm" data-action="cancelar" data-reserva="<?php echo esc_attr($reserva->id); ?>">
                                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                            <a href="<?php echo esc_url(add_query_arg('reserva', $reserva->id, $parkings_base_url . 'detalle/')); ?>" class="btn btn-primary btn-sm">
                                <?php esc_html_e('Ver detalles', 'flavor-chat-ia'); ?>
                            </a>
                        </footer>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="reservas-empty-state">
            <span class="dashicons dashicons-calendar-alt"></span>
            <h3><?php esc_html_e('No tienes reservas', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Cuando reserves una plaza de parking, aparecerá aquí.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url($parkings_base_url . 'disponibilidad/'); ?>" class="btn btn-primary">
                <?php esc_html_e('Buscar parking', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
.parkings-mis-reservas { max-width: 900px; margin: 0 auto; }

.reservas-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.reservas-header__titulo h2 { margin: 0 0 0.25rem; font-size: 1.5rem; color: #1f2937; }
.reservas-header__titulo p { margin: 0; color: #6b7280; font-size: 0.9rem; }

.reservas-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e5e7eb; overflow-x: auto; }
.reservas-tab { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; color: #6b7280; text-decoration: none; font-size: 0.875rem; border-radius: 6px; transition: all 0.2s; white-space: nowrap; }
.reservas-tab:hover { color: #1f2937; background: #f3f4f6; }
.reservas-tab.activo { color: #3b82f6; background: #eff6ff; font-weight: 500; }
.tab-count { background: #e5e7eb; padding: 0.125rem 0.5rem; border-radius: 10px; font-size: 0.75rem; }
.reservas-tab.activo .tab-count { background: #dbeafe; color: #3b82f6; }

.reservas-lista { display: flex; flex-direction: column; gap: 1rem; }

.reserva-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; }
.reserva-card--activa { border-left: 4px solid #10b981; }

.reserva-card__estado { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 1.25rem; background: #f9fafb; }
.estado-badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 20px; color: white; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.tiempo-restante { font-size: 0.8rem; color: #10b981; font-weight: 500; }

.reserva-card__contenido { padding: 1.25rem; display: grid; grid-template-columns: 1fr auto; gap: 1.5rem; align-items: center; }

.reserva-parking { margin: 0 0 0.25rem; font-size: 1.125rem; color: #1f2937; }
.reserva-direccion { margin: 0 0 0.75rem; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 0.25rem; }
.reserva-detalles { display: flex; gap: 1rem; flex-wrap: wrap; }
.detalle-item { display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.8rem; color: #4b5563; background: #f3f4f6; padding: 0.25rem 0.5rem; border-radius: 4px; }

.reserva-fechas { display: flex; align-items: center; gap: 0.75rem; text-align: center; }
.fecha-grupo { display: flex; flex-direction: column; }
.fecha-label { font-size: 0.7rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem; }
.fecha-valor { font-size: 0.9rem; font-weight: 500; color: #1f2937; white-space: nowrap; }
.fecha-separador { color: #d1d5db; }

.reserva-card__acciones { display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.75rem 1.25rem; border-top: 1px solid #f3f4f6; }

.reservas-empty-state { text-align: center; padding: 4rem 1rem; background: white; border-radius: 12px; }
.reservas-empty-state .dashicons { font-size: 3rem; width: auto; height: auto; color: #d1d5db; margin-bottom: 1rem; }
.reservas-empty-state h3 { margin: 0 0 0.5rem; color: #1f2937; }
.reservas-empty-state p { margin: 0 0 1.5rem; color: #6b7280; }

.parkings-login-required { text-align: center; padding: 3rem 1rem; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }

@media (max-width: 640px) {
    .reserva-card__contenido { grid-template-columns: 1fr; }
    .reserva-fechas { justify-content: center; margin-top: 0.5rem; padding-top: 1rem; border-top: 1px solid #f3f4f6; }
}
</style>
