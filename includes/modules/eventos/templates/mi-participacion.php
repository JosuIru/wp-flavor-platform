<?php
/**
 * Template: Mi Participación en Eventos
 * @var array $participacion Datos de participación del usuario
 * @var array $logros Logros desbloqueados
 */
if (!defined('ABSPATH')) exit;

$usuario = wp_get_current_user();
$puntos = $participacion['puntos_totales'] ?? 0;
$eventos_asistidos = $participacion['eventos_asistidos'] ?? 0;
$horas_voluntariado = $participacion['horas_voluntariado'] ?? 0;
$eventos_organizados = $participacion['eventos_organizados'] ?? 0;

// Determinar nivel
$nivel = 1;
$titulo_nivel = __('Asistente', 'flavor-chat-ia');
$siguiente_nivel = 50;

if ($puntos >= 500) {
    $nivel = 5;
    $titulo_nivel = __('Embajador', 'flavor-chat-ia');
    $siguiente_nivel = null;
} elseif ($puntos >= 200) {
    $nivel = 4;
    $titulo_nivel = __('Organizador', 'flavor-chat-ia');
    $siguiente_nivel = 500;
} elseif ($puntos >= 100) {
    $nivel = 3;
    $titulo_nivel = __('Colaborador', 'flavor-chat-ia');
    $siguiente_nivel = 200;
} elseif ($puntos >= 50) {
    $nivel = 2;
    $titulo_nivel = __('Participante', 'flavor-chat-ia');
    $siguiente_nivel = 100;
}

