<?php
/**
 * Template: Mis Reservas del Usuario
 *
 * Muestra las reservas del usuario logueado
 *
 * @package FlavorChatIA
 * @subpackage Reservas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar login
if (!is_user_logged_in()) {
    echo '<div class="flavor-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesión', 'flavor-chat-ia') . '</h3>';
    echo '<p>' . esc_html__('Debes iniciar sesión para ver tus reservas.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="flavor-btn flavor-btn-primary">';
    echo esc_html__('Iniciar Sesión', 'flavor-chat-ia');
    echo '</a>';
    echo '</div>';
    return;
}

$usuario_actual_id = get_current_user_id();

global $wpdb;
$tabla_reservas = $wpdb->prefix . 'flavor_reservas';

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_fecha = isset($_GET['fecha']) ? sanitize_text_field($_GET['fecha']) : '';
$pagina_actual = max(1, isset($_GET['paged']) ? absint($_GET['paged']) : 1);
$items_por_pagina = 10;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Construir query
$where_condiciones = ["user_id = %d"];
$params_query = [$usuario_actual_id];

if (!empty($filtro_estado)) {
    $where_condiciones[] = "estado = %s";
    $params_query[] = $filtro_estado;
}

if (!empty($filtro_fecha)) {
    $where_condiciones[] = "DATE(fecha_reserva) = %s";
    $params_query[] = $filtro_fecha;
}

$clausula_where = implode(' AND ', $where_condiciones);

// Contar total
$sql_count = "SELECT COUNT(*) FROM $tabla_reservas WHERE $clausula_where";
$total_reservas = $wpdb->get_var($wpdb->prepare($sql_count, ...$params_query));
$total_paginas = ceil($total_reservas / $items_por_pagina);

// Obtener reservas
$sql_reservas = "SELECT * FROM $tabla_reservas WHERE $clausula_where ORDER BY fecha_reserva DESC, hora_inicio DESC LIMIT %d OFFSET %d";
$params_query[] = $items_por_pagina;
$params_query[] = $offset;

$reservas = $wpdb->get_results($wpdb->prepare($sql_reservas, ...$params_query));

// Estadísticas rápidas
$sql_stats = "SELECT estado, COUNT(*) as total FROM $tabla_reservas WHERE user_id = %d GROUP BY estado";
$estadisticas = $wpdb->get_results($wpdb->prepare($sql_stats, $usuario_actual_id), OBJECT_K);

$estados_config = [
    'pendiente' => ['label' => __('Pendiente', 'flavor-chat-ia'), 'color' => 'warning', 'icono' => 'clock'],
    'confirmada' => ['label' => __('Confirmada', 'flavor-chat-ia'), 'color' => 'success', 'icono' => 'yes-alt'],
    'cancelada' => ['label' => __('Cancelada', 'flavor-chat-ia'), 'color' => 'danger', 'icono' => 'dismiss'],
    'completada' => ['label' => __('Completada', 'flavor-chat-ia'), 'color' => 'info', 'icono' => 'saved'],
];

// Enqueue assets
wp_enqueue_style('flavor-reservas');
wp_enqueue_script('flavor-reservas');
?>

<div class="flavor-mis-reservas-page">
    <!-- Header -->
    <header class="mis-reservas-header">
        <div class="header-content">
            <h1><?php esc_html_e('Mis Reservas', 'flavor-chat-ia'); ?></h1>
            <a href="<?php echo esc_url(home_url('/reservas/')); ?>" class="flavor-btn flavor-btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nueva Reserva', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </header>

    <!-- Estadísticas -->
    <div class="mis-reservas-stats">
        <?php foreach ($estados_config as $estado_key => $estado_info) :
            $cantidad = isset($estadisticas[$estado_key]) ? $estadisticas[$estado_key]->total : 0;
        ?>
            <a href="<?php echo esc_url(add_query_arg('estado', $estado_key)); ?>"
               class="stat-card stat-<?php echo esc_attr($estado_info['color']); ?> <?php echo $filtro_estado === $estado_key ? 'activo' : ''; ?>">
                <span class="dashicons dashicons-<?php echo esc_attr($estado_info['icono']); ?>"></span>
                <span class="stat-valor"><?php echo esc_html($cantidad); ?></span>
                <span class="stat-label"><?php echo esc_html($estado_info['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtros -->
    <div class="mis-reservas-filtros">
        <form method="get" class="filtros-form">
            <div class="filtros-row">
                <div class="filtro-item">
                    <label><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                    <select name="estado" onchange="this.form.submit()">
                        <option value=""><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($estados_config as $estado_key => $estado_info) : ?>
                            <option value="<?php echo esc_attr($estado_key); ?>" <?php selected($filtro_estado, $estado_key); ?>>
                                <?php echo esc_html($estado_info['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filtro-item">
                    <label><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></label>
                    <input type="date" name="fecha"
                           value="<?php echo esc_attr($filtro_fecha); ?>"
                           onchange="this.form.submit()">
                </div>

                <?php if (!empty($filtro_estado) || !empty($filtro_fecha)) : ?>
                    <a href="<?php echo esc_url(remove_query_arg(['estado', 'fecha', 'paged'])); ?>" class="filtro-limpiar">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Lista de reservas -->
    <?php if (!empty($reservas)) : ?>
        <div class="reservas-lista">
            <?php foreach ($reservas as $reserva) :
                $estado_info = $estados_config[$reserva->estado] ?? $estados_config['pendiente'];
                $fecha_reserva = strtotime($reserva->fecha_reserva);
                $es_pasada = $fecha_reserva < strtotime('today');
                $es_hoy = date('Y-m-d', $fecha_reserva) === date('Y-m-d');
                $puede_cancelar = in_array($reserva->estado, ['pendiente', 'confirmada']) && !$es_pasada;
            ?>
                <article class="reserva-item estado-<?php echo esc_attr($reserva->estado); ?> <?php echo $es_pasada ? 'pasada' : ''; ?> <?php echo $es_hoy ? 'hoy' : ''; ?>">
                    <div class="reserva-fecha-col">
                        <div class="fecha-box">
                            <span class="fecha-dia"><?php echo esc_html(date_i18n('d', $fecha_reserva)); ?></span>
                            <span class="fecha-mes"><?php echo esc_html(date_i18n('M', $fecha_reserva)); ?></span>
                            <span class="fecha-anio"><?php echo esc_html(date_i18n('Y', $fecha_reserva)); ?></span>
                        </div>
                        <?php if ($es_hoy) : ?>
                            <span class="badge-hoy"><?php esc_html_e('HOY', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="reserva-info-col">
                        <div class="reserva-tipo">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $reserva->tipo_servicio))); ?>
                        </div>

                        <div class="reserva-horario">
                            <span class="dashicons dashicons-clock"></span>
                            <span class="hora-inicio"><?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?></span>
                            <span class="separador">-</span>
                            <span class="hora-fin"><?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?></span>
                        </div>

                        <div class="reserva-personas">
                            <span class="dashicons dashicons-groups"></span>
                            <?php printf(esc_html(_n('%d persona', '%d personas', $reserva->num_personas, 'flavor-chat-ia')), $reserva->num_personas); ?>
                        </div>

                        <?php if (!empty($reserva->notas)) : ?>
                            <div class="reserva-notas">
                                <span class="dashicons dashicons-format-aside"></span>
                                <?php echo esc_html(wp_trim_words($reserva->notas, 10)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="reserva-estado-col">
                        <span class="estado-badge estado-<?php echo esc_attr($estado_info['color']); ?>">
                            <span class="dashicons dashicons-<?php echo esc_attr($estado_info['icono']); ?>"></span>
                            <?php echo esc_html($estado_info['label']); ?>
                        </span>

                        <span class="reserva-id">#<?php echo esc_html($reserva->id); ?></span>
                    </div>

                    <div class="reserva-acciones-col">
                        <?php if ($puede_cancelar) : ?>
                            <button type="button"
                                    class="flavor-btn flavor-btn-danger flavor-btn-sm btn-cancelar-reserva"
                                    data-id="<?php echo esc_attr($reserva->id); ?>"
                                    data-nonce="<?php echo wp_create_nonce('reservas_cancelar_' . $reserva->id); ?>">
                                <span class="dashicons dashicons-dismiss"></span>
                                <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>

                        <a href="<?php echo esc_url(home_url('/reservas/detalle/' . $reserva->id . '/')); ?>"
                           class="flavor-btn flavor-btn-outline flavor-btn-sm">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1) : ?>
            <nav class="reservas-paginacion">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $pagina_actual,
                    'total' => $total_paginas,
                    'prev_text' => '&laquo; ' . __('Anterior', 'flavor-chat-ia'),
                    'next_text' => __('Siguiente', 'flavor-chat-ia') . ' &raquo;',
                    'type' => 'list',
                ]);
                ?>
            </nav>
        <?php endif; ?>

    <?php else : ?>
        <div class="reservas-vacio">
            <span class="dashicons dashicons-calendar-alt"></span>
            <h3><?php esc_html_e('No tienes reservas', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtro_estado) || !empty($filtro_fecha)) : ?>
                <p><?php esc_html_e('No se encontraron reservas con los filtros aplicados.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(remove_query_arg(['estado', 'fecha', 'paged'])); ?>" class="flavor-btn flavor-btn-outline">
                    <?php esc_html_e('Ver todas', 'flavor-chat-ia'); ?>
                </a>
            <?php else : ?>
                <p><?php esc_html_e('Aún no has realizado ninguna reserva.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(home_url('/reservas/')); ?>" class="flavor-btn flavor-btn-primary">
                    <?php esc_html_e('Hacer una reserva', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmación de cancelación -->
<div id="modal-cancelar-reserva" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <h3><?php esc_html_e('Cancelar Reserva', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('¿Estás seguro de que deseas cancelar esta reserva? Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?></p>
        <div class="modal-acciones">
            <button type="button" class="flavor-btn flavor-btn-outline btn-modal-cerrar">
                <?php esc_html_e('No, mantener', 'flavor-chat-ia'); ?>
            </button>
            <button type="button" class="flavor-btn flavor-btn-danger btn-modal-confirmar">
                <?php esc_html_e('Sí, cancelar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.flavor-mis-reservas-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: var(--flavor-spacing-lg, 2rem);
}

.mis-reservas-header {
    margin-bottom: var(--flavor-spacing-xl, 3rem);
}

.mis-reservas-header .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--flavor-spacing-md, 1rem);
}

.mis-reservas-header h1 {
    font-size: var(--flavor-font-size-2xl, 1.5rem);
    font-weight: var(--flavor-font-weight-bold, 700);
    margin: 0;
}

.mis-reservas-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: var(--flavor-spacing-md, 1rem);
    margin-bottom: var(--flavor-spacing-xl, 3rem);
}

.stat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--flavor-spacing-md, 1rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-lg, 12px);
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    border: 2px solid transparent;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--flavor-shadow-md, 0 4px 6px rgba(0,0,0,0.1));
}

.stat-card.activo {
    border-color: currentColor;
}

.stat-card .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    margin-bottom: var(--flavor-spacing-xs, 0.25rem);
}

.stat-valor {
    font-size: var(--flavor-font-size-2xl, 1.5rem);
    font-weight: var(--flavor-font-weight-bold, 700);
}

.stat-label {
    font-size: var(--flavor-font-size-xs, 0.75rem);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-warning { color: var(--flavor-color-warning, #f59e0b); }
.stat-success { color: var(--flavor-color-success, #10b981); }
.stat-danger { color: var(--flavor-color-danger, #ef4444); }
.stat-info { color: var(--flavor-color-info, #3b82f6); }

.mis-reservas-filtros {
    background: var(--flavor-bg-secondary, #f8f9fa);
    padding: var(--flavor-spacing-md, 1rem);
    border-radius: var(--flavor-radius-lg, 12px);
    margin-bottom: var(--flavor-spacing-lg, 2rem);
}

.filtros-row {
    display: flex;
    flex-wrap: wrap;
    gap: var(--flavor-spacing-md, 1rem);
    align-items: flex-end;
}

.filtro-item {
    display: flex;
    flex-direction: column;
    gap: var(--flavor-spacing-xs, 0.25rem);
}

.filtro-item label {
    font-size: var(--flavor-font-size-sm, 0.875rem);
    font-weight: var(--flavor-font-weight-medium, 500);
    color: var(--flavor-text-secondary, #666);
}

.filtro-item select,
.filtro-item input {
    padding: var(--flavor-spacing-sm, 0.5rem);
    border: 1px solid var(--flavor-border-color, #ddd);
    border-radius: var(--flavor-radius-md, 8px);
    font-size: var(--flavor-font-size-base, 1rem);
}

.filtro-limpiar {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--flavor-color-danger, #ef4444);
    text-decoration: none;
    font-size: var(--flavor-font-size-sm, 0.875rem);
}

.reservas-lista {
    display: flex;
    flex-direction: column;
    gap: var(--flavor-spacing-md, 1rem);
}

.reserva-item {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    gap: var(--flavor-spacing-md, 1rem);
    align-items: center;
    padding: var(--flavor-spacing-md, 1rem);
    background: var(--flavor-bg-primary, #fff);
    border-radius: var(--flavor-radius-lg, 12px);
    box-shadow: var(--flavor-shadow-sm, 0 1px 3px rgba(0,0,0,0.1));
    transition: box-shadow 0.2s;
}

.reserva-item:hover {
    box-shadow: var(--flavor-shadow-md, 0 4px 6px rgba(0,0,0,0.1));
}

.reserva-item.pasada {
    opacity: 0.7;
}

.reserva-item.hoy {
    border-left: 4px solid var(--flavor-color-primary, #3b82f6);
}

.reserva-fecha-col {
    text-align: center;
    min-width: 70px;
}

.fecha-box {
    background: var(--flavor-bg-secondary, #f8f9fa);
    padding: var(--flavor-spacing-sm, 0.5rem);
    border-radius: var(--flavor-radius-md, 8px);
}

.fecha-dia {
    display: block;
    font-size: var(--flavor-font-size-2xl, 1.5rem);
    font-weight: var(--flavor-font-weight-bold, 700);
    line-height: 1;
}

.fecha-mes {
    display: block;
    font-size: var(--flavor-font-size-sm, 0.875rem);
    text-transform: uppercase;
    color: var(--flavor-text-secondary, #666);
}

.fecha-anio {
    display: block;
    font-size: var(--flavor-font-size-xs, 0.75rem);
    color: var(--flavor-text-muted, #9ca3af);
}

.badge-hoy {
    display: inline-block;
    margin-top: var(--flavor-spacing-xs, 0.25rem);
    padding: 2px 8px;
    background: var(--flavor-color-primary, #3b82f6);
    color: #fff;
    font-size: var(--flavor-font-size-xs, 0.75rem);
    font-weight: var(--flavor-font-weight-bold, 700);
    border-radius: var(--flavor-radius-full, 9999px);
}

.reserva-info-col {
    display: flex;
    flex-direction: column;
    gap: var(--flavor-spacing-xs, 0.25rem);
}

.reserva-tipo,
.reserva-horario,
.reserva-personas,
.reserva-notas {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: var(--flavor-font-size-sm, 0.875rem);
}

.reserva-tipo {
    font-weight: var(--flavor-font-weight-semibold, 600);
    color: var(--flavor-text-primary, #1a1a1a);
}

.reserva-horario,
.reserva-personas {
    color: var(--flavor-text-secondary, #666);
}

.reserva-notas {
    color: var(--flavor-text-muted, #9ca3af);
    font-style: italic;
}

.reserva-info-col .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: var(--flavor-text-muted, #9ca3af);
}

.reserva-estado-col {
    text-align: center;
}

.estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 12px;
    border-radius: var(--flavor-radius-full, 9999px);
    font-size: var(--flavor-font-size-sm, 0.875rem);
    font-weight: var(--flavor-font-weight-medium, 500);
}

.estado-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.estado-warning {
    background: #fef3c7;
    color: #92400e;
}

.estado-success {
    background: #d1fae5;
    color: #065f46;
}

.estado-danger {
    background: #fee2e2;
    color: #991b1b;
}

.estado-info {
    background: #dbeafe;
    color: #1e40af;
}

.reserva-id {
    display: block;
    margin-top: var(--flavor-spacing-xs, 0.25rem);
    font-size: var(--flavor-font-size-xs, 0.75rem);
    color: var(--flavor-text-muted, #9ca3af);
}

.reserva-acciones-col {
    display: flex;
    gap: var(--flavor-spacing-sm, 0.5rem);
}

.reservas-vacio {
    text-align: center;
    padding: var(--flavor-spacing-3xl, 4rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-lg, 12px);
}

.reservas-vacio .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--flavor-text-muted, #9ca3af);
}

.reservas-vacio h3 {
    margin: var(--flavor-spacing-md, 1rem) 0 var(--flavor-spacing-sm, 0.5rem);
    color: var(--flavor-text-primary, #1a1a1a);
}

.reservas-vacio p {
    color: var(--flavor-text-secondary, #666);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

/* Modal */
.flavor-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.5);
}

