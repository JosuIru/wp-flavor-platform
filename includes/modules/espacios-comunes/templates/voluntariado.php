<?php
/**
 * Template: Tareas de Voluntariado
 *
 * @package FlavorChatIA
 * @subpackage EspaciosComunes
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $tareas Lista de tareas de voluntariado
 * @var string $nonce Nonce de seguridad
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_logueado = is_user_logged_in();
$usuario_id = get_current_user_id();

$iconos_tipo = [
    'limpieza' => 'dashicons-visibility',
    'mantenimiento' => 'dashicons-hammer',
    'jardineria' => 'dashicons-palmtree',
    'pintura' => 'dashicons-art',
    'reparacion' => 'dashicons-admin-tools',
    'organizacion' => 'dashicons-clipboard',
    'otro' => 'dashicons-admin-generic',
];

$colores_urgencia = [
    'baja' => '#4caf50',
    'media' => '#ff9800',
    'alta' => '#f44336',
];
?>

<div class="ec-voluntariado" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="ec-voluntariado__header">
        <div class="ec-voluntariado__titulo-wrap">
            <span class="dashicons dashicons-heart"></span>
            <h3 class="ec-voluntariado__titulo"><?php esc_html_e('Cuidado Comunitario', 'flavor-chat-ia'); ?></h3>
        </div>
        <p class="ec-voluntariado__descripcion">
            <?php esc_html_e('Tareas de mantenimiento que necesitan voluntarios. ¡Colabora y gana puntos!', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <?php if (empty($tareas)): ?>
        <div class="ec-voluntariado__vacio">
            <span class="dashicons dashicons-smiley"></span>
            <p><?php esc_html_e('¡Todo está en orden!', 'flavor-chat-ia'); ?></p>
            <small><?php esc_html_e('No hay tareas de mantenimiento pendientes.', 'flavor-chat-ia'); ?></small>
        </div>
    <?php else: ?>
        <div class="ec-voluntariado__lista">
            <?php foreach ($tareas as $tarea): ?>
                <?php
                $icono = $iconos_tipo[$tarea->tipo] ?? 'dashicons-admin-generic';
                $color_urgencia = $colores_urgencia[$tarea->urgencia] ?? '#607d8b';
                $plazas_libres = $tarea->personas_necesarias - $tarea->personas_apuntadas;
                $fecha_tarea = $tarea->fecha_tarea ? strtotime($tarea->fecha_tarea) : null;
                ?>
                <div class="ec-voluntariado__tarea" data-id="<?php echo esc_attr($tarea->id); ?>">
                    <div class="ec-voluntariado__tarea-header">
                        <span class="ec-voluntariado__tarea-icono">
                            <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                        </span>
                        <div class="ec-voluntariado__tarea-info">
                            <h4><?php echo esc_html($tarea->titulo); ?></h4>
                            <span class="ec-voluntariado__espacio">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($tarea->espacio_nombre); ?>
                            </span>
                        </div>
                        <span class="ec-voluntariado__urgencia" style="background: <?php echo esc_attr($color_urgencia); ?>">
                            <?php echo esc_html(ucfirst($tarea->urgencia)); ?>
                        </span>
                    </div>

                    <?php if ($tarea->descripcion): ?>
                        <p class="ec-voluntariado__descripcion-tarea"><?php echo esc_html($tarea->descripcion); ?></p>
                    <?php endif; ?>

                    <div class="ec-voluntariado__detalles">
                        <?php if ($fecha_tarea): ?>
                            <span class="ec-voluntariado__detalle">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html(date_i18n('j M', $fecha_tarea)); ?>
                                <?php if ($tarea->hora_inicio): ?>
                                    <?php echo esc_html(date_i18n('H:i', strtotime($tarea->hora_inicio))); ?>
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <span class="ec-voluntariado__detalle">
                            <span class="dashicons dashicons-clock"></span>
                            ~<?php echo esc_html(number_format($tarea->horas_estimadas, 1)); ?>h
                        </span>
                        <span class="ec-voluntariado__detalle ec-voluntariado__puntos">
                            <span class="dashicons dashicons-star-filled"></span>
                            +<?php echo esc_html($tarea->puntos_recompensa); ?> pts
                        </span>
                    </div>

                    <?php if ($tarea->materiales_necesarios): ?>
                        <div class="ec-voluntariado__materiales">
                            <strong><?php esc_html_e('Materiales:', 'flavor-chat-ia'); ?></strong>
                            <?php echo esc_html($tarea->materiales_necesarios); ?>
                        </div>
                    <?php endif; ?>

                    <div class="ec-voluntariado__footer">
                        <div class="ec-voluntariado__plazas">
                            <div class="ec-voluntariado__plazas-bar">
                                <?php for ($i = 0; $i < $tarea->personas_necesarias; $i++): ?>
                                    <span class="ec-voluntariado__plaza <?php echo $i < $tarea->personas_apuntadas ? 'ocupada' : ''; ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <span class="ec-voluntariado__plazas-texto">
                                <?php printf(
                                    esc_html(_n('%d plaza libre', '%d plazas libres', $plazas_libres, 'flavor-chat-ia')),
                                    $plazas_libres
                                ); ?>
                            </span>
                        </div>

                        <?php if ($usuario_logueado && $plazas_libres > 0): ?>
                            <button type="button" class="ec-btn ec-btn--primary ec-voluntariado__apuntarse" data-tarea="<?php echo esc_attr($tarea->id); ?>">
                                <span class="dashicons dashicons-plus"></span>
                                <?php esc_html_e('Apuntarme', 'flavor-chat-ia'); ?>
                            </button>
                        <?php elseif (!$usuario_logueado): ?>
                            <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="ec-btn ec-btn--outline">
                                <?php esc_html_e('Inicia sesión', 'flavor-chat-ia'); ?>
                            </a>
                        <?php elseif ($plazas_libres == 0): ?>
                            <span class="ec-voluntariado__completo"><?php esc_html_e('Completo', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="ec-voluntariado__info-puntos">
        <span class="dashicons dashicons-info"></span>
        <p><?php esc_html_e('Los puntos de cuidado comunitario se pueden usar para prioridad en reservas y beneficios en otros módulos.', 'flavor-chat-ia'); ?></p>
    </div>
</div>

<style>
.ec-voluntariado {
    --ec-primary: #9c27b0;
    --ec-primary-light: #f3e5f5;
    --ec-text: #333;
    --ec-text-light: #666;
    --ec-border: #e0e0e0;
    --ec-radius: 12px;
    background: #fff;
    border: 1px solid var(--ec-border);
    border-radius: var(--ec-radius);
    padding: 1.5rem;
}

.ec-voluntariado__header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.ec-voluntariado__titulo-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.ec-voluntariado__titulo-wrap .dashicons {
    color: var(--ec-primary);
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.ec-voluntariado__titulo {
    margin: 0;
    font-size: 1.25rem;
}

.ec-voluntariado__descripcion {
    margin: 0.5rem 0 0;
    color: var(--ec-text-light);
    font-size: 0.9rem;
}

.ec-voluntariado__vacio {
    text-align: center;
    padding: 2rem;
    color: var(--ec-text-light);
}

.ec-voluntariado__vacio .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    color: #4caf50;
}

.ec-voluntariado__lista {
    display: grid;
    gap: 1rem;
}

.ec-voluntariado__tarea {
    padding: 1rem;
    border: 1px solid var(--ec-border);
    border-radius: 10px;
    transition: all 0.2s;
}

.ec-voluntariado__tarea:hover {
    border-color: var(--ec-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.ec-voluntariado__tarea-header {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.ec-voluntariado__tarea-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--ec-primary-light);
    border-radius: 10px;
    flex-shrink: 0;
}

.ec-voluntariado__tarea-icono .dashicons {
    color: var(--ec-primary);
    font-size: 1.25rem;
    width: 1.25rem;
    height: 1.25rem;
}

.ec-voluntariado__tarea-info {
    flex: 1;
    min-width: 0;
}

.ec-voluntariado__tarea-info h4 {
    margin: 0 0 0.25rem;
    font-size: 1rem;
}

.ec-voluntariado__espacio {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-voluntariado__espacio .dashicons {
    font-size: 0.9rem;
    width: 0.9rem;
    height: 0.9rem;
}

.ec-voluntariado__urgencia {
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
    color: #fff;
    text-transform: uppercase;
}

.ec-voluntariado__descripcion-tarea {
    margin: 0 0 0.75rem;
    font-size: 0.9rem;
    color: var(--ec-text-light);
}

.ec-voluntariado__detalles {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 0.75rem;
}

.ec-voluntariado__detalle {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-voluntariado__detalle .dashicons {
    font-size: 0.95rem;
    width: 0.95rem;
    height: 0.95rem;
}

.ec-voluntariado__puntos {
    color: #ff9800;
    font-weight: 600;
}

.ec-voluntariado__puntos .dashicons {
    color: #ff9800;
}

.ec-voluntariado__materiales {
    font-size: 0.85rem;
    padding: 0.5rem;
    background: #f5f5f5;
    border-radius: 6px;
    margin-bottom: 0.75rem;
}

.ec-voluntariado__footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 0.75rem;
    border-top: 1px solid var(--ec-border);
}

.ec-voluntariado__plazas-bar {
    display: flex;
    gap: 4px;
    margin-bottom: 0.25rem;
}

.ec-voluntariado__plaza {
    width: 20px;
    height: 20px;
    background: #e0e0e0;
    border-radius: 50%;
    transition: background 0.2s;
}

.ec-voluntariado__plaza.ocupada {
    background: var(--ec-primary);
}

.ec-voluntariado__plazas-texto {
    font-size: 0.8rem;
    color: var(--ec-text-light);
}

.ec-voluntariado__completo {
    padding: 0.4rem 0.8rem;
    background: #f5f5f5;
    border-radius: 6px;
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-voluntariado__info-puntos {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-top: 1rem;
    padding: 0.75rem;
    background: #fff3e0;
    border-radius: 8px;
}

.ec-voluntariado__info-puntos .dashicons {
    color: #ff9800;
    flex-shrink: 0;
}

.ec-voluntariado__info-puntos p {
    margin: 0;
    font-size: 0.85rem;
}

.ec-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}

.ec-btn--primary {
    background: var(--ec-primary);
    color: #fff;
}

.ec-btn--primary:hover {
    background: #7b1fa2;
}

.ec-btn--outline {
    background: transparent;
    border: 1px solid var(--ec-primary);
    color: var(--ec-primary);
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.ec-voluntariado');
        if (!container) return;

        const nonce = container.dataset.nonce;

        container.querySelectorAll('.ec-voluntariado__apuntarse').forEach(btn => {
            btn.addEventListener('click', function() {
                const tareaId = this.dataset.tarea;

                this.disabled = true;
                this.innerHTML = '<span class="dashicons dashicons-update"></span>';

                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'ec_apuntarse_voluntariado',
                        nonce: nonce,
                        tarea_id: tareaId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    alert(data.success ? data.message : (data.error || '<?php echo esc_js(__('Error', 'flavor-chat-ia')); ?>'));
                    if (data.success) {
                        location.reload();
                    } else {
                        this.disabled = false;
                        this.innerHTML = '<span class="dashicons dashicons-plus"></span> <?php echo esc_js(__('Apuntarme', 'flavor-chat-ia')); ?>';
                    }
                });
            });
        });
    });
})();
</script>
