<?php
/**
 * Template: Detalle de Recurso Reservable
 *
 * Muestra los detalles de un recurso y permite reservarlo
 *
 * @package FlavorChatIA
 * @subpackage Reservas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener ID del recurso
$recurso_id = isset($_GET['recurso_id']) ? absint($_GET['recurso_id']) : 0;

if (!$recurso_id) {
    // Intentar obtener de la URL
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    preg_match('/reservas\/(\d+)/', $url_path, $matches);
    $recurso_id = isset($matches[1]) ? absint($matches[1]) : 0;
}

if (!$recurso_id) {
    echo '<div class="flavor-notice flavor-notice-error">';
    echo '<p>' . esc_html__('Recurso no especificado.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(home_url('/reservas/')) . '" class="flavor-btn flavor-btn-outline">';
    echo esc_html__('Ver todos los recursos', 'flavor-chat-ia');
    echo '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_recursos = $wpdb->prefix . 'flavor_reservas_recursos';
$tabla_reservas = $wpdb->prefix . 'flavor_reservas';

// Obtener recurso
$recurso = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_recursos WHERE id = %d",
    $recurso_id
));

if (!$recurso) {
    echo '<div class="flavor-notice flavor-notice-error">';
    echo '<p>' . esc_html__('Recurso no encontrado.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(home_url('/reservas/')) . '" class="flavor-btn flavor-btn-outline">';
    echo esc_html__('Ver todos los recursos', 'flavor-chat-ia');
    echo '</a>';
    echo '</div>';
    return;
}

// Obtener reservas próximas para mostrar disponibilidad
$fecha_hoy = date('Y-m-d');
$fecha_limite = date('Y-m-d', strtotime('+7 days'));

$reservas_proximas = $wpdb->get_results($wpdb->prepare(
    "SELECT fecha_reserva, hora_inicio, hora_fin, estado
     FROM $tabla_reservas
     WHERE recurso_id = %d
       AND fecha_reserva BETWEEN %s AND %s
       AND estado IN ('pendiente', 'confirmada')
     ORDER BY fecha_reserva ASC, hora_inicio ASC",
    $recurso_id,
    $fecha_hoy,
    $fecha_limite
));

// Organizar reservas por día
$reservas_por_dia = [];
foreach ($reservas_proximas as $reserva) {
    $reservas_por_dia[$reserva->fecha_reserva][] = $reserva;
}

// Enqueue assets
wp_enqueue_style('flavor-reservas');
wp_enqueue_script('flavor-reservas');
?>

<div class="flavor-recurso-single">
    <!-- Breadcrumb -->
    <nav class="recurso-breadcrumb">
        <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Inicio', 'flavor-chat-ia'); ?></a>
        <span class="separator">/</span>
        <a href="<?php echo esc_url(home_url('/reservas/')); ?>"><?php esc_html_e('Reservas', 'flavor-chat-ia'); ?></a>
        <span class="separator">/</span>
        <span class="current"><?php echo esc_html($recurso->nombre); ?></span>
    </nav>

    <div class="recurso-layout">
        <!-- Columna principal -->
        <main class="recurso-main">
            <!-- Imagen -->
            <div class="recurso-imagen-wrapper">
                <?php if (!empty($recurso->imagen)) : ?>
                    <img src="<?php echo esc_url($recurso->imagen); ?>"
                         alt="<?php echo esc_attr($recurso->nombre); ?>"
                         class="recurso-imagen-principal">
                <?php else : ?>
                    <div class="recurso-imagen-placeholder">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                <?php endif; ?>
                <span class="recurso-tipo-badge"><?php echo esc_html(ucfirst($recurso->tipo)); ?></span>
            </div>

            <!-- Información -->
            <header class="recurso-header">
                <h1 class="recurso-titulo"><?php echo esc_html($recurso->nombre); ?></h1>

                <?php if (!empty($recurso->ubicacion)) : ?>
                    <p class="recurso-ubicacion">
                        <span class="dashicons dashicons-location"></span>
                        <?php echo esc_html($recurso->ubicacion); ?>
                    </p>
                <?php endif; ?>

                <div class="recurso-meta-grid">
                    <?php if (!empty($recurso->capacidad)) : ?>
                        <div class="meta-item">
                            <span class="dashicons dashicons-groups"></span>
                            <span class="meta-label"><?php esc_html_e('Capacidad', 'flavor-chat-ia'); ?></span>
                            <span class="meta-valor"><?php printf(esc_html__('%d personas', 'flavor-chat-ia'), $recurso->capacidad); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="meta-item">
                        <span class="dashicons dashicons-tag"></span>
                        <span class="meta-label"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></span>
                        <span class="meta-valor"><?php echo esc_html(ucfirst($recurso->tipo)); ?></span>
                    </div>

                    <div class="meta-item estado-<?php echo $recurso->estado === 'activo' ? 'disponible' : 'no-disponible'; ?>">
                        <span class="dashicons dashicons-<?php echo $recurso->estado === 'activo' ? 'yes' : 'no'; ?>"></span>
                        <span class="meta-label"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></span>
                        <span class="meta-valor">
                            <?php echo $recurso->estado === 'activo'
                                ? esc_html__('Disponible', 'flavor-chat-ia')
                                : esc_html__('No disponible', 'flavor-chat-ia'); ?>
                        </span>
                    </div>
                </div>
            </header>

            <!-- Descripción -->
            <?php if (!empty($recurso->descripcion)) : ?>
                <section class="recurso-descripcion">
                    <h2><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></h2>
                    <div class="descripcion-contenido">
                        <?php echo wp_kses_post(wpautop($recurso->descripcion)); ?>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Disponibilidad próxima -->
            <section class="recurso-disponibilidad">
                <h2><?php esc_html_e('Disponibilidad próxima', 'flavor-chat-ia'); ?></h2>

                <div class="calendario-semana">
                    <?php
                    for ($i = 0; $i < 7; $i++) :
                        $fecha = date('Y-m-d', strtotime("+$i days"));
                        $nombre_dia = date_i18n('D', strtotime($fecha));
                        $numero_dia = date('d', strtotime($fecha));
                        $reservas_dia = $reservas_por_dia[$fecha] ?? [];
                        $tiene_reservas = !empty($reservas_dia);
                        $es_hoy = $i === 0;
                    ?>
                        <div class="dia-item <?php echo $tiene_reservas ? 'tiene-reservas' : 'libre'; ?> <?php echo $es_hoy ? 'hoy' : ''; ?>">
                            <span class="dia-nombre"><?php echo esc_html($nombre_dia); ?></span>
                            <span class="dia-numero"><?php echo esc_html($numero_dia); ?></span>
                            <?php if ($tiene_reservas) : ?>
                                <span class="dia-estado">
                                    <?php printf(esc_html(_n('%d reserva', '%d reservas', count($reservas_dia), 'flavor-chat-ia')), count($reservas_dia)); ?>
                                </span>
                            <?php else : ?>
                                <span class="dia-estado libre"><?php esc_html_e('Libre', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <?php if (!empty($reservas_proximas)) : ?>
                    <details class="reservas-detalle">
                        <summary><?php esc_html_e('Ver horarios ocupados', 'flavor-chat-ia'); ?></summary>
                        <div class="reservas-lista-detalle">
                            <?php foreach ($reservas_por_dia as $fecha => $reservas) : ?>
                                <div class="dia-reservas">
                                    <strong><?php echo esc_html(date_i18n('l, j F', strtotime($fecha))); ?></strong>
                                    <ul>
                                        <?php foreach ($reservas as $reserva) : ?>
                                            <li>
                                                <?php echo esc_html(substr($reserva->hora_inicio, 0, 5)); ?> -
                                                <?php echo esc_html(substr($reserva->hora_fin, 0, 5)); ?>
                                                <span class="estado-mini"><?php echo esc_html(ucfirst($reserva->estado)); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>
            </section>
        </main>

        <!-- Sidebar -->
        <aside class="recurso-sidebar">
            <div class="sidebar-card card-reservar">
                <h3><?php esc_html_e('Reservar este recurso', 'flavor-chat-ia'); ?></h3>

                <?php if ($recurso->estado !== 'activo') : ?>
                    <div class="recurso-no-disponible">
                        <span class="dashicons dashicons-warning"></span>
                        <p><?php esc_html_e('Este recurso no está disponible para reservas en este momento.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php elseif (!is_user_logged_in()) : ?>
                    <div class="login-requerido">
                        <p><?php esc_html_e('Inicia sesión para realizar una reserva.', 'flavor-chat-ia'); ?></p>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-btn flavor-btn-primary flavor-btn-block">
                            <?php esc_html_e('Iniciar Sesión', 'flavor-chat-ia'); ?>
                        </a>
                        <p class="registro-link">
                            <?php esc_html_e('¿No tienes cuenta?', 'flavor-chat-ia'); ?>
                            <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php esc_html_e('Regístrate', 'flavor-chat-ia'); ?></a>
                        </p>
                    </div>
                <?php else : ?>
                    <form id="form-reserva-rapida" class="form-reserva">
                        <?php wp_nonce_field('reservas_nonce', 'reservas_nonce_field'); ?>
                        <input type="hidden" name="recurso_id" value="<?php echo esc_attr($recurso_id); ?>">

                        <div class="form-group">
                            <label><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></label>
                            <input type="date" name="fecha_inicio" required
                                   min="<?php echo esc_attr(date('Y-m-d')); ?>"
                                   max="<?php echo esc_attr(date('Y-m-d', strtotime('+30 days'))); ?>">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label><?php esc_html_e('Hora inicio', 'flavor-chat-ia'); ?></label>
                                <input type="time" name="hora_inicio" required value="09:00">
                            </div>
                            <div class="form-group">
                                <label><?php esc_html_e('Hora fin', 'flavor-chat-ia'); ?></label>
                                <input type="time" name="hora_fin" required value="10:00">
                            </div>
                        </div>

                        <div id="verificacion-resultado" class="verificacion-box" style="display: none;">
                            <span class="icono"></span>
                            <span class="mensaje"></span>
                        </div>

                        <div class="form-actions">
                            <button type="button" id="btn-verificar" class="flavor-btn flavor-btn-outline flavor-btn-block">
                                <?php esc_html_e('Verificar disponibilidad', 'flavor-chat-ia'); ?>
                            </button>
                            <button type="submit" id="btn-reservar" class="flavor-btn flavor-btn-primary flavor-btn-block" disabled>
                                <?php esc_html_e('Confirmar Reserva', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Compartir -->
            <div class="sidebar-card card-compartir">
                <h4><?php esc_html_e('Compartir', 'flavor-chat-ia'); ?></h4>
                <div class="compartir-botones">
                    <a href="https://wa.me/?text=<?php echo urlencode($recurso->nombre . ' - ' . get_permalink()); ?>"
                       target="_blank" class="btn-compartir whatsapp" title="WhatsApp">
                        <span class="dashicons dashicons-whatsapp"></span>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>"
                       target="_blank" class="btn-compartir facebook" title="Facebook">
                        <span class="dashicons dashicons-facebook-alt"></span>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($recurso->nombre); ?>&url=<?php echo urlencode(get_permalink()); ?>"
                       target="_blank" class="btn-compartir twitter" title="Twitter">
                        <span class="dashicons dashicons-twitter"></span>
                    </a>
                    <button type="button" class="btn-compartir copiar" title="<?php esc_attr_e('Copiar enlace', 'flavor-chat-ia'); ?>"
                            data-url="<?php echo esc_url(get_permalink()); ?>">
                        <span class="dashicons dashicons-admin-links"></span>
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <!-- Volver -->
    <nav class="recurso-navegacion">
        <a href="<?php echo esc_url(home_url('/reservas/')); ?>" class="flavor-btn flavor-btn-outline">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Ver todos los recursos', 'flavor-chat-ia'); ?>
        </a>
    </nav>
</div>

<style>
.flavor-recurso-single {
    max-width: 1200px;
    margin: 0 auto;
    padding: var(--flavor-spacing-lg, 2rem);
}

.recurso-breadcrumb {
    margin-bottom: var(--flavor-spacing-lg, 2rem);
    font-size: var(--flavor-font-size-sm, 0.875rem);
    color: var(--flavor-text-secondary, #666);
}

.recurso-breadcrumb a {
    color: var(--flavor-text-secondary, #666);
    text-decoration: none;
}

.recurso-breadcrumb a:hover {
    color: var(--flavor-color-primary, #3b82f6);
}

.recurso-breadcrumb .separator {
    margin: 0 var(--flavor-spacing-sm, 0.5rem);
}

.recurso-breadcrumb .current {
    color: var(--flavor-text-primary, #1a1a1a);
}

.recurso-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: var(--flavor-spacing-xl, 3rem);
}

.recurso-main {
    display: flex;
    flex-direction: column;
    gap: var(--flavor-spacing-xl, 2rem);
}

.recurso-imagen-wrapper {
    position: relative;
    border-radius: var(--flavor-radius-lg, 12px);
    overflow: hidden;
}

.recurso-imagen-principal {
    width: 100%;
    height: 400px;
    object-fit: cover;
}

.recurso-imagen-placeholder {
    width: 100%;
    height: 400px;
    background: var(--flavor-bg-tertiary, #e5e7eb);
    display: flex;
    align-items: center;
    justify-content: center;
}

.recurso-imagen-placeholder .dashicons {
    font-size: 80px;
    width: 80px;
    height: 80px;
    color: var(--flavor-text-muted, #9ca3af);
}

.recurso-tipo-badge {
    position: absolute;
    top: var(--flavor-spacing-md, 1rem);
    left: var(--flavor-spacing-md, 1rem);
    background: var(--flavor-color-primary, #3b82f6);
    color: #fff;
    padding: 6px 16px;
    border-radius: var(--flavor-radius-full, 9999px);
    font-size: var(--flavor-font-size-sm, 0.875rem);
    font-weight: var(--flavor-font-weight-medium, 500);
    text-transform: uppercase;
}

.recurso-header .recurso-titulo {
    font-size: var(--flavor-font-size-3xl, 2rem);
    font-weight: var(--flavor-font-weight-bold, 700);
    margin: 0 0 var(--flavor-spacing-sm, 0.5rem);
}

.recurso-ubicacion {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--flavor-text-secondary, #666);
    font-size: var(--flavor-font-size-lg, 1.125rem);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.recurso-meta-grid {
    display: flex;
    flex-wrap: wrap;
    gap: var(--flavor-spacing-md, 1rem);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-md, 8px);
}

.meta-item .dashicons {
    color: var(--flavor-color-primary, #3b82f6);
}

.meta-label {
    color: var(--flavor-text-secondary, #666);
    font-size: var(--flavor-font-size-sm, 0.875rem);
}

.meta-valor {
    font-weight: var(--flavor-font-weight-semibold, 600);
}

.meta-item.estado-disponible {
    background: #d1fae5;
}
.meta-item.estado-disponible .dashicons { color: #10b981; }

.meta-item.estado-no-disponible {
    background: #fee2e2;
}
.meta-item.estado-no-disponible .dashicons { color: #ef4444; }

.recurso-descripcion h2,
.recurso-disponibilidad h2 {
    font-size: var(--flavor-font-size-xl, 1.25rem);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.descripcion-contenido {
    color: var(--flavor-text-secondary, #666);
    line-height: 1.7;
}

.calendario-semana {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: var(--flavor-spacing-sm, 0.5rem);
}

.dia-item {
    text-align: center;
    padding: var(--flavor-spacing-md, 1rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-md, 8px);
    transition: transform 0.2s;
}

.dia-item:hover {
    transform: translateY(-2px);
}

.dia-item.hoy {
    border: 2px solid var(--flavor-color-primary, #3b82f6);
}

.dia-item.libre {
    background: #d1fae5;
}

.dia-item.tiene-reservas {
    background: #fef3c7;
}

.dia-nombre {
    display: block;
    font-size: var(--flavor-font-size-xs, 0.75rem);
    color: var(--flavor-text-secondary, #666);
    text-transform: uppercase;
}

.dia-numero {
    display: block;
    font-size: var(--flavor-font-size-xl, 1.25rem);
    font-weight: var(--flavor-font-weight-bold, 700);
}

.dia-estado {
    display: block;
    font-size: var(--flavor-font-size-xs, 0.75rem);
    margin-top: var(--flavor-spacing-xs, 0.25rem);
}

.dia-estado.libre {
    color: #10b981;
}

.reservas-detalle {
    margin-top: var(--flavor-spacing-md, 1rem);
}

.reservas-detalle summary {
    cursor: pointer;
    color: var(--flavor-color-primary, #3b82f6);
    font-weight: var(--flavor-font-weight-medium, 500);
}

.reservas-lista-detalle {
    margin-top: var(--flavor-spacing-md, 1rem);
    padding: var(--flavor-spacing-md, 1rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-md, 8px);
}

.dia-reservas {
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.dia-reservas:last-child {
    margin-bottom: 0;
}

.dia-reservas ul {
    margin: var(--flavor-spacing-sm, 0.5rem) 0 0;
    padding-left: var(--flavor-spacing-lg, 1.5rem);
}

.estado-mini {
    font-size: var(--flavor-font-size-xs, 0.75rem);
    padding: 2px 6px;
    background: var(--flavor-bg-tertiary, #e5e7eb);
    border-radius: var(--flavor-radius-sm, 4px);
    margin-left: var(--flavor-spacing-sm, 0.5rem);
}

/* Sidebar */
.recurso-sidebar {
    display: flex;
    flex-direction: column;
    gap: var(--flavor-spacing-lg, 1.5rem);
}