$progreso = $siguiente_nivel ? min(100, ($puntos / $siguiente_nivel) * 100) : 100;
?>
<div class="ev-mi-part">
    <div class="ev-mi-part__header">
        <div class="ev-mi-part__avatar">
            <?php echo get_avatar(get_current_user_id(), 80); ?>
            <span class="ev-mi-part__nivel-badge">Nv.<?php echo esc_html($nivel); ?></span>
        </div>
        <div class="ev-mi-part__info">
            <h3><?php echo esc_html($usuario->display_name); ?></h3>
            <span class="ev-mi-part__titulo"><?php echo esc_html($titulo_nivel); ?></span>
        </div>
    </div>

    <!-- Progreso de nivel -->
    <div class="ev-mi-part__progreso">
        <div class="ev-mi-part__progreso-header">
            <span><?php printf(esc_html__('%d puntos', 'flavor-chat-ia'), $puntos); ?></span>
            <?php if ($siguiente_nivel): ?>
                <span><?php printf(esc_html__('Siguiente nivel: %d pts', 'flavor-chat-ia'), $siguiente_nivel); ?></span>
            <?php else: ?>
                <span><?php esc_html_e('¡Nivel máximo!', 'flavor-chat-ia'); ?></span>
            <?php endif; ?>
        </div>
        <div class="ev-mi-part__progreso-bar">
            <div class="ev-mi-part__progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%"></div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="ev-mi-part__stats">
        <div class="ev-mi-part__stat">
            <span class="ev-mi-part__stat-icono" style="background: #2196f3;">
                <span class="dashicons dashicons-calendar-alt"></span>
            </span>
            <div class="ev-mi-part__stat-info">
                <span class="ev-mi-part__stat-valor"><?php echo esc_html($eventos_asistidos); ?></span>
                <span class="ev-mi-part__stat-label"><?php esc_html_e('Eventos asistidos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="ev-mi-part__stat">
            <span class="ev-mi-part__stat-icono" style="background: #e91e63;">
                <span class="dashicons dashicons-heart"></span>
            </span>
            <div class="ev-mi-part__stat-info">
                <span class="ev-mi-part__stat-valor"><?php echo esc_html($horas_voluntariado); ?>h</span>
                <span class="ev-mi-part__stat-label"><?php esc_html_e('Voluntariado', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <div class="ev-mi-part__stat">
            <span class="ev-mi-part__stat-icono" style="background: #4caf50;">
                <span class="dashicons dashicons-megaphone"></span>
            </span>
            <div class="ev-mi-part__stat-info">
                <span class="ev-mi-part__stat-valor"><?php echo esc_html($eventos_organizados); ?></span>
                <span class="ev-mi-part__stat-label"><?php esc_html_e('Organizados', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>

    <!-- Huella de carbono personal -->
    <?php if (!empty($participacion['co2_generado']) || !empty($participacion['co2_compensado'])): ?>
        <div class="ev-mi-part__co2">
            <h4>
                <span class="dashicons dashicons-cloud"></span>
                <?php esc_html_e('Mi huella en eventos', 'flavor-chat-ia'); ?>
            </h4>
            <div class="ev-mi-part__co2-grid">
                <div class="ev-mi-part__co2-item ev-mi-part__co2-item--emitido">
                    <span class="ev-mi-part__co2-valor"><?php echo esc_html(number_format($participacion['co2_generado'] ?? 0, 1)); ?></span>
                    <span class="ev-mi-part__co2-label"><?php esc_html_e('kg emitidos', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="ev-mi-part__co2-item ev-mi-part__co2-item--compensado">
                    <span class="ev-mi-part__co2-valor"><?php echo esc_html(number_format($participacion['co2_compensado'] ?? 0, 1)); ?></span>
                    <span class="ev-mi-part__co2-label"><?php esc_html_e('kg compensados', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Logros -->
    <div class="ev-mi-part__logros">
        <h4><?php esc_html_e('Logros desbloqueados', 'flavor-chat-ia'); ?></h4>
        <div class="ev-mi-part__logros-grid">
            <?php if ($eventos_asistidos >= 1): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-tickets-alt"></span>
                    <span><?php esc_html_e('Primer evento', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($eventos_asistidos >= 10): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-star-filled"></span>
                    <span><?php esc_html_e('10 eventos', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($horas_voluntariado >= 1): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-heart"></span>
                    <span><?php esc_html_e('Voluntario', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($horas_voluntariado >= 10): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-superhero"></span>
                    <span><?php esc_html_e('10h voluntariado', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($eventos_organizados >= 1): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-megaphone"></span>
                    <span><?php esc_html_e('Organizador', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($nivel >= 4): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-awards"></span>
                    <span><?php esc_html_e('Nivel Organizador', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($nivel >= 5): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-shield"></span>
                    <span><?php esc_html_e('Embajador', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
            <?php if (($participacion['co2_compensado'] ?? 0) >= 50): ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--desbloqueado">
                    <span class="dashicons dashicons-palmtree"></span>
                    <span><?php esc_html_e('Eco-consciente', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>

            <?php
            // Contar logros desbloqueados
            $total_logros = ($eventos_asistidos >= 1 ? 1 : 0) + ($eventos_asistidos >= 10 ? 1 : 0) +
                           ($horas_voluntariado >= 1 ? 1 : 0) + ($horas_voluntariado >= 10 ? 1 : 0) +
                           ($eventos_organizados >= 1 ? 1 : 0) + ($nivel >= 4 ? 1 : 0) +
                           ($nivel >= 5 ? 1 : 0) + (($participacion['co2_compensado'] ?? 0) >= 50 ? 1 : 0);
            if ($total_logros == 0):
            ?>
                <div class="ev-mi-part__logro ev-mi-part__logro--bloqueado">
                    <span class="dashicons dashicons-lock"></span>
                    <span><?php esc_html_e('Participa para desbloquear', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Beneficios del nivel -->
    <div class="ev-mi-part__beneficios">
        <h4><?php esc_html_e('Beneficios de tu nivel', 'flavor-chat-ia'); ?></h4>
        <ul>
            <?php if ($nivel >= 2): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Inscripción anticipada a eventos', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel >= 3): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Prioridad en eventos con aforo limitado', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel >= 4): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Puede organizar eventos comunitarios', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel >= 5): ?>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Descuentos en eventos de pago', 'flavor-chat-ia'); ?></li>
                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Acceso a eventos exclusivos', 'flavor-chat-ia'); ?></li>
            <?php endif; ?>
            <?php if ($nivel == 1): ?>
                <li class="ev-mi-part__beneficio-bloqueado">
                    <span class="dashicons dashicons-lock"></span>
                    <?php esc_html_e('Sube de nivel para obtener beneficios', 'flavor-chat-ia'); ?>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Próximos eventos -->
    <?php if (!empty($participacion['proximos_eventos'])): ?>
        <div class="ev-mi-part__proximos">
            <h4><?php esc_html_e('Mis próximos eventos', 'flavor-chat-ia'); ?></h4>
            <div class="ev-mi-part__proximos-lista">
                <?php foreach ($participacion['proximos_eventos'] as $evento): ?>
                    <div class="ev-mi-part__proximo">
                        <div class="ev-mi-part__proximo-fecha">
                            <span class="ev-mi-part__proximo-dia"><?php echo esc_html(date_i18n('j', strtotime($evento->fecha_inicio))); ?></span>
                            <span class="ev-mi-part__proximo-mes"><?php echo esc_html(date_i18n('M', strtotime($evento->fecha_inicio))); ?></span>
                        </div>
                        <div class="ev-mi-part__proximo-info">
                            <strong><?php echo esc_html($evento->titulo); ?></strong>
                            <span><?php echo esc_html(date_i18n('H:i', strtotime($evento->fecha_inicio))); ?></span>
                        </div>
                        <?php if ($evento->es_voluntario): ?>
                            <span class="ev-mi-part__proximo-badge">
                                <span class="dashicons dashicons-heart"></span>
                                <?php esc_html_e('Voluntario', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<style>
.ev-mi-part{--ev-primary:#673ab7;--ev-primary-light:#ede7f6;--ev-gold:#ffc107;--ev-text:#333;--ev-text-light:#666;--ev-border:#e0e0e0;background:#fff;border:1px solid var(--ev-border);border-radius:12px;padding:1.5rem;max-width:450px}
.ev-mi-part__header{display:flex;align-items:center;gap:1rem;margin-bottom:1.5rem}
.ev-mi-part__avatar{position:relative}
.ev-mi-part__avatar img{width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--ev-primary)}
.ev-mi-part__nivel-badge{position:absolute;bottom:-4px;right:-4px;background:var(--ev-primary);color:#fff;padding:.2rem .5rem;border-radius:10px;font-size:.75rem;font-weight:600}
.ev-mi-part__info h3{margin:0;font-size:1.2rem}
.ev-mi-part__titulo{display:inline-block;padding:.25rem .6rem;background:linear-gradient(135deg,var(--ev-gold),#ffb300);color:#333;border-radius:12px;font-size:.8rem;font-weight:600;margin-top:.25rem}
.ev-mi-part__progreso{margin-bottom:1.5rem}
.ev-mi-part__progreso-header{display:flex;justify-content:space-between;font-size:.8rem;color:var(--ev-text-light);margin-bottom:.5rem}
.ev-mi-part__progreso-bar{height:12px;background:#e0e0e0;border-radius:6px;overflow:hidden}
.ev-mi-part__progreso-fill{height:100%;background:linear-gradient(90deg,var(--ev-primary),#9c27b0);border-radius:6px;transition:width .5s ease}
.ev-mi-part__stats{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.5rem}
.ev-mi-part__stat{display:flex;flex-direction:column;align-items:center;text-align:center;padding:.75rem;background:#f5f5f5;border-radius:10px}
.ev-mi-part__stat-icono{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;margin-bottom:.5rem}
.ev-mi-part__stat-icono .dashicons{color:#fff;font-size:1.25rem;width:1.25rem;height:1.25rem}
.ev-mi-part__stat-valor{font-size:1.4rem;font-weight:700;line-height:1.2}
.ev-mi-part__stat-label{font-size:.7rem;color:var(--ev-text-light)}
.ev-mi-part__co2{padding:1rem;background:#e8f5e9;border-radius:10px;margin-bottom:1.5rem}
.ev-mi-part__co2 h4{display:flex;align-items:center;gap:.5rem;margin:0 0 .75rem;font-size:.9rem;color:#2e7d32}
.ev-mi-part__co2-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}
.ev-mi-part__co2-item{text-align:center;padding:.75rem;border-radius:8px}
.ev-mi-part__co2-item--emitido{background:rgba(244,67,54,.1)}
.ev-mi-part__co2-item--compensado{background:rgba(76,175,80,.2)}
.ev-mi-part__co2-valor{display:block;font-size:1.5rem;font-weight:700}
.ev-mi-part__co2-item--emitido .ev-mi-part__co2-valor{color:#c62828}
.ev-mi-part__co2-item--compensado .ev-mi-part__co2-valor{color:#2e7d32}
.ev-mi-part__co2-label{font-size:.75rem;color:var(--ev-text-light)}
.ev-mi-part__logros{margin-bottom:1.5rem}
.ev-mi-part__logros h4{margin:0 0 .75rem;font-size:.9rem;color:var(--ev-text-light);text-transform:uppercase}
.ev-mi-part__logros-grid{display:flex;flex-wrap:wrap;gap:.5rem}
.ev-mi-part__logro{display:flex;align-items:center;gap:.25rem;padding:.35rem .6rem;border-radius:15px;font-size:.75rem}
.ev-mi-part__logro--desbloqueado{background:linear-gradient(135deg,#fff8e1,#ffecb3);border:1px solid var(--ev-gold)}
.ev-mi-part__logro--desbloqueado .dashicons{color:#f57c00;font-size:.9rem;width:.9rem;height:.9rem}
.ev-mi-part__logro--bloqueado{background:#f5f5f5;color:#999}
.ev-mi-part__logro--bloqueado .dashicons{font-size:.9rem;width:.9rem;height:.9rem}
.ev-mi-part__beneficios{margin-bottom:1.5rem}
.ev-mi-part__beneficios h4{margin:0 0 .75rem;font-size:.9rem;color:var(--ev-text-light);text-transform:uppercase}
.ev-mi-part__beneficios ul{margin:0;padding:0;list-style:none}
.ev-mi-part__beneficios li{display:flex;align-items:center;gap:.5rem;font-size:.9rem;margin-bottom:.5rem}
.ev-mi-part__beneficios li .dashicons-yes{color:var(--ev-primary)}
.ev-mi-part__beneficio-bloqueado{color:#999}
.ev-mi-part__beneficio-bloqueado .dashicons{color:#999}
.ev-mi-part__proximos h4{margin:0 0 .75rem;font-size:.9rem;color:var(--ev-text-light);text-transform:uppercase}
.ev-mi-part__proximos-lista{display:grid;gap:.75rem}
.ev-mi-part__proximo{display:flex;align-items:center;gap:.75rem;padding:.75rem;border:1px solid var(--ev-border);border-radius:10px}
.ev-mi-part__proximo:hover{border-color:var(--ev-primary)}
.ev-mi-part__proximo-fecha{width:50px;height:50px;background:var(--ev-primary-light);border-radius:10px;display:flex;flex-direction:column;align-items:center;justify-content:center}
.ev-mi-part__proximo-dia{font-size:1.25rem;font-weight:700;line-height:1;color:var(--ev-primary)}
.ev-mi-part__proximo-mes{font-size:.65rem;text-transform:uppercase;color:var(--ev-primary)}
.ev-mi-part__proximo-info{flex:1}
.ev-mi-part__proximo-info strong{display:block;font-size:.9rem}
.ev-mi-part__proximo-info span{font-size:.8rem;color:var(--ev-text-light)}
.ev-mi-part__proximo-badge{display:flex;align-items:center;gap:.25rem;padding:.25rem .5rem;background:#fce4ec;color:#e91e63;border-radius:12px;font-size:.7rem;font-weight:500}
.ev-mi-part__proximo-badge .dashicons{font-size:.8rem;width:.8rem;height:.8rem}
@media(max-width:400px){.ev-mi-part__stats{grid-template-columns:1fr}}
</style>