.flavor-modal-content {
    position: relative;
    background: var(--flavor-bg-primary, #fff);
    padding: var(--flavor-spacing-xl, 2rem);
    border-radius: var(--flavor-radius-lg, 12px);
    max-width: 400px;
    width: 90%;
    text-align: center;
}

.flavor-modal-content h3 {
    margin: 0 0 var(--flavor-spacing-md, 1rem);
}

.modal-acciones {
    display: flex;
    gap: var(--flavor-spacing-md, 1rem);
    justify-content: center;
    margin-top: var(--flavor-spacing-lg, 1.5rem);
}

.reservas-paginacion {
    margin-top: var(--flavor-spacing-xl, 3rem);
    display: flex;
    justify-content: center;
}

.reservas-paginacion ul {
    display: flex;
    gap: var(--flavor-spacing-xs, 0.25rem);
    list-style: none;
    padding: 0;
    margin: 0;
}

.reservas-paginacion .page-numbers {
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    border-radius: var(--flavor-radius-md, 8px);
    background: var(--flavor-bg-secondary, #f8f9fa);
    color: var(--flavor-text-primary, #1a1a1a);
    text-decoration: none;
}

.reservas-paginacion .page-numbers:hover,
.reservas-paginacion .page-numbers.current {
    background: var(--flavor-color-primary, #3b82f6);
    color: #fff;
}

/* Login Required */
.flavor-login-required {
    text-align: center;
    padding: var(--flavor-spacing-3xl, 4rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-lg, 12px);
    max-width: 400px;
    margin: var(--flavor-spacing-xl, 3rem) auto;
}

.flavor-login-required .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: var(--flavor-text-muted, #9ca3af);
}

.flavor-login-required h3 {
    margin: var(--flavor-spacing-md, 1rem) 0 var(--flavor-spacing-sm, 0.5rem);
}

.flavor-login-required p {
    color: var(--flavor-text-secondary, #666);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

@media (max-width: 768px) {
    .flavor-mis-reservas-page {
        padding: var(--flavor-spacing-md, 1rem);
    }

    .mis-reservas-header .header-content {
        flex-direction: column;
        align-items: stretch;
        text-align: center;
    }

    .reserva-item {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .reserva-fecha-col {
        order: -1;
    }

    .reserva-info-col {
        align-items: center;
    }

    .reserva-acciones-col {
        justify-content: center;
        flex-wrap: wrap;
    }

    .filtros-row {
        flex-direction: column;
    }

    .filtro-item {
        width: 100%;
    }

    .filtro-item select,
    .filtro-item input {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modal-cancelar-reserva');
    const btnsCancelar = document.querySelectorAll('.btn-cancelar-reserva');
    let reservaIdActual = null;
    let nonceActual = null;

    btnsCancelar.forEach(function(btn) {
        btn.addEventListener('click', function() {
            reservaIdActual = this.dataset.id;
            nonceActual = this.dataset.nonce;
            modal.style.display = 'flex';
        });
    });

    modal.querySelector('.flavor-modal-overlay').addEventListener('click', cerrarModal);
    modal.querySelector('.btn-modal-cerrar').addEventListener('click', cerrarModal);

    modal.querySelector('.btn-modal-confirmar').addEventListener('click', function() {
        if (!reservaIdActual) return;

        const btn = this;
        btn.disabled = true;
        btn.textContent = '<?php echo esc_js(__('Cancelando...', 'flavor-chat-ia')); ?>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'reservas_cancelar',
                reserva_id: reservaIdActual,
                nonce: '<?php echo wp_create_nonce('reservas_nonce'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.data || '<?php echo esc_js(__('Error al cancelar', 'flavor-chat-ia')); ?>');
                btn.disabled = false;
                btn.textContent = '<?php echo esc_js(__('Sí, cancelar', 'flavor-chat-ia')); ?>';
            }
        })
        .catch(function() {
            alert('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>');
            btn.disabled = false;
            btn.textContent = '<?php echo esc_js(__('Sí, cancelar', 'flavor-chat-ia')); ?>';
        });
    });

    function cerrarModal() {
        modal.style.display = 'none';
        reservaIdActual = null;
        nonceActual = null;
    }
});
</script>
