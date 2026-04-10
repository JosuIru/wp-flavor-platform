<?php
/**
 * Template: Proximo viaje del usuario
 *
 * Muestra el proximo viaje programado del usuario actual,
 * ya sea como conductor o como pasajero.
 *
 * @package FlavorPlatform
 * @subpackage Modules/Carpooling
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar si el usuario esta logueado
if (!is_user_logged_in()) {
    return;
}

global $wpdb;
$usuario_id = get_current_user_id();
$tabla_viajes = $wpdb->prefix . 'flavor_carpooling_viajes';
$tabla_reservas = $wpdb->prefix . 'flavor_carpooling_reservas';

// Buscar proximo viaje como conductor
$viaje_como_conductor = $wpdb->get_row($wpdb->prepare(
    "SELECT v.*,
            (SELECT COUNT(*) FROM {$tabla_reservas} r WHERE r.viaje_id = v.id AND r.estado IN ('confirmada', 'solicitada')) as total_reservas
     FROM {$tabla_viajes} v
     WHERE v.conductor_id = %d
     AND v.estado IN ('activo', 'completo')
     AND v.fecha_salida > NOW()
     ORDER BY v.fecha_salida ASC
     LIMIT 1",
    $usuario_id
));

// Buscar proximo viaje como pasajero
$viaje_como_pasajero = $wpdb->get_row($wpdb->prepare(
    "SELECT v.*, r.id as reserva_id, r.numero_plazas, r.estado as estado_reserva, r.precio_total
     FROM {$tabla_reservas} r
     JOIN {$tabla_viajes} v ON r.viaje_id = v.id
     WHERE r.pasajero_id = %d
     AND r.estado IN ('confirmada', 'pendiente')
     AND v.fecha_salida > NOW()
     ORDER BY v.fecha_salida ASC
     LIMIT 1",
    $usuario_id
));

// Determinar cual mostrar primero (el mas cercano)
$proximo_viaje = null;
$es_conductor = false;

if ($viaje_como_conductor && $viaje_como_pasajero) {
    if (strtotime($viaje_como_conductor->fecha_salida) <= strtotime($viaje_como_pasajero->fecha_salida)) {
        $proximo_viaje = $viaje_como_conductor;
        $es_conductor = true;
    } else {
        $proximo_viaje = $viaje_como_pasajero;
        $es_conductor = false;
    }
} elseif ($viaje_como_conductor) {
    $proximo_viaje = $viaje_como_conductor;
    $es_conductor = true;
} elseif ($viaje_como_pasajero) {
    $proximo_viaje = $viaje_como_pasajero;
    $es_conductor = false;
}

// Calcular tiempo restante
$tiempo_restante = '';
$clase_urgencia = '';
if ($proximo_viaje) {
    $segundos_restantes = strtotime($proximo_viaje->fecha_salida) - time();
    $horas_restantes = floor($segundos_restantes / 3600);
    $dias_restantes = floor($horas_restantes / 24);

    if ($dias_restantes > 1) {
        $tiempo_restante = sprintf(__('En %d dias', FLAVOR_PLATFORM_TEXT_DOMAIN), $dias_restantes);
    } elseif ($dias_restantes === 1) {
        $tiempo_restante = __('Manana', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $clase_urgencia = 'flavor-carpooling-proximo--pronto';
    } elseif ($horas_restantes > 1) {
        $tiempo_restante = sprintf(__('En %d horas', FLAVOR_PLATFORM_TEXT_DOMAIN), $horas_restantes);
        $clase_urgencia = 'flavor-carpooling-proximo--muy-pronto';
    } else {
        $tiempo_restante = __('Muy pronto', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $clase_urgencia = 'flavor-carpooling-proximo--inminente';
    }
}
?>

<div class="flavor-carpooling-proximo <?php echo esc_attr($clase_urgencia); ?>">
    <?php if ($proximo_viaje) : ?>
        <!-- Header con rol y tiempo -->
        <div class="flavor-carpooling-proximo__header">
            <span class="flavor-carpooling-proximo__rol <?php echo $es_conductor ? 'rol--conductor' : 'rol--pasajero'; ?>">
                <span class="dashicons <?php echo $es_conductor ? 'dashicons-steering-wheel' : 'dashicons-groups'; ?>"></span>
                <?php echo $es_conductor ? esc_html__('Conductor', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Pasajero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
            <span class="flavor-carpooling-proximo__tiempo">
                <span class="dashicons dashicons-clock"></span>
                <?php echo esc_html($tiempo_restante); ?>
            </span>
        </div>

        <!-- Ruta del viaje -->
        <div class="flavor-carpooling-proximo__ruta">
            <div class="flavor-carpooling-proximo__punto flavor-carpooling-proximo__punto--origen">
                <span class="flavor-carpooling-proximo__punto-icono">
                    <span class="dashicons dashicons-location"></span>
                </span>
                <div class="flavor-carpooling-proximo__punto-info">
                    <span class="flavor-carpooling-proximo__punto-label"><?php esc_html_e('Origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-carpooling-proximo__punto-valor"><?php echo esc_html($proximo_viaje->origen); ?></span>
                </div>
            </div>

            <div class="flavor-carpooling-proximo__linea">
                <span class="dashicons dashicons-arrow-down-alt"></span>
            </div>

            <div class="flavor-carpooling-proximo__punto flavor-carpooling-proximo__punto--destino">
                <span class="flavor-carpooling-proximo__punto-icono">
                    <span class="dashicons dashicons-flag"></span>
                </span>
                <div class="flavor-carpooling-proximo__punto-info">
                    <span class="flavor-carpooling-proximo__punto-label"><?php esc_html_e('Destino', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-carpooling-proximo__punto-valor"><?php echo esc_html($proximo_viaje->destino); ?></span>
                </div>
            </div>
        </div>

        <!-- Detalles del viaje -->
        <div class="flavor-carpooling-proximo__detalles">
            <div class="flavor-carpooling-proximo__detalle">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span><?php echo esc_html(date_i18n('l, d M', strtotime($proximo_viaje->fecha_salida))); ?></span>
            </div>
            <div class="flavor-carpooling-proximo__detalle">
                <span class="dashicons dashicons-clock"></span>
                <span><?php echo esc_html(date_i18n('H:i', strtotime($proximo_viaje->fecha_salida))); ?></span>
            </div>

            <?php if ($es_conductor) : ?>
                <div class="flavor-carpooling-proximo__detalle">
                    <span class="dashicons dashicons-groups"></span>
                    <span>
                        <?php
                        printf(
                            esc_html__('%d de %d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            $proximo_viaje->plazas_ocupadas,
                            $proximo_viaje->plazas_disponibles + $proximo_viaje->plazas_ocupadas
                        );
                        ?>
                    </span>
                </div>
                <?php if ($proximo_viaje->total_reservas > 0) : ?>
                    <div class="flavor-carpooling-proximo__detalle flavor-carpooling-proximo__detalle--alerta">
                        <span class="dashicons dashicons-bell"></span>
                        <span>
                            <?php
                            printf(
                                esc_html(_n('%d reserva', '%d reservas', $proximo_viaje->total_reservas, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                                $proximo_viaje->total_reservas
                            );
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="flavor-carpooling-proximo__detalle">
                    <span class="dashicons dashicons-admin-users"></span>
                    <span>
                        <?php
                        printf(
                            esc_html(_n('%d plaza reservada', '%d plazas reservadas', $proximo_viaje->numero_plazas, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                            $proximo_viaje->numero_plazas
                        );
                        ?>
                    </span>
                </div>
                <div class="flavor-carpooling-proximo__detalle flavor-carpooling-proximo__detalle--estado">
                    <?php
                    $clases_estado = [
                        'confirmada' => 'estado--confirmado',
                        'pendiente'  => 'estado--pendiente',
                    ];
                    $clase_estado = $clases_estado[$proximo_viaje->estado_reserva] ?? '';
                    $textos_estado = [
                        'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'pendiente'  => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ];
                    ?>
                    <span class="flavor-carpooling-proximo__estado <?php echo esc_attr($clase_estado); ?>">
                        <?php echo esc_html($textos_estado[$proximo_viaje->estado_reserva] ?? $proximo_viaje->estado_reserva); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Precio -->
        <?php if (!$es_conductor && $proximo_viaje->precio_total) : ?>
            <div class="flavor-carpooling-proximo__precio">
                <span class="flavor-carpooling-proximo__precio-label"><?php esc_html_e('Total a pagar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <span class="flavor-carpooling-proximo__precio-valor"><?php echo esc_html(number_format($proximo_viaje->precio_total, 2)); ?>€</span>
            </div>
        <?php endif; ?>

        <!-- Acciones -->
        <div class="flavor-carpooling-proximo__acciones">
            <a href="<?php echo esc_url(home_url('/carpooling/viaje/' . $proximo_viaje->id)); ?>" class="cp-btn cp-btn-primary">
                <span class="dashicons dashicons-visibility"></span>
                <?php esc_html_e('Ver detalles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>

            <?php if ($es_conductor) : ?>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('carpooling', 'mis-viajes')); ?>" class="cp-btn cp-btn-outline">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php else : ?>
                <button type="button" class="cp-btn cp-btn-outline" data-reserva-id="<?php echo esc_attr($proximo_viaje->reserva_id); ?>" data-action="contactar-conductor">
                    <span class="dashicons dashicons-email"></span>
                    <?php esc_html_e('Contactar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>
        </div>

    <?php else : ?>
        <!-- Estado vacio -->
        <div class="flavor-carpooling-proximo__vacio">
            <span class="dashicons dashicons-car"></span>
            <h4><?php esc_html_e('Sin viajes proximos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <p><?php esc_html_e('No tienes viajes programados proximamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="flavor-carpooling-proximo__vacio-acciones">
                <a href="<?php echo esc_url(home_url('/carpooling/buscar/')); ?>" class="cp-btn cp-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Buscar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(home_url('/carpooling/publicar/')); ?>" class="cp-btn cp-btn-outline">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Publicar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* === Widget proximo viaje === */