.sidebar-card {
    background: var(--flavor-bg-primary, #fff);
    border-radius: var(--flavor-radius-lg, 12px);
    padding: var(--flavor-spacing-lg, 1.5rem);
    box-shadow: var(--flavor-shadow-md, 0 4px 6px rgba(0,0,0,0.1));
}

.sidebar-card h3,
.sidebar-card h4 {
    margin: 0 0 var(--flavor-spacing-md, 1rem);
}

.card-reservar .form-group {
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.card-reservar .form-group label {
    display: block;
    font-size: var(--flavor-font-size-sm, 0.875rem);
    font-weight: var(--flavor-font-weight-medium, 500);
    margin-bottom: var(--flavor-spacing-xs, 0.25rem);
}

.card-reservar .form-group input {
    width: 100%;
    padding: var(--flavor-spacing-sm, 0.5rem);
    border: 1px solid var(--flavor-border-color, #ddd);
    border-radius: var(--flavor-radius-md, 8px);
}

.card-reservar .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--flavor-spacing-sm, 0.5rem);
}

.verificacion-box {
    padding: var(--flavor-spacing-md, 1rem);
    border-radius: var(--flavor-radius-md, 8px);
    margin-bottom: var(--flavor-spacing-md, 1rem);
    display: flex;
    align-items: center;
    gap: var(--flavor-spacing-sm, 0.5rem);
}

.verificacion-box.disponible {
    background: #d1fae5;
    color: #065f46;
}

.verificacion-box.no-disponible {
    background: #fee2e2;
    color: #991b1b;
}

.form-actions {
    display: flex;
    flex-direction: column;
    gap: var(--flavor-spacing-sm, 0.5rem);
}

.recurso-no-disponible,
.login-requerido {
    text-align: center;
    padding: var(--flavor-spacing-md, 1rem);
    background: var(--flavor-bg-secondary, #f8f9fa);
    border-radius: var(--flavor-radius-md, 8px);
}

.recurso-no-disponible .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: var(--flavor-color-warning, #f59e0b);
}

.registro-link {
    font-size: var(--flavor-font-size-sm, 0.875rem);
    margin-top: var(--flavor-spacing-md, 1rem);
}

.compartir-botones {
    display: flex;
    gap: var(--flavor-spacing-sm, 0.5rem);
}

.btn-compartir {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--flavor-radius-full, 9999px);
    border: none;
    cursor: pointer;
    transition: transform 0.2s;
}

.btn-compartir:hover {
    transform: scale(1.1);
}

.btn-compartir.whatsapp { background: #25D366; color: #fff; }
.btn-compartir.facebook { background: #1877F2; color: #fff; }
.btn-compartir.twitter { background: #1DA1F2; color: #fff; }
.btn-compartir.copiar { background: var(--flavor-bg-secondary, #f8f9fa); color: var(--flavor-text-primary, #1a1a1a); }

.recurso-navegacion {
    margin-top: var(--flavor-spacing-xl, 3rem);
    padding-top: var(--flavor-spacing-lg, 2rem);
    border-top: 1px solid var(--flavor-border-color, #e5e7eb);
}

@media (max-width: 900px) {
    .recurso-layout {
        grid-template-columns: 1fr;
    }

    .recurso-sidebar {
        order: -1;
    }

    .recurso-imagen-principal,
    .recurso-imagen-placeholder {
        height: 250px;
    }

    .calendario-semana {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (max-width: 480px) {
    .flavor-recurso-single {
        padding: var(--flavor-spacing-md, 1rem);
    }

    .calendario-semana {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-reserva-rapida');
    if (!form) return;

    const btnVerificar = document.getElementById('btn-verificar');
    const btnReservar = document.getElementById('btn-reservar');
    const resultado = document.getElementById('verificacion-resultado');

    btnVerificar.addEventListener('click', function() {
        const formData = new FormData(form);
        formData.append('action', 'reservas_disponibilidad');
        formData.append('nonce', '<?php echo wp_create_nonce('reservas_nonce'); ?>');

        btnVerificar.disabled = true;
        btnVerificar.textContent = '<?php echo esc_js(__('Verificando...', 'flavor-chat-ia')); ?>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            resultado.style.display = 'flex';

            if (data.success && data.data.disponible) {
                resultado.className = 'verificacion-box disponible';
                resultado.innerHTML = '<span class="dashicons dashicons-yes-alt"></span><span>' + data.data.mensaje + '</span>';
                btnReservar.disabled = false;
            } else {
                resultado.className = 'verificacion-box no-disponible';
                resultado.innerHTML = '<span class="dashicons dashicons-dismiss"></span><span>' + (data.data?.mensaje || '<?php echo esc_js(__('No disponible', 'flavor-chat-ia')); ?>') + '</span>';
                btnReservar.disabled = true;
            }
        })
        .finally(function() {
            btnVerificar.disabled = false;
            btnVerificar.textContent = '<?php echo esc_js(__('Verificar disponibilidad', 'flavor-chat-ia')); ?>';
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append('action', 'reservas_crear');
        formData.append('nonce', '<?php echo wp_create_nonce('reservas_nonce'); ?>');
        formData.append('fecha_fin', formData.get('fecha_inicio'));

        btnReservar.disabled = true;
        btnReservar.textContent = '<?php echo esc_js(__('Reservando...', 'flavor-chat-ia')); ?>';

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultado.className = 'verificacion-box disponible';
                resultado.innerHTML = '<span class="dashicons dashicons-yes-alt"></span><span><?php echo esc_js(__('¡Reserva confirmada!', 'flavor-chat-ia')); ?></span>';
                form.reset();
                btnReservar.disabled = true;

                setTimeout(function() {
                    window.location.href = '<?php echo esc_url(home_url('/reservas/mis-reservas/')); ?>';
                }, 1500);
            } else {
                resultado.className = 'verificacion-box no-disponible';
                resultado.innerHTML = '<span class="dashicons dashicons-dismiss"></span><span>' + (data.data || '<?php echo esc_js(__('Error al reservar', 'flavor-chat-ia')); ?>') + '</span>';
                btnReservar.disabled = false;
                btnReservar.textContent = '<?php echo esc_js(__('Confirmar Reserva', 'flavor-chat-ia')); ?>';
            }
        });
    });

    // Copiar enlace
    document.querySelector('.btn-compartir.copiar')?.addEventListener('click', function() {
        navigator.clipboard.writeText(this.dataset.url).then(function() {
            alert('<?php echo esc_js(__('Enlace copiado', 'flavor-chat-ia')); ?>');
        });
    });
});
</script>
