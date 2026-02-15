<?php
/**
 * Template: Mi Impacto en Espacios Comunes
 *
 * @package FlavorChatIA
 * @subpackage EspaciosComunes
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $impacto Datos de impacto del usuario
 */

if (!defined('ABSPATH')) {
    exit;
}

$puntos = $impacto['puntos'] ?? 0;
$horas_voluntariado = $impacto['horas_voluntariado'] ?? 0;
$cesiones_realizadas = $impacto['cesiones_realizadas'] ?? 0;

// Determinar nivel basado en puntos
$nivel = 1;
$titulo_nivel = __('Vecino', 'flavor-chat-ia');
$siguiente_nivel = 50;

if ($puntos >= 200) {
    $nivel = 5;
    $titulo_nivel = __('Guardián', 'flavor-chat-ia');
    $siguiente_nivel = null;
} elseif ($puntos >= 100) {
    $nivel = 4;
    $titulo_nivel = __('Cuidador', 'flavor-chat-ia');
    $siguiente_nivel = 200;
} elseif ($puntos >= 50) {
    $nivel = 3;
    $titulo_nivel = __('Colaborador', 'flavor-chat-ia');
    $siguiente_nivel = 100;
} elseif ($puntos >= 20) {
    $nivel = 2;
    $titulo_nivel = __('Participante', 'flavor-chat-ia');
    $siguiente_nivel = 50;
}

$progreso = $siguiente_nivel ? min(100, ($puntos / $siguiente_nivel) * 100) : 100;
$usuario = wp_get_current_user();
?>