.flavor-carpooling-proximo {
    background: var(--cp-bg-card);
    border-radius: var(--cp-radius);
    box-shadow: var(--cp-shadow);
    padding: var(--fl-space-5, 1.25rem);
    border-left: 4px solid var(--cp-primary);
}

.flavor-carpooling-proximo--pronto {
    border-left-color: var(--cp-warning);
}

.flavor-carpooling-proximo--muy-pronto {
    border-left-color: var(--fl-warning-500, #f59e0b);
    background: linear-gradient(135deg, var(--cp-bg-card) 0%, color-mix(in srgb, var(--fl-warning-500, #f59e0b) 5%, white) 100%);
}

.flavor-carpooling-proximo--inminente {
    border-left-color: var(--cp-danger);
    background: linear-gradient(135deg, var(--cp-bg-card) 0%, color-mix(in srgb, var(--cp-danger) 5%, white) 100%);
    animation: pulse-border 2s infinite;
}

@keyframes pulse-border {
    0%, 100% { border-left-width: 4px; }
    50% { border-left-width: 6px; }
}

/* Header */
.flavor-carpooling-proximo__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--fl-space-4, 1rem);
}

.flavor-carpooling-proximo__rol {
    display: inline-flex;
    align-items: center;
    gap: var(--fl-space-1, 0.25rem);
    padding: var(--fl-space-1, 0.25rem) var(--fl-space-3, 0.75rem);
    border-radius: var(--fl-radius-full, 9999px);
    font-size: var(--fl-font-size-sm, 0.875rem);
    font-weight: var(--fl-font-weight-semibold, 600);
}

.flavor-carpooling-proximo__rol.rol--conductor {
    background: var(--cp-primary-light);
    color: var(--cp-primary);
}

.flavor-carpooling-proximo__rol.rol--pasajero {
    background: color-mix(in srgb, var(--cp-secondary) 15%, white);
    color: var(--cp-secondary);
}

.flavor-carpooling-proximo__tiempo {
    display: flex;
    align-items: center;
    gap: var(--fl-space-1, 0.25rem);
    font-size: var(--fl-font-size-sm, 0.875rem);
    color: var(--cp-text-muted);
}

/* Ruta */
.flavor-carpooling-proximo__ruta {
    margin-bottom: var(--fl-space-4, 1rem);
}

.flavor-carpooling-proximo__punto {
    display: flex;
    align-items: flex-start;
    gap: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-proximo__punto-icono {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-carpooling-proximo__punto--origen .flavor-carpooling-proximo__punto-icono {
    background: var(--fl-success-100, #dcfce7);
    color: var(--fl-success-600, #16a34a);
}

.flavor-carpooling-proximo__punto--destino .flavor-carpooling-proximo__punto-icono {
    background: var(--fl-danger-100, #fee2e2);
    color: var(--fl-danger-600, #dc2626);
}

.flavor-carpooling-proximo__punto-label {
    display: block;
    font-size: var(--fl-font-size-xs, 0.75rem);
    color: var(--cp-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.flavor-carpooling-proximo__punto-valor {
    font-weight: var(--fl-font-weight-semibold, 600);
    color: var(--cp-text-primary);
}

.flavor-carpooling-proximo__linea {
    display: flex;
    justify-content: center;
    padding: var(--fl-space-2, 0.5rem) 0;
    margin-left: 16px;
    color: var(--cp-border);
}

/* Detalles */
.flavor-carpooling-proximo__detalles {
    display: flex;
    flex-wrap: wrap;
    gap: var(--fl-space-3, 0.75rem);
    padding: var(--fl-space-3, 0.75rem) 0;
    border-top: 1px solid var(--cp-border);
    border-bottom: 1px solid var(--cp-border);
    margin-bottom: var(--fl-space-4, 1rem);
}

.flavor-carpooling-proximo__detalle {
    display: flex;
    align-items: center;
    gap: var(--fl-space-1, 0.25rem);
    font-size: var(--fl-font-size-sm, 0.875rem);
    color: var(--cp-text-secondary);
}

.flavor-carpooling-proximo__detalle .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: var(--cp-text-muted);
}

.flavor-carpooling-proximo__detalle--alerta {
    color: var(--cp-warning);
}

.flavor-carpooling-proximo__detalle--alerta .dashicons {
    color: var(--cp-warning);
}

.flavor-carpooling-proximo__estado {
    padding: var(--fl-space-1, 0.25rem) var(--fl-space-2, 0.5rem);
    border-radius: var(--cp-radius-sm);
    font-size: var(--fl-font-size-xs, 0.75rem);
    font-weight: var(--fl-font-weight-semibold, 600);
}

.flavor-carpooling-proximo__estado.estado--confirmado {
    background: var(--fl-success-100, #dcfce7);
    color: var(--fl-success-700, #15803d);
}

.flavor-carpooling-proximo__estado.estado--pendiente {
    background: var(--fl-warning-100, #fef3c7);
    color: var(--fl-warning-700, #b45309);
}

/* Precio */
.flavor-carpooling-proximo__precio {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--fl-space-4, 1rem);
}

.flavor-carpooling-proximo__precio-label {
    font-size: var(--fl-font-size-sm, 0.875rem);
    color: var(--cp-text-muted);
}

.flavor-carpooling-proximo__precio-valor {
    font-size: var(--fl-font-size-xl, 1.25rem);
    font-weight: var(--fl-font-weight-bold, 700);
    color: var(--cp-primary);
}

/* Acciones */
.flavor-carpooling-proximo__acciones {
    display: flex;
    gap: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-proximo__acciones .cp-btn {
    flex: 1;
}

/* Estado vacio */
.flavor-carpooling-proximo__vacio {
    text-align: center;
    padding: var(--fl-space-4, 1rem) 0;
}

.flavor-carpooling-proximo__vacio .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--cp-text-muted);
    opacity: 0.5;
    margin-bottom: var(--fl-space-3, 0.75rem);
}

.flavor-carpooling-proximo__vacio h4 {
    margin: 0 0 var(--fl-space-2, 0.5rem);
    font-size: var(--fl-font-size-lg, 1.125rem);
    color: var(--cp-text-primary);
}

.flavor-carpooling-proximo__vacio p {
    margin: 0 0 var(--fl-space-4, 1rem);
    color: var(--cp-text-muted);
    font-size: var(--fl-font-size-sm, 0.875rem);
}

.flavor-carpooling-proximo__vacio-acciones {
    display: flex;
    gap: var(--fl-space-3, 0.75rem);
    justify-content: center;
}

/* Responsive */
@media (max-width: 480px) {
    .flavor-carpooling-proximo__header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--fl-space-2, 0.5rem);
    }

    .flavor-carpooling-proximo__acciones {
        flex-direction: column;
    }

    .flavor-carpooling-proximo__vacio-acciones {
        flex-direction: column;
    }
}
</style>
