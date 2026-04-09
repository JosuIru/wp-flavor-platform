<?php
/**
 * Template: Mis Inscripciones de Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="eventos-login-required"><span class="dashicons dashicons-lock"></span><h3>' . __('Inicia sesión para ver tus inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3><a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . __('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></div>';
    return;
}

global $wpdb;
$tabla_eventos = $wpdb->prefix . 'flavor_eventos';
$tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

$usuario_id = get_current_user_id();

// Obtener inscripciones del usuario
$inscripciones = $wpdb->get_results($wpdb->prepare(
    "SELECT i.*, e.titulo, e.fecha_inicio, e.fecha_fin,
            e.ubicacion, e.imagen, e.precio, e.tipo, e.estado as evento_estado
     FROM $tabla_inscripciones i
     INNER JOIN $tabla_eventos e ON i.evento_id = e.id
     WHERE i.user_id = %d
     ORDER BY e.fecha_inicio DESC",
    $usuario_id
));

// Función auxiliar para extraer hora de datetime
$extraer_hora = function($datetime) {
    if (empty($datetime)) return '';
    return date('H:i', strtotime($datetime));
};

// Separar en próximas y pasadas
$proximas = [];
$pasadas = [];
$hoy = current_time('Y-m-d');

foreach ($inscripciones as $inscripcion) {
    if ($inscripcion->fecha_inicio >= $hoy) {
        $proximas[] = $inscripcion;
    } else {
        $pasadas[] = $inscripcion;
    }
}

$estados_labels = [
    'confirmada' => __('Confirmada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cancelada' => __('Cancelada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'lista_espera' => __('Lista de espera', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="eventos-inscripciones-wrapper">
    <div class="eventos-tabs">
        <button class="eventos-tab active" data-tab="proximas">
            <?php _e('Próximas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if (count($proximas) > 0): ?>
                <span class="eventos-badge"><?php echo count($proximas); ?></span>
            <?php endif; ?>
        </button>
        <button class="eventos-tab" data-tab="pasadas">
            <?php _e('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </button>
    </div>

    <!-- Panel: Próximas -->
    <div id="proximas" class="eventos-panel">
        <?php if ($proximas): ?>
            <div class="eventos-grid">
                <?php foreach ($proximas as $inscripcion): ?>
                    <div class="evento-card">
                        <div class="evento-card-imagen">
                            <?php if (!empty($inscripcion->imagen)): ?>
                                <img src="<?php echo esc_url($inscripcion->imagen); ?>" alt="">
                            <?php else: ?>
                                <div class="evento-card-placeholder">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                </div>
                            <?php endif; ?>
                            <span class="evento-card-tipo"><?php echo esc_html(ucfirst($inscripcion->tipo)); ?></span>
                        </div>
                        <div class="evento-card-body">
                            <h4><?php echo esc_html($inscripcion->titulo); ?></h4>
                            <div class="evento-card-meta">
                                <span class="evento-card-fecha">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($inscripcion->fecha_inicio)); ?>
                                </span>
                                <?php
                                $hora_inscripcion = $extraer_hora($inscripcion->fecha_inicio);
                                if ($hora_inscripcion && $hora_inscripcion !== '00:00'): ?>
                                    <span class="evento-card-hora">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html($hora_inscripcion); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($inscripcion->ubicacion): ?>
                                <div class="evento-card-ubicacion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($inscripcion->ubicacion); ?>
                                </div>
                            <?php endif; ?>
                            <span class="evento-card-estado estado-<?php echo esc_attr($inscripcion->estado); ?>">
                                <?php echo $estados_labels[$inscripcion->estado] ?? $inscripcion->estado; ?>
                            </span>
                        </div>
                        <div class="evento-card-footer">
                            <a href="<?php echo esc_url(add_query_arg('evento_id', $inscripcion->evento_id, Flavor_Chat_Helpers::get_action_url('eventos', 'detalle'))); ?>" class="btn btn-outline btn-sm">
                                <?php _e('Ver evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <?php if ($inscripcion->estado === 'confirmada' || $inscripcion->estado === 'pendiente'): ?>
                                <button class="btn btn-danger btn-sm btn-cancelar-inscripcion" data-inscripcion-id="<?php echo $inscripcion->id; ?>">
                                    <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="eventos-empty">
                <span class="dashicons dashicons-calendar-alt"></span>
                <h3><?php _e('No tienes inscripciones próximas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Explora los eventos disponibles y apúntate.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('eventos', '')); ?>" class="btn btn-primary">
                    <?php _e('Ver eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel: Historial -->
    <div id="pasadas" class="eventos-panel" style="display: none;">
        <?php if ($pasadas): ?>
            <div class="eventos-grid">
                <?php foreach ($pasadas as $inscripcion): ?>
                    <div class="evento-card evento-card--pasado">
                        <div class="evento-card-imagen">
                            <?php if (!empty($inscripcion->imagen)): ?>
                                <img src="<?php echo esc_url($inscripcion->imagen); ?>" alt="">
                            <?php else: ?>
                                <div class="evento-card-placeholder">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="evento-card-body">
                            <h4><?php echo esc_html($inscripcion->titulo); ?></h4>
                            <div class="evento-card-meta">
                                <span class="evento-card-fecha">
                                    <span class="dashicons dashicons-calendar"></span>
                                    <?php echo date_i18n(get_option('date_format'), strtotime($inscripcion->fecha_inicio)); ?>
                                </span>
                            </div>
                            <span class="evento-card-estado estado-<?php echo esc_attr($inscripcion->estado); ?>">
                                <?php echo $estados_labels[$inscripcion->estado] ?? $inscripcion->estado; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="eventos-empty">
                <span class="dashicons dashicons-backup"></span>
                <h3><?php _e('Sin historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Aquí aparecerán tus eventos pasados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.eventos-inscripciones-wrapper { max-width: 1200px; margin: 0 auto; }
.eventos-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e5e7eb; padding-bottom: 0; }
.eventos-tab { padding: 0.75rem 1.5rem; background: transparent; border: none; cursor: pointer; font-weight: 500; color: #6b7280; position: relative; bottom: -2px; border-bottom: 2px solid transparent; }
.eventos-tab.active { color: #4f46e5; border-bottom-color: #4f46e5; }
.eventos-badge { background: #4f46e5; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; margin-left: 0.5rem; }
.eventos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
.evento-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; display: flex; flex-direction: column; }
.evento-card--pasado { opacity: 0.75; }
.evento-card-imagen { position: relative; height: 150px; background: #f3f4f6; }
.evento-card-imagen img { width: 100%; height: 100%; object-fit: cover; }
.evento-card-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; }
.evento-card-placeholder .dashicons { font-size: 48px; color: #9ca3af; }
.evento-card-tipo { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.6); color: white; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; }
.evento-card-body { padding: 1rem; flex: 1; }
.evento-card-body h4 { margin: 0 0 0.75rem; font-size: 1rem; }
.evento-card-meta { display: flex; gap: 1rem; margin-bottom: 0.5rem; font-size: 0.875rem; color: #6b7280; }
.evento-card-meta .dashicons { font-size: 14px; width: 14px; height: 14px; }
.evento-card-ubicacion { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.75rem; }
.evento-card-ubicacion .dashicons { font-size: 14px; width: 14px; height: 14px; }
.evento-card-estado { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }
.estado-confirmada { background: #d1fae5; color: #065f46; }
.estado-pendiente { background: #fef3c7; color: #92400e; }
.estado-cancelada { background: #fee2e2; color: #991b1b; }
.estado-lista_espera { background: #e0e7ff; color: #3730a3; }
.evento-card-footer { padding: 1rem; border-top: 1px solid #f3f4f6; display: flex; gap: 0.5rem; }
.eventos-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.eventos-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.eventos-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.eventos-empty p { margin: 0 0 1.5rem; color: #6b7280; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; }
.btn-primary { background: #4f46e5; color: white; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-danger { background: #ef4444; color: white; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.eventos-tab');
    const panels = document.querySelectorAll('.eventos-panel');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;

            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            panels.forEach(panel => {
                panel.style.display = panel.id === target ? 'block' : 'none';
            });
        });
    });
});
</script>