<div class="ec-mi-impacto">
    <div class="ec-mi-impacto__header">
        <div class="ec-mi-impacto__avatar">
            <?php echo get_avatar(get_current_user_id(), 64); ?>
            <span class="ec-mi-impacto__nivel-badge">Nv.<?php echo esc_html($nivel); ?></span>
        </div>
        <div class="ec-mi-impacto__info">
            <h3 class="ec-mi-impacto__nombre"><?php echo esc_html($usuario->display_name); ?></h3>
            <span class="ec-mi-impacto__titulo"><?php echo esc_html($titulo_nivel); ?></span>
        </div>
    </div>

    <!-- Progreso de nivel -->
    <div class="ec-mi-impacto__progreso">
        <div class="ec-mi-impacto__progreso-header">
            <span><?php printf(esc_html__('%d puntos', 'flavor-chat-ia'), $puntos); ?></span>
            <?php if ($siguiente_nivel): ?>
                <span><?php printf(esc_html__('Siguiente nivel: %d pts', 'flavor-chat-ia'), $siguiente_nivel); ?></span>
            <?php else: ?>
                <span><?php esc_html_e('¡Nivel máximo!', 'flavor-chat-ia'); ?></span>
            <?php endif; ?>
        </div>
        <div class="ec-mi-impacto__progreso-bar">
            <div class="ec-mi-impacto__progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%"></div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="ec-mi-impacto__stats">
        <div class="ec-mi-impacto__stat">
            <span class="ec-mi-impacto__stat-icono" style="background: #9c27b0;">
                <span class="dashicons dashicons-clock"></span>
            </span>
            <div class="ec-mi-impacto__stat-info">
                <span class="ec-mi-impacto__stat-valor"><?php echo esc_html($horas_voluntariado); ?>h</span>
                <span class="ec-mi-impacto__stat-label"><?php esc_html_e('Voluntariado', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="ec-mi-impacto__stat">
            <span class="ec-mi-impacto__stat-icono" style="background: #e91e63;">
                <span class="dashicons dashicons-share-alt"></span>
            </span>
            <div class="ec-mi-impacto__stat-info">
                <span class="ec-mi-impacto__stat-valor"><?php echo esc_html($cesiones_realizadas); ?></span>
                <span class="ec-mi-impacto__stat-label"><?php esc_html_e('Cesiones', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="ec-mi-impacto__stat">
            <span class="ec-mi-impacto__stat-icono" style="background: #ff9800;">
                <span class="dashicons dashicons-star-filled"></span>
            </span>
            <div class="ec-mi-impacto__stat-info">
                <span class="ec-mi-impacto__stat-valor"><?php echo esc_html($puntos); ?></span>
                <span class="ec-mi-impacto__stat-label"><?php esc_html_e('Puntos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Logros -->
    <div class="ec-mi-impacto__logros">
        <h4><?php esc_html_e('Logros desbloqueados', 'flavor-chat-ia'); ?></h4>
        <div class="ec-mi-impacto__logros-grid">
            <?php if ($horas_voluntariado >= 1): ?>
                <div class="ec-mi-impacto__logro ec-mi-impacto__logro--desbloqueado">
                    <span class="dashicons dashicons-hammer"></span>
                    <span><?php esc_html_e('Primer voluntariado', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($cesiones_realizadas >= 1): ?>
                <div class="ec-mi-impacto__logro ec-mi-impacto__logro--desbloqueado">
                    <span class="dashicons dashicons-heart"></span>
                    <span><?php esc_html_e('Primer cesión', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($horas_voluntariado >= 10): ?>
                <div class="ec-mi-impacto__logro ec-mi-impacto__logro--desbloqueado">
                    <span class="dashicons dashicons-awards"></span>
                    <span><?php esc_html_e('10h voluntariado', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($cesiones_realizadas >= 5): ?>
                <div class="ec-mi-impacto__logro ec-mi-impacto__logro--desbloqueado">
                    <span class="dashicons dashicons-superhero"></span>
                    <span><?php esc_html_e('5 cesiones', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($nivel >= 3): ?>
                <div class="ec-mi-impacto__logro ec-mi-impacto__logro--desbloqueado">
                    <span class="dashicons dashicons-star-filled"></span>
                    <span><?php esc_html_e('Colaborador', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($nivel >= 5): ?>
                <div class="ec-mi-impacto__logro ec-mi-impacto__logro--desbloqueado">
                    <span class="dashicons dashicons-shield"></span>
                    <span><?php esc_html_e('Guardián', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>

            <?php
            $logros_desbloqueados = ($horas_voluntariado >= 1 ? 1 : 0) + ($cesiones_realizadas >= 1 ? 1 : 0) +
                                   ($horas_voluntariado >= 10 ? 1 : 0) + ($cesiones_realizadas >= 5 ? 1 : 0) +
                                   ($nivel >= 3 ? 1 : 0) + ($nivel >= 5 ? 1 : 0);
            if ($logros_desbloqueados == 0):
            ?>
                <div class="ec-mi-impacto__logro ec-mi-impacto__logro--bloqueado">
                    <span class="dashicons dashicons-lock"></span>
                    <span><?php esc_html_e('Participa para desbloquear', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Beneficios del nivel -->
    <div class="ec-mi-impacto__beneficios">
        <h4><?php esc_html_e('Beneficios de tu nivel', 'flavor-chat-ia'); ?></h4>
        <ul>
            <?php if ($nivel >= 2): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Prioridad en lista de espera', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel >= 3): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Reservas con más anticipación', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel >= 4): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Descuento en fianza', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel >= 5): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Sin necesidad de aprobación', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel == 1): ?>
                <li class="ec-mi-impacto__beneficio-bloqueado">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e('Sube de nivel para obtener beneficios', 'flavor-chat-ia'); ?>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>

<style>
.ec-mi-impacto {
    --ec-primary: #00897b;
    --ec-primary-light: #e0f2f1;
    --ec-gold: #ffc107;
    --ec-text: #333;
    --ec-text-light: #666;
    --ec-border: #e0e0e0;
    --ec-radius: 12px;
    background: #fff;
    border: 1px solid var(--ec-border);
    border-radius: var(--ec-radius);
    padding: 1.5rem;
    max-width: 400px;
}

.ec-mi-impacto__header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.ec-mi-impacto__avatar {
    position: relative;
}

.ec-mi-impacto__avatar img {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    object-fit: cover;
}

.ec-mi-impacto__nivel-badge {
    position: absolute;
    bottom: -4px;
    right: -4px;
    background: var(--ec-primary);
    color: #fff;
    padding: 0.15rem 0.4rem;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 600;
}

.ec-mi-impacto__nombre {
    margin: 0;
    font-size: 1.1rem;
}

.ec-mi-impacto__titulo {
    display: inline-block;
    padding: 0.2rem 0.5rem;
    background: linear-gradient(135deg, var(--ec-gold), #ffb300);
    color: #333;
    border-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
}

.ec-mi-impacto__progreso {
    margin-bottom: 1.5rem;
}

.ec-mi-impacto__progreso-header {
    display: flex;
    justify-content: space-between;
    font-size: 0.8rem;
    color: var(--ec-text-light);
    margin-bottom: 0.5rem;
}

.ec-mi-impacto__progreso-bar {
    height: 10px;
    background: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
}

.ec-mi-impacto__progreso-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--ec-primary), #26a69a);
    border-radius: 5px;
    transition: width 0.5s ease;
}

.ec-mi-impacto__stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.ec-mi-impacto__stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0.75rem;
    background: #f5f5f5;
    border-radius: 10px;
}

.ec-mi-impacto__stat-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    margin-bottom: 0.5rem;
}

.ec-mi-impacto__stat-icono .dashicons {
    color: #fff;
    font-size: 1.1rem;
    width: 1.1rem;
    height: 1.1rem;
}

.ec-mi-impacto__stat-valor {
    display: block;
    font-size: 1.25rem;
    font-weight: 700;
    line-height: 1.2;
}

.ec-mi-impacto__stat-label {
    font-size: 0.7rem;
    color: var(--ec-text-light);
}

.ec-mi-impacto__logros h4,
.ec-mi-impacto__beneficios h4 {
    font-size: 0.9rem;
    color: var(--ec-text-light);
    margin: 0 0 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.ec-mi-impacto__logros-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.ec-mi-impacto__logro {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.35rem 0.6rem;
    border-radius: 15px;
    font-size: 0.75rem;
}

.ec-mi-impacto__logro--desbloqueado {
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
    border: 1px solid var(--ec-gold);
}

.ec-mi-impacto__logro--desbloqueado .dashicons {
    color: #f57c00;
    font-size: 0.9rem;
    width: 0.9rem;
    height: 0.9rem;
}

.ec-mi-impacto__logro--bloqueado {
    background: #f5f5f5;
    color: #999;
}

.ec-mi-impacto__logro--bloqueado .dashicons {
    font-size: 0.9rem;
    width: 0.9rem;
    height: 0.9rem;
}

.ec-mi-impacto__beneficios ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.ec-mi-impacto__beneficios li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.ec-mi-impacto__beneficios li .dashicons-yes {
    color: var(--ec-primary);
}

.ec-mi-impacto__beneficio-bloqueado {
    color: #999;
}

.ec-mi-impacto__beneficio-bloqueado .dashicons {
    color: #999;
}
</style>
